<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Activity\MailChimpBundle\Controller;

use CampaignChain\Operation\MailChimpBundle\Entity\MailChimpNewsletter;
use CampaignChain\Operation\MailChimpBundle\Form\Type\MailChimpOperationType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use CampaignChain\CoreBundle\Entity\Operation;
use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\CoreBundle\Entity\Medium;

class MailChimpController extends Controller
{
    const ACTIVITY_BUNDLE_NAME          = 'campaignchain/activity-mailchimp';
    const ACTIVITY_MODULE_IDENTIFIER    = 'campaignchain-mailchimp';
    const OPERATION_BUNDLE_NAME         = 'campaignchain/operation-mailchimp';
    const OPERATION_MODULE_IDENTIFIER   = 'campaignchain-mailchimp-newsletter';
    const LOCATION_BUNDLE_NAME          = 'campaignchain/location-mailchimp';
    const LOCATION_MODULE_IDENTIFIER    = 'campaignchain-mailchimp-newsletter';
    const TRIGGER_HOOK_IDENTIFIER       = 'campaignchain-due';
    const LINK_ADMIN_CAMPAIGNS          = 'https://admin.mailchimp.com/campaigns/';

    public function newAction(Request $request)
    {
        $wizard = $this->get('campaignchain.core.activity.wizard');
        $campaign = $wizard->getCampaign();
        $activity = $wizard->getActivity();
        $location = $wizard->getLocation();

        $activity->setEqualsOperation(true);

        // Retrieve upcoming newsletter campaigns from MailChimp.
        $restService = $this->get('campaignchain.channel.mailchimp.rest.client');
        $client = $restService->connectByLocation($location);
        $upcomingNewsletters = $client->campaigns->getList(array(
            'status' => 'save,paused,schedule',
        ));

        if($upcomingNewsletters['total'] == 0){
            $this->get('session')->getFlashBag()->add(
                'warning',
                'No upcoming newsletter campaigns available.'
            );

            return $this->redirect(
                $this->generateUrl('campaignchain_core_activities_new')
            );
        }

        $locationService = $this->get('campaignchain.core.location');

        foreach($upcomingNewsletters['data'] as $key => $upcomingNewsletter){
            // Check if newsletter has already been added to this Campaign.
            if(!$locationService->existsInCampaign(
                self::LOCATION_BUNDLE_NAME, self::LOCATION_MODULE_IDENTIFIER,
                $upcomingNewsletter['id'], $campaign
            )){
                // TODO: If send_time not empty, then pass to due hook.
                $newsletters[$key] = $upcomingNewsletter['title']
                    .'('.$upcomingNewsletter['subject'].')';
            } else {
                $this->get('session')->getFlashBag()->add(
                    'warning',
                    'All upcoming newsletters have already been added to the campaign "'.$campaign->getName().'".'
                );

                return $this->redirect(
                    $this->generateUrl('campaignchain_core_activities_new')
                );
            }
        }

        $activityType = $this->get('campaignchain.core.form.type.activity');
        $activityType->setBundleName(self::ACTIVITY_BUNDLE_NAME);
        $activityType->setModuleIdentifier(self::ACTIVITY_MODULE_IDENTIFIER);
        $activityType->showNameField(false);

        $operationType = new MailChimpOperationType($this->getDoctrine()->getManager(), $this->get('service_container'));

        $location = $locationService->getLocation($location->getId());

        // Have the location point to the list of newsletters in the
        // MailChimp website's admin area.
        $location->setUrl(self::LINK_ADMIN_CAMPAIGNS);

        $operationType->setLocation($location);

        $operationType->setNewsletters($newsletters);

        $operationForms[] = array(
            'identifier' => self::OPERATION_MODULE_IDENTIFIER,
            'form' => $operationType,
            'label' => 'Include Webinar',
        );
        $activityType->setOperationForms($operationForms);
        $activityType->setCampaign($campaign);

        $form = $this->createForm($activityType, $activity);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $newsletterKey = $form->get(self::OPERATION_MODULE_IDENTIFIER)->getData()['newsletter'];
            $newsletter = $upcomingNewsletters['data'][$key];

            $activity = $wizard->end();

            $activity->setName($newsletter['title']);

            // Get the operation module.
            $operationService = $this->get('campaignchain.core.operation');
            $operationModule = $operationService->getOperationModule(self::OPERATION_BUNDLE_NAME, self::OPERATION_MODULE_IDENTIFIER);

            // The activity equals the operation. Thus, we create a new operation with the same data.
            $operation = new Operation();
            $operation->setName($newsletter['title']);
            $operation->setActivity($activity);
            $activity->addOperation($operation);
            $operationModule->addOperation($operation);
            $operation->setOperationModule($operationModule);

            // The Operation creates a Location, i.e. the newsletter
            // will be accessible through an archive URL after publishing.
            // Get the location module for the user stream.
            $locationService = $this->get('campaignchain.core.location');
            $locationModule = $locationService->getLocationModule(
                self::LOCATION_BUNDLE_NAME,
                self::LOCATION_MODULE_IDENTIFIER
            );

            $location = new Location();
            $location->setLocationModule($locationModule);
            $location->setParent($activity->getLocation());
            $location->setIdentifier($newsletter['id']);
            $location->setName($newsletter['title']);
            $location->setUrl($newsletter['archive_url_long']);
            $location->setStatus(Medium::STATUS_UNPUBLISHED);
            $location->setOperation($operation);
            $operation->addLocation($location);

            // Store newsletter details.
            $newsletterOperation = new MailChimpNewsletter();
            $newsletterOperation->setOperation($operation);
            $newsletterOperation->setCampaignId($newsletter['id']);
            $newsletterOperation->setWebId($newsletter['web_id']);
            $newsletterOperation->setListId($newsletter['list_id']);
            $newsletterOperation->setFolderId($newsletter['folder_id']);
            $newsletterOperation->setTemplateId($newsletter['template_id']);
            $newsletterOperation->setContentType($newsletter['content_type']);
            $newsletterOperation->setTitle($newsletter['title']);
            $newsletterOperation->setType($newsletter['type']);
            $newsletterOperation->setCreateTime(new \DateTime($newsletter['create_time']));
            if(isset($newsletter['send_time']) && strlen($newsletter['send_time'])){
                $newsletterOperation->setSendTime(new \DateTime($newsletter['send_time']));
            }
            if(isset($newsletter['content_updated_time']) && strlen($newsletter['content_updated_time'])){
                $newsletterOperation->setContentUpdatedTime(new \DateTime($newsletter['content_updated_time']));
            }
            $newsletterOperation->setStatus($newsletter['status']);
            $newsletterOperation->setFromName($newsletter['from_name']);
            $newsletterOperation->setFromEmail($newsletter['from_email']);
            $newsletterOperation->setSubject($newsletter['subject']);
            $newsletterOperation->setArchiveUrl($newsletter['archive_url']);
            $newsletterOperation->setArchiveUrlLong($newsletter['archive_url_long']);
            $newsletterOperation->setTrackingHtmlClicks($newsletter['tracking']['html_clicks']);
            $newsletterOperation->setTrackingTextClicks($newsletter['tracking']['text_clicks']);
            $newsletterOperation->setTrackingOpens($newsletter['tracking']['opens']);


            $repository = $this->getDoctrine()->getManager();

            // Make sure that data stays intact by using transactions.
            try {
                $repository->getConnection()->beginTransaction();

                $repository->persist($operation);
                $repository->persist($activity);
                $repository->persist($newsletterOperation);

                $repository->flush();

                $hookService = $this->get('campaignchain.core.hook');
                $activity = $hookService->processHooks(self::ACTIVITY_BUNDLE_NAME, self::ACTIVITY_MODULE_IDENTIFIER, $activity, $form, true);
                $repository->flush();

                // Set send time per Activity's start time
                $newsletterOperation->setSendTime($activity->getStartDate());
                $repository->flush();

                // Schedule the newsletter on MailChimp.
//                $client->campaigns->schedule(
//                    $newsletterOperation->getCampaignId(),
//                    $newsletterOperation->getSendTime()->format()
//                );

                $repository->getConnection()->commit();
            } catch (\Exception $e) {
                $repository->getConnection()->rollback();
                throw $e;
            }

            $this->get('session')->getFlashBag()->add(
                'success',
                'The newsletter <a href="'.$this->generateUrl('campaignchain_core_activity_edit', array('id' => $activity->getId())).'">'.$activity->getName().'</a> has been added successfully.'
            );

            return $this->redirect($this->generateUrl('campaignchain_core_activities'));
        }

        return $this->render(
            'CampaignChainCoreBundle:Operation:new.html.twig',
            array(
                'page_title' => 'Add a Newsletter',
                'activity' => $activity,
                'campaign' => $campaign,
                'channel_module' => $wizard->getChannelModule(),
                'channel_module_bundle' => $wizard->getChannelModuleBundle(),
                'location' => $location,
                'form' => $form->createView(),
                'form_submit_label' => 'Save',
                'form_cancel_route' => 'campaignchain_core_activities_new'
            ));

    }

    public function editAction(Request $request, $id)
    {
        return $this->redirect(
            $this->generateUrl(
                'campaignchain_activity_mailchimp_read',
                array(
                    'id' => $id,
                )
            )
        );
    }

    public function readAction(Request $request, $id){
        $activityService = $this->get('campaignchain.core.activity');
        $activity = $activityService->getActivity($id);

        // Get the one operation.
        $operation = $activityService->getOperation($id);

        // Get the newsletter details.
        $newsletter = $this->getNewsletter($operation);

        // TODO: Check if newsletter schedule dates were edited on MailChimp.

        return $this->render(
            'CampaignChainOperationMailChimpBundle::read.html.twig',
            array(
                'page_title' => $activity->getName(),
                'operation' => $operation,
                'activity' => $activity,
                'newsletter' => $newsletter,
                'show_date' => true,
            ));
    }

    public function editModalAction(Request $request, $id)
    {
        $activityService = $this->get('campaignchain.core.activity');
        $activity = $activityService->getActivity($id);

        // Get the one operation.
        $operation = $activityService->getOperation($id);

        // Get the newsletter details.
        $newsletter = $this->getNewsletter($operation);

        // TODO: Check if newsletter schedule dates were edited on MailChimp.

        return $this->render(
            'CampaignChainOperationMailChimpBundle::read_modal.html.twig',
            array(
                'page_title' => $activity->getName(),
                'operation' => $operation,
                'activity' => $activity,
                'newsletter' => $newsletter,
                'show_date' => true,
            ));
    }

    public function previewAction(Request $request, $id){
        $restService = $this->get('campaignchain.channel.mailchimp.rest.client');
        $restService->connectByNewsletterId($id);
        $newsletterPreview = $restService->getNewsletterPreview($id);

        return $this->render(
            'CampaignChainActivityMailChimpBundle::preview.html.twig',
            array(
                'content' => $newsletterPreview,
            ));
    }

    public function getNewsletter($operation)
    {
        // Get the newsletter details.
        $newsletter = $this->getDoctrine()
            ->getRepository('CampaignChainOperationMailChimpBundle:MailChimpNewsletter')
            ->findOneByOperation($operation);

        if (!$newsletter) {
            throw new \Exception(
                'No newsletter found for Operation with ID '.$operation->getId()
            );
        }

        return $newsletter;
    }
}
