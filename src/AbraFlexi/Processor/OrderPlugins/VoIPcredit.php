<?php
/**
 * System.Spoje.Net - VoIP Order Plugin.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-19 Spoje.Net
 */

namespace SpojeNet\System\orderplugins;

/**
 * Description of VoIP
 *
 * @author vitex
 */
class VoIPcredit extends \SpojeNet\System\OrderPlugin
{
    /**
     * Name of Product/Service
     * @var string
     */
    public $name = null;

    /**
     * FlexiBee storage item Code 
     * @var string regexp
     */
    public $productCode = 'KREDIT_VOIP';

    /**
     * Minimal Requed Requested Credit
     * @var float
     */
    public $minCredit = 200;

    /**
     *
     * @var type
     */
    public $fields = [];

    /**
     * IPEX User ID
     * @var int 
     */
    private $ipexUserID;

    /**
     * 
     * @var string customer email 
     */
    private $customerEmail = null;

    /**
     * Offer this Item only to Customer with this extId filled ext:ipex:12345
     * @var string ipex|subreg|etc ...
     */
    public $reqExtPrefix = 'ipex';

    /**
     * VoIP Credir Order Plugin
     */
    public function setModuleName()
    {
        $this->name = _('VoIP Credit');
    }

    /**
     * Add Product Fields to Form
     *
     * @param Ease\TWB\Form $form
     *
     * @return Ease\TWB\Form
     */
    public function formFields($form)
    {
        parent::formFields($form);

        $this->minCredit = $form->order->cenik->getDataValue('cenaZakl');


        $credit = $form->order->getDataValue('credit') ? $form->order->getDataValue('credit')
                : $this->minCredit;

        $form->addInput(new \Ease\Html\InputNumberTag('credit', $credit,
                ['min' => $this->minCredit, 'id' => 'credit']),
            _('Requied Credit Value').' ('._('with VAT').')', $this->minCredit,
            _('Your price').' '._('without VAT').':  '.new \Ease\Html\SpanTag(null,
                ['id' => 'vatPrice']));

        \Ease\Shared::webPage()->addJavaScript('
$(\'#credit\').change(function(){
    var coeficient = 21 / 121;
    var price = $(this).val();
    var dph = coeficient * price;
    $(\'#vatPrice\').html( ( price - dph ).toFixed(2) );
}).change()
');


        $form->addInput(new \Ease\Html\InputTextTag('number',
                $form->order->getDataValue('number'),
                ['pattern' => '.{9,}', 'required', 'title' => sprintf(_('%d characters minimum'),
                    9)]), _('For Number'), '',
            _('Telephone number you requested to pay'));

        return $form;
    }

    /**
     * Control Plugin fields
     *
     * @param \SpojeNet\System\OrderItem $order
     *
     * @return boolean
     */
    public function controlFields($order)
    {
        return parent::controlFields($order) && $this->checkLogin() && $this->checkCredit($order->getDataValue('credit'))
            && $this->checkNumber($order->getDataValue('number'));
    }

    /**
     * Check Credit
     * 
     * @param float $lmsid
     *
     * @return boolen check result
     */
    public function checkCredit($credit)
    {
        $ok = false;
        if (floatval($credit) <= 0) {
            $this->addStatusMessage(_('Please enter Requested Credit'),
                'warning');
        } elseif (floatval($credit) < $this->minCredit) {
            $this->addStatusMessage(sprintf(_('Please enter at least %s Credit'),
                    $this->minCredit), 'warning');
        } else {
            $ok = true;
        }
        return $ok;
    }

    /**
     * Check VoIP number
     *
     * @param string $number VoIP Number
     *
     * @return boolean
     */
    public function checkNumber($number)
    {
        $ok = true;
        if (strlen($number) == 0) {
            $this->addStatusMessage(_('VoIP Number not set'), 'warning');
            $ok = false;
        } else {

            $servicer      = new \IPEXB2B\Services();
            $servicer->ignore404(true);
            $servicer->loadFromIPEX(['number' => $number]);
            $servicer->ignore404(false);
            $numberInfoRaw = $servicer->getData();
            if (empty($numberInfoRaw)) {
                $this->addStatusMessage(sprintf(_('VoIP Number %s is not registered'),
                        $number), 'warning');
                $ok = false;
            } else {

                $numberDetails = $numberInfoRaw[0];

                if (array_key_exists('paymentType', $numberDetails) && ($numberDetails['paymentType']
                    == 'prepaid')) {

                    if ($numberDetails['status'] == 'active') {

                        $myCompany = \Ease\Shared::user()->adresar;

                        $owner = false;
                        if (array_key_exists('customerExternId', $numberDetails)) {
                            $ipexExternId        = $numberDetails['customerExternId'];
                            $this->customerEmail = \Ease\Shared::user()->getUserEmail();
                            $this->ipexUserID    = $numberDetails['customerId'];
                            $owner               = (\AbraFlexi\FlexiBeeRO::uncode($ipexExternId)
                                == \AbraFlexi\FlexiBeeRO::uncode($myCompany->getDataValue('kod')));
                        } else {
                            $owner = ($this->ipexCustomerNameToFlexiBeeCustomer($numberDetails['customerName'])
                                == $myCompany->getDataValue('nazev'));
                        }
                        if ($owner) {
                            $ok = true;
                        } else {
                            $this->addStatusMessage(sprintf(_('VoIP Number %s does not belong to you'),
                                    $number), 'warning');
                            $ok = false;
                        }
                    } else { //Number Is not Active
                        $this->addToLog(sprintf(_('VoIP Number %s is not active'),
                                $number), 'warning');
                        $this->addStatusMessage(sprintf(_('Cannot add credit to VoIP Number %s'),
                                $number), 'warning');
                        $ok = false;
                    }
                } else {
                    $this->addToLog(sprintf(_('VoIP Number %s is not prepayed'),
                            $number), 'warning');
                    $this->addStatusMessage(sprintf(_('Cannot add credit to VoIP Number %s'),
                            $number), 'warning');
                    $ok = false;
                }
            }
        }
        return $ok;
    }

    /**
     * Try to found FlexiBee user for name from Ipex
     *
     * @param string $ipexAddressName
     * 
     * @return string
     */
    public function ipexCustomerNameToFlexiBeeCustomer($ipexAddressName)
    {
        $flexiBeeName = 'n/a';
        if (preg_match('/(.*)\s\((.*?)\)/i', $ipexAddressName, $match)) { //OK - LMS ID
            $adresInfoRaw = \Ease\Shared::user()->adresar->getColumnsFromFlexibee('nazev',
                ['id' => 'ext:lms.cstmr:'.intval($match[2])]);
            $flexiBeeName = $adresInfoRaw[0]['nazev'];
        } else { //Try to found by name
            $adresInfoRaw = \Ease\Shared::user()->adresar->getColumnsFromFlexibee('nazev',
                ['nazev' => trim($ipexAddressName)]);
            if (count($adresInfoRaw) == 1) {
                $flexiBeeName = $adresInfoRaw[0]['nazev'];
            } else {
                $this->addStatusMessage(_('Chyba hledání klientů tohoto jména. Prosím kontaktujte Helpdesk SPOJE.NET'));
                $opMail  = 'helpdesk@spoje.net,tomasek@spoje.net';
                $subject = sprintf(_('IPEX: nelze dohledat klienta %s'),
                    $ipexAddressName);
                $message = print_r($adresInfoRaw, true);
                $mailer  = new \Ease\Mailer($opMail, $subject, $message);
                $mailer->send();
            }
        }
        return $flexiBeeName;
    }

    /**
     * Make OrderItem from FormData
     *
     * @param Order $order
     *
     * @return array
     */
    public function processFields($order)
    {

        $coeficient = 21 / 121;
        $price      = $order->getDataValue('credit');
        $dph        = $coeficient * $price;

        $orderItemData                   = parent::processFields($order);
        $orderItemData['cenaZaklBezDph'] = $price - $dph;
        $orderItemData['cenaMj']         = $price;
        $orderItemData['cenaZaklVcDph']  = $price;
        $orderItemData['poznam']         = $order->getDataValue('number');
        $orderItemData['ipexuser']       = $this->ipexUserID;
        $orderItemData['phoneno']        = $order->getDataValue('number');
        $orderItemData['notify']         = $this->customerEmail;
        $orderItemData['nazev']          = _('VoIP Credit').': '.$price;
        $orderItemData['typCenyDphK']    = 'typCeny.sDph';
        $orderItemData['typSzbDphK']     = 'typSzbDph.dphZakl';
        $orderItemData['stitky']         = 'API';
        return $orderItemData;
    }

    /**
     * Do this when item is settled
     * 
     * @param \SpojeNet\System\FakturaVydana $invoice
     */
    public function settled($invoice)
    {
        $itemsOfInterest = $this->getItemsOfInterest($invoice);
        if (count($itemsOfInterest) && (array_key_exists('API',
                \AbraFlexi\Stitek::listToArray($invoice->getDataValue('stitky'))))) {
            foreach ($itemsOfInterest as $creditItemData) {
                if (isset($creditItemData['mnozMj'])) {
                    $itemPrice = $creditItemData['mnozMj'] * $creditItemData['cenaMj'];
                } else {
                    $itemPrice = floatval($creditItemData['cenaMj']);
                }
                $price += $itemPrice;
            }

            $voiper = new \IPEXB2B\Voip();
            $voiper->setPostFields(json_encode([
                'customerId' => $invoice->orderData['ipexuser'],
                'amount' => $invoice->orderData['cenaMj'],
                'expiration' => 365
            ]));
            $result = $voiper->requestData($invoice->orderData['phoneno'].'/credit',
                'PUT');
            if ($voiper->lastResponseCode == 200) {

                $mail = new \SpojeNet\System\Mailer($invoice->orderData['notify'],
                    'VoIP Kredit byl navýšen',
                    $invoice->getDataValue('firma@showAs')."\n".
                    'Kredit u VoIP čísla '.$invoice->orderData['phoneno'].' bylo navýšen o '.$invoice->orderData['cenaMj'].'.'."\n".' Doba trvání kreditu byla prodloužena 365 dní.');
                $mail->addItem(new \SpojeNet\System\ui\LinkToDocument($invoice));
                $mail->send();
            } else {
                $this->addStatusMessage('Incerase Credit Error: '.$invoice.' '.json_encode($invoice->orderData),
                    'error');
            }
        }
        return parent::settled($invoice);
    }
}
