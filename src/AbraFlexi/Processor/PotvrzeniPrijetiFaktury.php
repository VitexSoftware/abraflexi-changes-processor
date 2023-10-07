<?php

/**
 * Accpeted Invoice Confirmation Class
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2021-2022 VitexSoftware
 */

namespace AbraFlexi\Processor;

/**
 * Description of PotvrzeniPrijetiFaktury
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class PotvrzeniPrijetiFaktury extends Mailer
{
    /**
     * Company signature
     * @var string
     */
    static $signature = '';

    /**
     * Odešle potvrzení úhrady
     * @param \AbraFlexi\FakturaVydana $invoice
     */
    public function __construct($invoice = null)
    {
        if (!is_null($invoice)) {
            $this->assignInvoice($invoice);
        }
    }

    /**
     *
     * @param \AbraFlexi\FakturaPrijata $invoice
     */
    public function assignInvoice($invoice)
    {
        $defaultLocale = 'cs_CZ';
        setlocale(LC_ALL, $defaultLocale);
        putenv("LC_ALL=$defaultLocale");

        $body = new \Ease\Container();

        $to = (new \AbraFlexi\Adresar($invoice->getDataValue('firma')))->getNotificationEmailAddress();

        $customerName = $invoice->getDataValue('firma')->showAs;
        if (empty($customerName)) {
            $customerName = \AbraFlexi\RO::uncode($invoice->getDataValue('firma'));
        }

        $body->addItem(new \AbraFlexi\ui\CompanyLogo(['align' => 'right', 'id' => 'companylogo',
                'height' => '50', 'title' => _('Company logo')]));

        $prober = new \AbraFlexi\Company();
        $infoRaw = $prober->getFlexiData();
        if (count($infoRaw) && !array_key_exists('success', $infoRaw)) {
            $info = \Ease\Functions::reindexArrayBy($infoRaw, 'dbNazev');
            $myCompany = $prober->getCompany();
            if (array_key_exists($myCompany, $info)) {
                $body->addItem(new \Ease\Html\H2Tag($info[$myCompany]['nazev']));
            }
        }


        $body->addItem(new \Ease\Html\DivTag(sprintf(
            _('Dear customer %s,'),
            $customerName
        )));
        $body->addItem(new \Ease\Html\DivTag("\n<br>"));

        $body->addItem(new \Ease\Html\DivTag(sprintf(
            _('we confirm receipt of invoice %s as %s '),
            $invoice->getDataValue('cisDosle'),
            $invoice->getDataValue('kod')
        )));
        $body->addItem(new \Ease\Html\DivTag("\n<br>"));

        $body->addItem(new \Ease\Html\DivTag(_('With greetings')));

        $body->addItem(new \Ease\Html\DivTag("\n<br>"));

        $body->addItem(nl2br($this->getSignature()));

        parent::__construct(
            $to,
            sprintf(
                _('Confirmation of receipt your invoice %s'),
                \AbraFlexi\RO::uncode($invoice->getRecordIdent())
            ),
            $body
        );
        $this->setMailHeaders(['Cc' => \Ease\Functions::cfg('SEND_INFO_TO')]);
    }
}
