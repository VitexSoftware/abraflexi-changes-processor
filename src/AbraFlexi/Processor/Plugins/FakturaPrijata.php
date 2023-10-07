<?php

namespace AbraFlexi\Processor\Plugins;

/**
 * Description of FakturaPrijata
 *
 * @author vitex
 */
class FakturaPrijata extends \AbraFlexi\Processor\Plugin
{
    use \AbraFlexi\subItems;

    /**
     * Order Data
     * @var array
     */
    public $orderData = null;

    /**
     * Handle Incoming change of Incoming Invoice
     *
     * @param int   $id      changed record id
     * @param array $options
     */
    public function __construct($id, $options)
    {
        \AbraFlexi\Processor\Engine::init(['ABRAFLEXI_OST_ZAVAZEK']);
        parent::__construct($id, $options);
    }

    /**
     *
     * @var string
     */
    public $evidence = 'faktura-prijata';

    /**
     * Keep History for current object's evidence
     * @var boolean
     */
    public $keepHistory = true;

    /**
     * Invoice was inserted. What to do now ?
     *
     * @return boolean Change was processed. Ok remeber it
     */
    public function create()
    {

        $this->addStatusMessage(sprintf(
            'New invoice %s was accepted',
            $this->getDataValue('kod')
        ) . ' ' . $this->getDataValue('firma')->showAs . ' Celkem: ' . $this->getDataValue('sumCelkem') . ' Zbyva uh.: ' . $this->getDataValue('zbyvaUhradit') . ' ' . $this->getDataValue('mena')->showAs);

        switch (\AbraFlexi\RO::uncode($this->getDataValue('typDokl'))) {
            case 'ZAVAZEK':
                $copyer = new \AbraFlexi\Bricks\Convertor(
                    $this,
                    new \AbraFlexi\Zavazek(['typDokl' => 'code:' . \Ease\Functions::cfg('OST-ZAVAZEK'), 'stitky' => 'SYSTEM'])
                );

                $zavazek = $copyer->conversion();

                if ($zavazek->sync()) {
                    $zavazek->addStatusMessage(sprintf(
                        _('new commitment %s'),
                        $zavazek->getApiUrl()
                    ), 'success');
                    if (!$this->deleteFromAbraFlexi()) {
                        $this->addStatusMessage(sprintf(
                            'error removig %s',
                            $this
                        ));
                    }
                } else {
                    $zavazek->addStatusMessage(sprintf(
                        _('error creating commitment from %s'),
                        $this
                    ), 'error');
                }

                break;

            default:
                break;
        }

        return true;
    }

    /**
     * Is invoice Settled
     *
     * @return boolean
     */
    public function bankOrderSent()
    {
        $changes = $this->getChanges();
        return isset($changes['stavUzivK']) && ($changes['datUhr'] == 'stavUziv.celPrikaz') ? true : false;
    }

//    /**
//     * Invoice was updated. What to do now ?
//     *
//     * @return boolean Change was processed. Ok remeber it
//     */
//    public function update()
//    {
//        if ($this->bankOrderSent()) {
//            $this->addStatusMessage(sprintf('Bank order to settle Invoice %s was sent',
//                    $this->getDataValue('kod')));
//
//            if (\AbraFlexi\RO::uncode($this->getDataValue('typDokl')) == 'FAKTURA') {
//
//                if (!empty($this->getDataValue('kontaktEmail'))) {
//                    $notify = $this->getDataValue('kontaktEmail');
//                } else {
//                    $adrHelper = new \AbraFlexi\Adresar($this->getDataValue('firma'));
//                    $notify    = \SpojeNet\System\Mailer::getNotificationEmailAddres($adrHelper);
//                    unset($adrHelper);
//                }
//
//                if (!empty($notify)) {
////                    if (!strstr($this->getDataValue('stitky'), 'SETTLE_NOTIFIED')) {
//                    $this->setDataValue('email', $notify);
//                    $potvrzovac = new \SpojeNet\System\PotvrzeniOdeslaniUhrady($this);
//                    if ($potvrzovac->send()) {
////                            $this->insertToAbraFlexi(['id' => $this->getRecordID(),
////                                'stitky' => 'SETTLE_NOTIFIED']);
////                        }
//                    }
//                } else {
//                    $this->addStatusMessage(
//                        sprintf(_('No notify email for %s  %s'),
//                            \AbraFlexi\RO::uncode($this->getDataValue('firma')),
//                            \SpojeNet\System\ParovacFaktur::apiUrlToLink($this->apiURL)
//                        )
//                        , 'warning');
//                }
//            }
//        }
//        return true;
//    }

    /**
     * take deata from attachment order.json
     */
    public function loadOrderData()
    {
        $attachments = \AbraFlexi\Priloha::getAttachmentsList($this);
        foreach ($attachments as $attachment) {
            if ($attachment['nazSoub'] == 'order.json') {
                $orderJson = \AbraFlexi\Priloha::getAttachment($attachment['id']);
                $this->orderData = json_decode($orderJson, true)[0];
            }
        }
    }
}
