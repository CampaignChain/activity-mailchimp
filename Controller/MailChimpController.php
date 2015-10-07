<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Activity\MailChimpBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class MailChimpController extends Controller
{
//    const ACTIVITY_BUNDLE_NAME          = 'campaignchain/activity-mailchimp';
//    const ACTIVITY_MODULE_IDENTIFIER    = 'campaignchain-mailchimp';
//    const OPERATION_BUNDLE_NAME         = 'campaignchain/operation-mailchimp';
//    const OPERATION_MODULE_IDENTIFIER   = 'campaignchain-mailchimp-newsletter';
//    const LOCATION_BUNDLE_NAME          = 'campaignchain/location-mailchimp';
//    const LOCATION_MODULE_IDENTIFIER    = 'campaignchain-mailchimp-newsletter';
//    const TRIGGER_HOOK_IDENTIFIER       = 'campaignchain-due';
//    const LINK_ADMIN_CAMPAIGNS          = 'https://admin.mailchimp.com/campaigns/';
//    const MAILCHIMP_DATETIME_FORMAT     = 'Y-m-d H:i:s';

//    public function newAction(Request $request)
//    {
//        $wizard = $this->get('campaignchain.core.activity.wizard');
//        $campaign = $wizard->getCampaign();
//        $activity = $wizard->getActivity();
//        $location = $wizard->getLocation();
//
//        $activity->setEqualsOperation(true);
//
//        // Retrieve upcoming newsletter campaigns from MailChimp.
//        $restService = $this->get('campaignchain.channel.mailchimp.rest.client');
//        $client = $restService->connectByLocation($location);
//        $upcomingNewsletters = $client->campaigns->getList(array(
//            'status' => 'save,paused,schedule',
//        ));
//
//        if($upcomingNewsletters['total'] == 0){
//            $this->get('session')->getFlashBag()->add(
//                'warning',
//                'No upcoming newsletter campaigns available.'
//            );
//
//            return $this->redirect(
//                $this->generateUrl('campaignchain_core_activities_new')
//            );
//        }
//
//        $locationService = $this->get('campaignchain.core.location');
//
//        $newsletters = array();
//
//        foreach($upcomingNewsletters['data'] as $key => $upcomingNewsletter){
//            // Check if newsletter has already been added to this Campaign.
//            if(!$locationService->existsInAllCampaigns(
//                self::LOCATION_BUNDLE_NAME, self::LOCATION_MODULE_IDENTIFIER,
//                $upcomingNewsletter['id']
//            )){
//                // TODO: If send_time not empty, then pass to due hook.
//                $newsletters[$key] = $upcomingNewsletter['title']
//                    .' ('.$upcomingNewsletter['subject'].')';
//            }
//        }
//
//        if(!count($newsletters)){
//            $this->get('session')->getFlashBag()->add(
//                'warning',
//                'All upcoming newsletters have already been added to campaigns.'
//            );
//
//            return $this->redirect(
//                $this->generateUrl('campaignchain_core_activities_new')
//            );
//        }
//
//        $activityType = $this->get('campaignchain.core.form.type.activity');
//        $activityType->setBundleName(self::ACTIVITY_BUNDLE_NAME);
//        $activityType->setModuleIdentifier(self::ACTIVITY_MODULE_IDENTIFIER);
//        $activityType->showNameField(false);
//
//        $operationType = new MailChimpOperationType($this->getDoctrine()->getManager(), $this->get('service_container'));
//
//        $location = $locationService->getLocation($location->getId());
//
//        // Have the location point to the list of newsletters in the
//        // MailChimp website's admin area.
//        $location->setUrl(self::LINK_ADMIN_CAMPAIGNS);
//
//        $operationType->setLocation($location);
//
//        $operationType->setNewsletters($newsletters);
//
//        $operationForms[] = array(
//            'identifier' => self::OPERATION_MODULE_IDENTIFIER,
//            'form' => $operationType,
//            'label' => 'Add Newsletter',
//        );
//        $activityType->setOperationForms($operationForms);
//        $activityType->setCampaign($campaign);
//
//        $form = $this->createForm($activityType, $activity);
//
//        $form->handleRequest($request);
//
//        if ($form->isValid()) {
//            $newsletterKey = $form->get(self::OPERATION_MODULE_IDENTIFIER)->getData()['newsletter'];
//            $newsletter = $upcomingNewsletters['data'][$newsletterKey];
//
//            $activity = $wizard->end();
//
//            $activity->setName($newsletter['title']);
//
//            // Get the operation module.
//            $operationService = $this->get('campaignchain.core.operation');
//            $operationModule = $operationService->getOperationModule(self::OPERATION_BUNDLE_NAME, self::OPERATION_MODULE_IDENTIFIER);
//
//            // The activity equals the operation. Thus, we create a new operation with the same data.
//            $operation = new Operation();
//            $operation->setName($newsletter['title']);
//            $operation->setActivity($activity);
//            $activity->addOperation($operation);
//            $operationModule->addOperation($operation);
//            $operation->setOperationModule($operationModule);
//
//            // The Operation creates a Location, i.e. the newsletter
//            // will be accessible through an archive URL after publishing.
//            // Get the location module for the user stream.
//            $locationService = $this->get('campaignchain.core.location');
//            $locationModule = $locationService->getLocationModule(
//                self::LOCATION_BUNDLE_NAME,
//                self::LOCATION_MODULE_IDENTIFIER
//            );
//
//            $location = new Location();
//            $location->setLocationModule($locationModule);
//            $location->setParent($activity->getLocation());
//            $location->setIdentifier($newsletter['id']);
//            $location->setName($newsletter['title']);
//            $location->setUrl($newsletter['archive_url_long']);
//            $location->setStatus(Medium::STATUS_UNPUBLISHED);
//            $location->setOperation($operation);
//            $operation->addLocation($location);
//
//            // Store newsletter details.
//            $newsletterOperation = new MailChimpNewsletter();
//            $newsletterOperation->setOperation($operation);
//            $newsletterOperation->setCampaignId($newsletter['id']);
//            $newsletterOperation->setWebId($newsletter['web_id']);
//            $newsletterOperation->setListId($newsletter['list_id']);
//            $newsletterOperation->setFolderId($newsletter['folder_id']);
//            $newsletterOperation->setTemplateId($newsletter['template_id']);
//            $newsletterOperation->setContentType($newsletter['content_type']);
//            $newsletterOperation->setTitle($newsletter['title']);
//            $newsletterOperation->setType($newsletter['type']);
//            $newsletterOperation->setCreateTime(new \DateTime($newsletter['create_time']));
//            if(isset($newsletter['send_time']) && strlen($newsletter['send_time'])){
//                $newsletterOperation->setSendTime(new \DateTime($newsletter['send_time']));
//            }
//            if(isset($newsletter['content_updated_time']) && strlen($newsletter['content_updated_time'])){
//                $newsletterOperation->setContentUpdatedTime(new \DateTime($newsletter['content_updated_time']));
//            }
//            $newsletterOperation->setStatus($newsletter['status']);
//            $newsletterOperation->setFromName($newsletter['from_name']);
//            $newsletterOperation->setFromEmail($newsletter['from_email']);
//            $newsletterOperation->setSubject($newsletter['subject']);
//            $newsletterOperation->setArchiveUrl($newsletter['archive_url']);
//            $newsletterOperation->setArchiveUrlLong($newsletter['archive_url_long']);
//            $newsletterOperation->setTrackingHtmlClicks($newsletter['tracking']['html_clicks']);
//            $newsletterOperation->setTrackingTextClicks($newsletter['tracking']['text_clicks']);
//            $newsletterOperation->setTrackingOpens($newsletter['tracking']['opens']);
//
//
//            $repository = $this->getDoctrine()->getManager();
//
//            // Make sure that data stays intact by using transactions.
//            try {
//                $repository->getConnection()->beginTransaction();
//
//                $repository->persist($operation);
//                $repository->persist($activity);
//                $repository->persist($newsletterOperation);
//
//                $repository->flush();
//
//                $hookService = $this->get('campaignchain.core.hook');
//                $activity = $hookService->processHooks(self::ACTIVITY_BUNDLE_NAME, self::ACTIVITY_MODULE_IDENTIFIER, $activity, $form, true);
//                $repository->flush();
//
//                // Set send time per Activity's start time
//                $newsletterOperation->setSendTime($activity->getStartDate());
//                $repository->flush();
//
//                // Unschedule the newsletter on MailChimp if it was scheduled there,
//                // to let CampaignChain handle the scheduling.
//                if($newsletter['status'] == 'schedule') {
//                    $client->campaigns->unschedule(
//                        $newsletterOperation->getCampaignId()
//                    );
//                }
//
//                $repository->getConnection()->commit();
//            } catch (\Exception $e) {
//                $repository->getConnection()->rollback();
//                throw $e;
//            }
//
//            $this->get('session')->getFlashBag()->add(
//                'success',
//                'The newsletter <a href="'.$this->generateUrl('campaignchain_core_activity_edit', array('id' => $activity->getId())).'">'.$activity->getName().'</a> has been added successfully.'
//            );
//
//            // Status Update to be sent immediately?
//            // TODO: This is an intermediary hardcoded hack and should be instead handled by the scheduler.
//            if ($form->get('campaignchain_hook_campaignchain_due')->has('execution_choice') && $form->get('campaignchain_hook_campaignchain_due')->get('execution_choice')->getData() == 'now') {
//                $job = $this->get('campaignchain.job.operation.mailchimp.newsletter');
//                $job->execute($operation->getId());
//                // TODO: Add different flashbag which includes link to posted message on Facebook
//            }
//
//            return $this->redirect($this->generateUrl('campaignchain_core_activities'));
//        }
//
//        $campaignService = $this->get('campaignchain.core.campaign');
//        $campaign = $campaignService->getCampaign($campaign);
//
//        return $this->render(
//            'CampaignChainCoreBundle:Operation:new.html.twig',
//            array(
//                'page_title' => 'Add a Newsletter',
//                'activity' => $activity,
//                'campaign' => $campaign,
//                'campaign_module' => $campaign->getCampaignModule(),
//                'channel_module' => $wizard->getChannelModule(),
//                'channel_module_bundle' => $wizard->getChannelModuleBundle(),
//                'location' => $location,
//                'form' => $form->createView(),
//                'form_submit_label' => 'Save',
//                'form_cancel_route' => 'campaignchain_core_activities_new'
//            ));
//
//    }
//
//    public function editAction(Request $request, $id)
//    {
//        $activityService = $this->get('campaignchain.core.activity');
//        $activity = $activityService->getActivity($id);
//        $campaign = $activity->getCampaign();
//
//        // Get the one operation.
//        $operation = $activityService->getOperation($id);
//
//        // Get the newsletter details.
//        $localNewsletter = $this->getNewsletter($operation);
//
//        // Retrieve up-to-date newsletter data from MailChimp
//        $restService = $this->get('campaignchain.channel.mailchimp.rest.client');
//        $client = $restService->connectByActivity($activity);
//        $remoteNewsletterData = $client->campaigns->getList(array(
//            'campaign_id' => $localNewsletter->getCampaignId(),
//        ));
//
//        $remoteNewsletter = $remoteNewsletterData['data'][0];
//
//        // If content update times of remote and local newsletter diverge, then
//        // the content differs.
//        if(
//            new \DateTime($remoteNewsletter['content_updated_time'])
//            != $localNewsletter->getContentUpdatedTime()
//            ||
//            $localNewsletter->getContentUpdatedTime() == null
//            ||
//            strlen($remoteNewsletter['content_updated_time']) == 0
//        ){
//            $contentDiff = '';
//
//            // 1. Check newsletter's scheduled send time aka due date.
//            $remoteNewsletterSendTime = new \DateTime($remoteNewsletter['send_time']);
//
//            // 1.1 Different send times.
//            if($remoteNewsletterSendTime != $localNewsletter->getSendTime()){
//                $dueDiff =
//                    '<p><strike>'.$localNewsletter->getSendTime()->format(self::MAILCHIMP_DATETIME_FORMAT).'</strike></p>'
//                    .'<p>'.$remoteNewsletter['send_time'].'</p>';
//
//                // Check whether new send_time is within campaign duration.
//                $datetimeUtil = $this->get('campaignchain.core.util.datetime');
//                if($datetimeUtil->isWithinDuration(
//                    $campaign->getStartDate(),
//                    $remoteNewsletterSendTime,
//                    $campaign->getEndDate()
//                )){
//                    $localNewsletterSendTime = $remoteNewsletterSendTime;
//                    $contentDiff .=
//                        '<h4>Due</h4>'.$dueDiff;
//                } else {
//                    // Modified send_time is not within campaign duration.
//                    $localNewsletterSendTime = null;
//
//                    // Set activity status to paused.
//                    $activity->setStatus(Action::STATUS_INTERACTION_REQUIRED);
//
//                    $this->get('session')->getFlashBag()->add(
//                        'warning',
//                        '<p>Due date has been modified remotely on MailChimp and is not within the campaign duration.</p>'
//                        .$dueDiff
//                        .'<p>This activity was paused.</p>'
//                        .'<p>Please define a new due date to reactivate it.</p>'
//                    );
//                }
//
//                $localNewsletter->setSendTime($localNewsletterSendTime);
//                $activity->setStartDate($localNewsletterSendTime);
//                $operation->setStartDate($localNewsletterSendTime);
//            }
//
//            // 1.2 Local send time is null, which means changed data got
//            // updated before, but no due date provided by user.
//
//            // Check newsletter title.
//            if($remoteNewsletter['title'] != $localNewsletter->getTitle()){
//                $contentDiff .=
//                    '<h4>Title</h4>'
//                    .'<p><strike>'.$localNewsletter->getTitle().'</strike></p>'
//                    .'<p>'.$remoteNewsletter['title'].'</p>';
//                $localNewsletter->setTitle($remoteNewsletter['title']);
//                $activity->setName($remoteNewsletter['title']);
//                $operation->setName($remoteNewsletter['title']);
//            }
//
//            // Check subject.
//            if($remoteNewsletter['subject'] != $localNewsletter->getSubject()){
//                $contentDiff .=
//                    '<h4>Subject</h4>'
//                    .'<p><strike>'.$localNewsletter->getSubject().'</strike></p>'
//                    .'<p>'.$remoteNewsletter['subject'].'</p>';
//                $localNewsletter->setSubject($remoteNewsletter['subject']);
//            }
//
//            // Check from_name.
//            if($remoteNewsletter['from_name'] != $localNewsletter->getFromName()){
//                $contentDiff .=
//                    '<h4>From Name</h4>'
//                    .'<p><strike>'.$localNewsletter->getFromName().'</strike></p>'
//                    .'<p>'.$remoteNewsletter['from_name'].'</p>';
//                $localNewsletter->setFromName($remoteNewsletter['from_name']);
//            }
//
//            // Check from_email.
//            if($remoteNewsletter['from_email'] != $localNewsletter->getFromEmail()){
//                $contentDiff .=
//                    '<h4>From Email</h4>'
//                    .'<p><strike>'.$localNewsletter->getFromEmail().'</strike></p>'
//                    .'<p>'.$remoteNewsletter['from_email'].'</p>';
//                $localNewsletter->setFromEmail($remoteNewsletter['from_email']);
//            }
//
//            if(strlen($contentDiff)){
//                // Update CampaignChain to reflect remote changes.
//                $localNewsletter->setContentUpdatedTime(new \DateTime($remoteNewsletter['content_updated_time']));
//                $localNewsletter->setStatus($remoteNewsletter['status']);
//
//                $repository = $this->getDoctrine()->getManager();
//                $repository->persist($localNewsletter);
//                $repository->persist($operation);
//                $repository->persist($activity);
//                $repository->flush();
//
//                $this->get('session')->getFlashBag()->add(
//                    'info',
//                    '<p>The following newsletter data has been edited remotely on MailChimp. These changes have just been updated in CampaignChain.</p>'.$contentDiff
//                );
//            }
//        }
//
//        $activityType = $this->get('campaignchain.core.form.type.activity');
//        $activityType->setBundleName(self::ACTIVITY_BUNDLE_NAME);
//        $activityType->setModuleIdentifier(self::ACTIVITY_MODULE_IDENTIFIER);
//        $activityType->showNameField(false);
//        $activityType->setCampaign($campaign);
//
//        $form = $this->createForm($activityType, $activity);
//
//        $form->handleRequest($request);
//
//        // TODO: Check if newsletter schedule dates were edited on MailChimp.
//
//        if ($form->isValid()) {
//            $repository = $this->getDoctrine()->getManager();
//
//            // Make sure that data stays intact by using transactions.
//            try {
//                $repository->getConnection()->beginTransaction();
//
//                // The activity equals the operation. Thus, we update the operation with the same data.
//                $activityService = $this->get('campaignchain.core.activity');
//                $operation = $activityService->getOperation($id);
//                $operation->setName($activity->getName());
//
//                $hookService = $this->get('campaignchain.core.hook');
//                $activity = $hookService->processHooks(self::ACTIVITY_BUNDLE_NAME, self::ACTIVITY_MODULE_IDENTIFIER, $activity, $form);
//
//                // Update local newsletter meta data
//                $localNewsletter->setSendTime($activity->getStartDate());
//
//                // Unschedule the newsletter on MailChimp if it was scheduled there,
//                // to let CampaignChain handle the scheduling.
//                if($remoteNewsletter['status'] == 'schedule') {
//                    $client->campaigns->unschedule(
//                        $localNewsletter->getCampaignId()
//                    );
//                }
//
//                $repository->flush();
//
//                $this->get('session')->getFlashBag()->add(
//                    'success',
//                    'Your Facebook activity <a href="'.$this->generateUrl('campaignchain_core_activity_edit', array('id' => $activity->getId())).'">'.$activity->getName().'</a> was edited successfully.'
//                );
//
//                // Status Update to be sent immediately?
//                // TODO: This is an intermediary hardcoded hack and should be instead handled by the scheduler.
//                if ($form->get('campaignchain_hook_campaignchain_due')->has('execution_choice') && $form->get('campaignchain_hook_campaignchain_due')->get('execution_choice')->getData() == 'now') {
//                    $job = $this->get('campaignchain.job.operation.mailchimp.newsletter');
//                    $job->execute($operation->getId());
//                    // TODO: Add different flashbag which includes link to posted message on Facebook
//                }
//
//                $repository->getConnection()->commit();
//
//                return $this->redirect($this->generateUrl('campaignchain_core_activities'));
//            } catch (\Exception $e) {
//                $repository->getConnection()->rollback();
//                throw $e;
//            }
//        }
//
//        return $this->render(
//            'CampaignChainOperationMailChimpBundle::edit.html.twig',
//            array(
//                'page_title' => $activity->getName(),
//                'page_secondary_title' => $localNewsletter->getSubject(),
//                'activity' => $activity,
//                'newsletter' => $localNewsletter,
//                'form' => $form->createView(),
//                'form_submit_label' => 'Save',
//                'form_cancel_route' => 'campaignchain_core_activities'
//            ));
//    }
//
//    public function readAction(Request $request, $id){
//        $activityService = $this->get('campaignchain.core.activity');
//        $activity = $activityService->getActivity($id);
//
//        // Get the one operation.
//        $operation = $activityService->getOperation($id);
//
//        // Get the newsletter details.
//        $newsletter = $this->getNewsletter($operation);
//
//        // TODO: Check if newsletter schedule dates were edited on MailChimp.
//
//        return $this->render(
//            'CampaignChainOperationMailChimpBundle::read.html.twig',
//            array(
//                'page_title' => $activity->getName(),
//                'operation' => $operation,
//                'activity' => $activity,
//                'newsletter' => $newsletter,
//                'show_date' => true,
//            ));
//    }
//
//    public function editModalAction(Request $request, $id)
//    {
//        $activityService = $this->get('campaignchain.core.activity');
//        $activity = $activityService->getActivity($id);
//        $campaign = $activity->getCampaign();
//
//        // Get the one operation.
//        $operation = $activityService->getOperation($id);
//
//        // Get the newsletter details.
//        $newsletter = $this->getNewsletter($operation);
//
//        $activityType = $this->get('campaignchain.core.form.type.activity');
//        $activityType->setBundleName(self::ACTIVITY_BUNDLE_NAME);
//        $activityType->setModuleIdentifier(self::ACTIVITY_MODULE_IDENTIFIER);
//        $activityType->showNameField(false);
//        $activityType->setCampaign($campaign);
//
//        $form = $this->createForm($activityType, $activity);
//
//        return $this->render(
//            'CampaignChainOperationMailChimpBundle::edit_modal.html.twig',
//            array(
//                'page_title' => $activity->getName(),
//                'operation' => $operation,
//                'activity' => $activity,
//                'newsletter' => $newsletter,
//                'show_date' => true,
//                'form' => $form->createView(),
//            ));
//    }
//
//    public function editApiAction(Request $request, $id)
//    {
//        $responseData = array();
//
//        $data = $request->get('campaignchain_core_activity');
//
//        //$responseData['payload'] = $data;
//
//        $activityService = $this->get('campaignchain.core.activity');
//        $activity = $activityService->getActivity($id);
//
//        $hookService = $this->get('campaignchain.core.hook');
//        $activity = $hookService->processHooks(self::ACTIVITY_BUNDLE_NAME, self::ACTIVITY_MODULE_IDENTIFIER, $activity, $data);
//
//        $repository = $this->getDoctrine()->getManager();
//        $repository->persist($activity);
//        $repository->flush();
//
//        $responseData['start_date'] =
//        $responseData['end_date'] =
//            $activity->getStartDate()->format(\DateTime::ISO8601);
//
//        $encoders = array(new JsonEncoder());
//        $normalizers = array(new GetSetMethodNormalizer());
//        $serializer = new Serializer($normalizers, $encoders);
//
//        $response = new Response($serializer->serialize($responseData, 'json'));
//        return $response->setStatusCode(Response::HTTP_OK);
//    }

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

//    public function getNewsletter($operation)
//    {
//        // Get the newsletter details.
//        $newsletter = $this->getDoctrine()
//            ->getRepository('CampaignChainOperationMailChimpBundle:MailChimpNewsletter')
//            ->findOneByOperation($operation);
//
//        if (!$newsletter) {
//            throw new \Exception(
//                'No newsletter found for Operation with ID '.$operation->getId()
//            );
//        }
//
//        return $newsletter;
//    }
//
//    public function readModalAction(Request $request, $id)
//    {
//        $activityService = $this->get('campaignchain.core.activity');
//        $activity = $activityService->getActivity($id);
//
//        // Get the one operation.
//        $operation = $activityService->getOperation($id);
//
//        // Get the newsletter details.
//        $newsletter = $this->getNewsletter($operation);
//
//        // TODO: Check if newsletter schedule dates were edited on MailChimp.
//
//        return $this->render(
//            'CampaignChainOperationMailChimpBundle::read_modal.html.twig',
//            array(
//                'page_title' => $activity->getName(),
//                'operation' => $operation,
//                'activity' => $activity,
//                'newsletter' => $newsletter,
//                'show_date' => true,
//            ));
//    }
}
