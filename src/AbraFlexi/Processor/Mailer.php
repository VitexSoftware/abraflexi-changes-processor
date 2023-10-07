<?php

/**
 * System.Spoje.Net - Mailer class
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2018 Spoje.Net, 2022 VitexSoftware
 */

namespace AbraFlexi\Processor;

/**
 * Description of Mailer
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Mailer extends \Ease\HtmlMailer
{
    /**
     * Send Mail Message
     *
     * @param string $emailAddress
     * @param string $mailSubject
     * @param string $emailContents
     */
    public function __construct(
        $emailAddress,
        $mailSubject,
        $emailContents = null
    ) {
        if (\Ease\Functions::cfg('SUPPRESS_EMAILS') == 'true') {
            $emailContents = "OriginalTO: " . $emailAddress . "\n\n " . $emailContents;
            $emailAddress = \Ease\Functions::cfg('EASE_EMAILTO');
        }
        if (\Ease\Functions::cfg('SEND_INFO_TO')) {
            $emailAddress .= ',' . \Ease\Functions::cfg('SEND_INFO_TO');
        }
        parent::__construct($emailAddress, $mailSubject, $emailContents);
        $this->setMailHeaders([
            'Reply-To' => 'fakturace@spoje.net',
            'From' => \Ease\Functions::cfg('SEND_MAILS_FROM')
        ]);
    }

    public function getSignature()
    {
        return \Ease\Functions::cfg('SIGNATURE');
    }
}
