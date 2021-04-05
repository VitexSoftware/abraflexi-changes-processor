<?php
/**
 * System.Spoje.Net - Domain Credit Plugin.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 Spoje.Net
 */

namespace SpojeNet\System\orderplugins;

use \SpojeNet\System\SubregAPI;

/**
 * Description of VoIP
 *
 * @author vitex
 */
class DomainCredit extends \SpojeNet\System\OrderPlugin
{
    /**
     * Name of Product/Service
     * @var string
     */
    public $name = null;

    /**
     * FlexiBee storage item Code
     * @var string
     */
    public $productCode = 'KREDIT_DOMENA';

    /**
     * Offer this Item only to Customer with this extId filled ext:subreg:12345
     * @var string subreg|subreg|etc ...
     */
    public $reqExtPrefix = 'subreg';

    /**
     *
     * @var type 
     */
    private $subreg = null;

    /**
     *
     * @var SubregAPI
     */
    public $fields = [];

    /**
     * Domain Credit
     */
    public function setModuleName()
    {
        $this->name = _('Domain Credit');
    }

    /**
     * Add Product Fields to Form
     *
     * @param \SpojeNet\System\ui\OrderFormHtml $form
     * 
     * @return Ease\TWB\Form
     */
    public function formFields($form)
    {
        parent::formFields($form);

        $credit = $form->order->getDataValue('credit') ? $form->order->getDataValue('credit')
                : 500;

        $form->addInput(new \Ease\Html\InputNumberTag('credit', $credit,
                ['min' => 500]), _('Requied Credit Value'), '500',
            _('Amount of Credit you requested'));

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
        return parent::controlFields($order) && $this->checkCredit($order->getDataValue('credit'));
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
        } else {
            $ok = true;
        }
        return $ok;
    }

    /**
     * Make OrderItem from FormData
     *
     * @param \SpojeNet\System\OrderItem $orderItem
     * @return array
     */
    public function processFields($orderItem)
    {
        $orderItemData           = parent::processFields($orderItem);
        $orderItemData['cenaMj'] = $orderItem->getDataValue('credit');
        return $orderItemData;
    }

    /**
     * Do this when item is settled
     * 
     * @param \SpojeNet\System\FakturaVydana $invoice
     */
    public function settled($invoice)
    {
        $result          = false;
        $itemsOfInterest = $this->getItemsOfInterest($invoice);
        if (count($itemsOfInterest) && (array_key_exists('API',
                \AbraFlexi\Stitek::listToArray($invoice->getDataValue('stitky'))))) {
            $subregLogin = $this->flexiBeeAddressToSubregUserName($invoice->getDataValue('firma'));
            if ($subregLogin) {
                $price = 0;
                foreach ($itemsOfInterest as $creditItemData) {
                    if (isset($creditItemData['mnozMj'])) {
                        $itemPrice = $creditItemData['mnozMj'] * $creditItemData['cenaMj'];
                    } else {
                        $itemPrice = floatval($creditItemData['cenaMj']);
                    }
                    $price += $itemPrice;
                }
                $this->subreger()->call('Credit_Correction',
                    ['username' => $subregLogin, 'amount' => $price, 'reason' => 'faktura '.$invoice->getRecordIdent().' uhrazena']);
                $this->addStatusMessage(sprintf(_('SubReg add credit %s for %s by %s'),
                        $price,
                        $subregLogin.'/'.\AbraFlexi\FlexiBeeRO::uncode($invoice->getDataValue('firma')),
                        $invoice->getRecordIdent()),
                    ($this->subreger()->lastResult == 'ok') ? 'success' : 'error' );
                $result = parent::settled($invoice);
            } else {
                $this->addStatusMessage(sprintf(_('%s not found as subreg clinet'),
                        \AbraFlexi\FlexiBeeRO::uncode($invoice->getDataValue('firma'))),
                    'error');
            }
        }
        return $result;
    }

    /**
     * Obtain Subreg userName for FlexiBee address
     * 
     * @param string $addressCode FlexiBee address code
     * 
     * @return string
     */
    public function flexiBeeAddressToSubregUserName($addressCode)
    {
        $subregLogin = null;
        $customer    = new \AbraFlexi\Adresar(\AbraFlexi\FlexiBeeRO::code($addressCode),
            ['detail' => 'id']);
        $subregID    = $customer->getExternalID('subreg');
        if (empty($subregID)) {
            $this->addStatusMessage(\AbraFlexi\FlexiBeeRO::uncode($addressCode).': '._('SubReg client without ext:subreg:xxx'),
                'warning');
        } else {
            $userListRaw = $this->subreger()->call('Users_List');

            if (intval($userListRaw['count'])) {
                foreach ($userListRaw['users'] as $user) {
                    if ($user['id'] == $subregID) {
                        $subregLogin = $user['username'];
                        break;
                    }
                }
            }
        }
        return $subregLogin;
    }

    /**
     * Subreg ApiClient
     * 
     * @return SubregAPI
     */
    public function subreger()
    {
        if (is_null($this->subreg)) {
            $this->subreg = new SubregAPI();
        }
        return $this->subreg;
    }
}
