<?php
/*
 * Copyright 2016 CampaignChain, Inc. <info@campaignchain.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CampaignChain\Activity\MailChimpBundle\Controller;

use CampaignChain\Channel\MailChimpBundle\REST\MailChimpClient;
use CampaignChain\CoreBundle\Controller\Module\AbstractActivityHandler;
use CampaignChain\CoreBundle\EntityService\LocationService;
use CampaignChain\CoreBundle\Util\DateTimeUtil;
use CampaignChain\Operation\MailChimpBundle\Job\SendNewsletter;
use CampaignChain\CoreBundle\Entity\Campaign;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Session\Session;
use CampaignChain\CoreBundle\Entity\Operation;
use CampaignChain\CoreBundle\Entity\Activity;
use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\Operation\MailChimpBundle\Entity\MailChimpNewsletter;
use CampaignChain\Operation\MailChimpBundle\EntityService\MailChimpNewsletter as NewsletterService;

class ScheduleHandler extends AbstractActivityHandler
{
    const LOCATION_BUNDLE_NAME          = 'campaignchain/location-mailchimp';
    const LOCATION_MODULE_IDENTIFIER    = 'campaignchain-mailchimp-newsletter';
    const LINK_ADMIN_CAMPAIGNS          = 'https://admin.mailchimp.com/campaigns/';
    const MAILCHIMP_DATETIME_FORMAT     = 'Y-m-d H:i:s';

    protected $em;
    protected $router;
    protected $contentService;
    protected $locationService;
    protected $restClient;
    protected $job;
    protected $datetimeUtil;
    protected $session;
    protected $templating;
    protected $upcomingNewsletters;
    private     $remoteNewsletter;
    private     $restApiConnection;

    public function __construct(
        EntityManager $em,
        NewsletterService $contentService,
        LocationService $locationService,
        MailChimpClient $restClient,
        SendNewsletter $job,
        DateTimeUtil $datetimeUtil,
        $session,
        TwigEngine $templating,
        Router $router
    )
    {
        $this->em = $em;
        $this->contentService   = $contentService;
        $this->locationService  = $locationService;
        $this->restClient       = $restClient;
        $this->job              = $job;
        $this->datetimeUtil     = $datetimeUtil;
        $this->session          = $session;
        $this->templating       = $templating;
        $this->router           = $router;
    }

    public function createContent(Location $location = null, Campaign $campaign = null)
    {
        // Retrieve upcoming newsletter campaigns from MailChimp.
        $client = $this->getRestApiConnectionByLocation($location);
        $this->upcomingNewsletters = $client->campaigns->getList(array(
            'status' => 'save,paused,schedule',
        ));

        if($this->upcomingNewsletters['total'] == 0){
            $this->session->getFlashBag()->add(
                'warning',
                'No upcoming newsletter campaigns available.'
            );

            header('Location: '.$this->router->generate('campaignchain_core_activities_new'));
            exit;
        }

        $newsletters = array();

        foreach($this->upcomingNewsletters['data'] as $key => $upcomingNewsletter){
            // Check if newsletter has already been added to this Campaign.
            if(!$this->locationService->existsInAllCampaigns(
                self::LOCATION_BUNDLE_NAME, self::LOCATION_MODULE_IDENTIFIER,
                $upcomingNewsletter['id']
            )){
                // TODO: If send_time not empty, then pass to due hook.
                $newsletters[$key] = $upcomingNewsletter['title']
                    .' ('.$upcomingNewsletter['subject'].')';
            }
        }

        if(!count($newsletters)){
            $this->session->getFlashBag()->add(
                'warning',
                'All upcoming newsletters have already been added to campaigns.'
            );

            header('Location: '.$this->router->generate('campaignchain_core_activities_new'));
            exit;
        }

        return $newsletters;
    }

    public function getContent(Location $location, Operation $operation = null)
    {
        return $this->contentService->getNewsletterByOperation($operation);
    }

    public function processContent(Operation $operation, $data)
    {
        $remoteNewsletter = $this->getRemoteNewsletter($data);

        // Store newsletter details.
        $newsletterOperation = new MailChimpNewsletter();
        $newsletterOperation->setOperation($operation);
        $newsletterOperation->setCampaignId($remoteNewsletter['id']);
        $newsletterOperation->setWebId($remoteNewsletter['web_id']);
        $newsletterOperation->setListId($remoteNewsletter['list_id']);
        $newsletterOperation->setFolderId($remoteNewsletter['folder_id']);
        $newsletterOperation->setTemplateId($remoteNewsletter['template_id']);
        $newsletterOperation->setContentType($remoteNewsletter['content_type']);
        $newsletterOperation->setTitle($remoteNewsletter['title']);
        $newsletterOperation->setType($remoteNewsletter['type']);
        $newsletterOperation->setCreateTime(new \DateTime($remoteNewsletter['create_time']));
        if(isset($remoteNewsletter['send_time']) && strlen($remoteNewsletter['send_time'])){
            $newsletterOperation->setSendTime(new \DateTime($remoteNewsletter['send_time']));
        }
        if(isset($remoteNewsletter['content_updated_time']) && strlen($remoteNewsletter['content_updated_time'])){
            $newsletterOperation->setContentUpdatedTime(new \DateTime($remoteNewsletter['content_updated_time']));
        }
        $newsletterOperation->setStatus($remoteNewsletter['status']);
        $newsletterOperation->setFromName($remoteNewsletter['from_name']);
        $newsletterOperation->setFromEmail($remoteNewsletter['from_email']);
        $newsletterOperation->setSubject($remoteNewsletter['subject']);
        $newsletterOperation->setArchiveUrl($remoteNewsletter['archive_url']);
        $newsletterOperation->setArchiveUrlLong($remoteNewsletter['archive_url_long']);
        $newsletterOperation->setTrackingHtmlClicks($remoteNewsletter['tracking']['html_clicks']);
        $newsletterOperation->setTrackingTextClicks($remoteNewsletter['tracking']['text_clicks']);
        $newsletterOperation->setTrackingOpens($remoteNewsletter['tracking']['opens']);

        return $newsletterOperation;
    }

    public function readAction(Operation $operation)
    {
        // Get the newsletter details.
        $newsletter = $this->contentService->getNewsletterByOperation($operation);

        // TODO: Check if newsletter schedule dates were edited on MailChimp.

        return $this->templating->renderResponse(
            'CampaignChainOperationMailChimpBundle::read.html.twig',
            array(
                'page_title' => $operation->getActivity()->getName(),
                'operation' => $operation,
                'activity' => $operation->getActivity(),
                'newsletter' => $newsletter,
                'show_date' => true,
            ));
    }

    public function processActivity(Activity $activity, $data)
    {
        $remoteNewsletter = $this->getRemoteNewsletter($data);

        $activity->setName($remoteNewsletter['title']);

        return $activity;
    }

    public function processContentLocation(Location $location, $data)
    {
        $remoteNewsletter = $this->getRemoteNewsletter($data);
        $location->setIdentifier($remoteNewsletter['id']);
        $location->setName($remoteNewsletter['title']);
        $location->setUrl($remoteNewsletter['archive_url_long']);

        return $location;
    }

    public function processActivityLocation(Location $location)
    {
        $location->setUrl(self::LINK_ADMIN_CAMPAIGNS);

        return $location;
    }

    public function postPersistNewEvent(Operation $operation, Form $form, $content = null)
    {
        // Content to be published immediately?
        $this->publishNow($operation, $form, $content);

        // Set send time per Activity's start time
        $content->setSendTime($operation->getActivity()->getStartDate());
        $this->em->persist($content);
        $this->em->flush();
    }

    public function postPersistEditEvent(Operation $operation, Form $form, $content = null)
    {
        $content = $this->contentService->getNewsletterByOperation($operation);

        // Content to be published immediately?
        $this->publishNow($operation, $form, $content);
    }

    public function preFormSubmitEditEvent(Operation $operation)
    {
        $activity = $operation->getActivity();
        $campaign = $activity->getCampaign();

        // Get the newsletter details.
        $localNewsletter = $this->contentService->getNewsletterByOperation($operation);

        // Retrieve up-to-date newsletter data from MailChimp
        $client = $this->getRestApiConnectionByOperation($operation);
        $remoteNewsletterData = $client->campaigns->getList(array(
            'campaign_id' => $localNewsletter->getCampaignId(),
        ));

        $remoteNewsletter = $remoteNewsletterData['data'][0];

        // If content update times of remote and local newsletter diverge, then
        // the content differs.
        if(
            new \DateTime($remoteNewsletter['content_updated_time'])
            != $localNewsletter->getContentUpdatedTime()
            ||
            $localNewsletter->getContentUpdatedTime() == null
            ||
            strlen($remoteNewsletter['content_updated_time']) == 0
        ){
            $contentDiff = '';

            // 1. Check newsletter's scheduled send time aka due date.
            $remoteNewsletterSendTime = new \DateTime($remoteNewsletter['send_time']);

            // 1.1 Different send times.
            if($remoteNewsletterSendTime != $localNewsletter->getSendTime()){
                $dueDiff =
                    '<p><strike>'.$localNewsletter->getSendTime()->format(self::MAILCHIMP_DATETIME_FORMAT).'</strike></p>'
                    .'<p>'.$remoteNewsletter['send_time'].'</p>';

                // Check whether new send_time is within campaign duration.
                if($this->datetimeUtil->isWithinDuration(
                    $campaign->getStartDate(),
                    $remoteNewsletterSendTime,
                    $campaign->getEndDate()
                )){
                    $localNewsletterSendTime = $remoteNewsletterSendTime;
                    $contentDiff .=
                        '<h4>Due</h4>'.$dueDiff;
                } else {
                    // Modified send_time is not within campaign duration.
                    $localNewsletterSendTime = null;

                    // Set activity status to paused.
                    $activity->setStatus(Action::STATUS_INTERACTION_REQUIRED);

                    $this->session->getFlashBag()->add(
                        'warning',
                        '<p>Due date has been modified remotely on MailChimp and is not within the campaign duration.</p>'
                        .$dueDiff
                        .'<p>This activity was paused.</p>'
                        .'<p>Please define a new due date to reactivate it.</p>'
                    );
                }

                $localNewsletter->setSendTime($localNewsletterSendTime);
                $activity->setStartDate($localNewsletterSendTime);
                $operation->setStartDate($localNewsletterSendTime);
            }

            // 1.2 Local send time is null, which means changed data got
            // updated before, but no due date provided by user.

            // Check newsletter title.
            if($remoteNewsletter['title'] != $localNewsletter->getTitle()){
                $contentDiff .=
                    '<h4>Title</h4>'
                    .'<p><strike>'.$localNewsletter->getTitle().'</strike></p>'
                    .'<p>'.$remoteNewsletter['title'].'</p>';
                $localNewsletter->setTitle($remoteNewsletter['title']);
                $activity->setName($remoteNewsletter['title']);
                $operation->setName($remoteNewsletter['title']);
            }

            // Check subject.
            if($remoteNewsletter['subject'] != $localNewsletter->getSubject()){
                $contentDiff .=
                    '<h4>Subject</h4>'
                    .'<p><strike>'.$localNewsletter->getSubject().'</strike></p>'
                    .'<p>'.$remoteNewsletter['subject'].'</p>';
                $localNewsletter->setSubject($remoteNewsletter['subject']);
            }

            // Check from_name.
            if($remoteNewsletter['from_name'] != $localNewsletter->getFromName()){
                $contentDiff .=
                    '<h4>From Name</h4>'
                    .'<p><strike>'.$localNewsletter->getFromName().'</strike></p>'
                    .'<p>'.$remoteNewsletter['from_name'].'</p>';
                $localNewsletter->setFromName($remoteNewsletter['from_name']);
            }

            // Check from_email.
            if($remoteNewsletter['from_email'] != $localNewsletter->getFromEmail()){
                $contentDiff .=
                    '<h4>From Email</h4>'
                    .'<p><strike>'.$localNewsletter->getFromEmail().'</strike></p>'
                    .'<p>'.$remoteNewsletter['from_email'].'</p>';
                $localNewsletter->setFromEmail($remoteNewsletter['from_email']);
            }

            if(strlen($contentDiff)){
                // Update CampaignChain to reflect remote changes.
                $localNewsletter->setContentUpdatedTime(new \DateTime($remoteNewsletter['content_updated_time']));
                $localNewsletter->setStatus($remoteNewsletter['status']);

                $this->em->persist($localNewsletter);
                $this->em->persist($operation);
                $this->em->persist($activity);
                $this->em->flush();

                $this->session->getFlashBag()->add(
                    'info',
                    '<p>The following newsletter data has been edited remotely on MailChimp. These changes have just been updated in CampaignChain.</p>'.$contentDiff
                );
            }
        }
    }

    public function preFormSubmitEditModalEvent(Operation $operation)
    {
        $this->makeRemoteSlideshowEmbeddable($operation);
    }

    public function getEditRenderOptions(Operation $operation)
    {
        $localNewsletter = $this->contentService->getNewsletterByOperation($operation);

        return array(
            'template' => 'CampaignChainOperationMailChimpBundle::edit.html.twig',
            'vars' => array(
                'page_secondary_title' => $localNewsletter->getSubject(),
                'newsletter' => $localNewsletter,
            )
        );
    }

    public function getEditModalRenderOptions(Operation $operation)
    {
        $newsletter = $this->contentService->getNewsletterByOperation($operation);

        return array(
            'template' => 'CampaignChainOperationMailChimpBundle::edit_modal.html.twig',
            'vars' => array(
                'newsletter' => $newsletter,
                'show_date' => true,
            )
        );
    }

    private function getRestApiConnectionByOperation(Operation $operation)
    {
        if(!$this->restApiConnection){
            $this->restApiConnection = $this->restClient->connectByActivity(
                $operation->getActivity()
            );
        }

        return $this->restApiConnection;
    }

    private function getRestApiConnectionByLocation(Location $location)
    {
        if(!$this->restApiConnection){
            $this->restApiConnection =
                $this->restClient->connectByLocation($location);
        }

        return $this->restApiConnection;
    }

    public function hasContent($view)
    {
        if($view != 'new'){
            return false;
        }

        return true;
    }

    private function publishNow(Operation $operation, Form $form, $content)
    {
        if ($form->get('campaignchain_hook_campaignchain_due')->has('execution_choice') && $form->get('campaignchain_hook_campaignchain_due')->get('execution_choice')->getData() == 'now') {
            $this->job->execute($operation->getId());
            $content = $this->contentService->getNewsletterByOperation($operation);
            $this->session->getFlashBag()->add(
                'success',
                'The newsletter was published. <a href="'.$content->getArchiveUrl().'">View it on MailChimp</a>.'
            );

            return true;
        } else {
            // Unschedule the newsletter on MailChimp if it was scheduled there,
            // to let CampaignChain handle the scheduling.
            $remoteNewsletter = $this->getRemoteNewsletter();
            if ($remoteNewsletter['status'] == 'schedule') {
                $client = $this->getRestApiConnectionByOperation($operation);
                $client->campaigns->unschedule(
                    $content->getCampaignId()
                );
            }
        }

        return false;
    }

    private function getRemoteNewsletter($data = null)
    {
        if(!$this->remoteNewsletter) {
            $newsletterKey = $data['newsletter'];
            $this->remoteNewsletter = $this->upcomingNewsletters['data'][$newsletterKey];
        }

        return $this->remoteNewsletter;
    }
}