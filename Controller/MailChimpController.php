<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Activity\MailChimpBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class MailChimpController extends Controller
{
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
}
