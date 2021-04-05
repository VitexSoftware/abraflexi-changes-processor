<?php
/**
 * System.Spoje.Net - OrderPlugin parent.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2018 Spoje.Net
 */

namespace AbraFlexi\Processor;

use Ease\Html\InputHiddenTag;
use Ease\Sand;
use Ease\Shared;
use Ease\TWB\Form;
use AbraFlexi\FlexiBeeRO;
use AbraFlexi\Stitek;
use SpojeNet\System\ui\OrderFormHtml;

/**
 * Description of OrderPlugin
 *
 * @author vitex
 */
abstract class OrderPlugin extends Sand
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
    public $productCode = null;

    /**
     *
     * @var type 
     */
    public $fields = [];

    /**
     * PoductCode->orderModule
     * @var array 
     */
    public $modulesForProducts = [];

    /**
     * Abstract Order Plugin
     * 
     * @param array $modulesForProducts
     */
    public function __construct(array $modulesForProducts = [])
    {
        parent::__construct();
        $this->modulesForProducts = $modulesForProducts;
        $this->setModuleName();
    }

    public function setModuleName()
    {
        $this->name = _('Unnamed');
    }

    /**
     * 
     * @return array
     */
    public function getMyProductCodes()
    {
        return array_keys(array_filter($this->modulesForProducts,
                [$this, 'isMyClass']));
    }

    public function isMyClass($className)
    {
        return ($className == \AbraFlexi\Bricks\Convertor::baseClassName($this) );
    }

    /**
     * Add Product Fields to Form
     *
     * @param OrderFormHtml $form
     * 
     * @return Form
     */
    public function formFields($form)
    {
        $edit = $form->order->getDataValue('edit');
        if (!is_null($form->order->getDataValue('edit'))) {
            $form->addItem(new InputHiddenTag('edited', $edit));
        }
        $form->addItem(new InputHiddenTag('productCode', $this->productCode));

        return $form;
    }

    /**
     * Make OrderItem from FormData
     *
     * @param OrderItem $orderItem
     * 
     * @return array
     */
    public function processFields(OrderItem $orderItem)
    {
        $orderItemData = [
            'cenaMj' => (float) $orderItem->cenik->getDataValue('cenaZaklVcDph'),
            'nazev' => $orderItem->cenik->getDataValue('nazev'),
            'cenik' => \AbraFlexi\FlexiBeeRO::code($orderItem->cenik->getDataValue('kod')),
            'stitky' => 'API',
            'typPolozkyK' => 'typPolozky.katalog'
        ];
        return $orderItemData;
    }

    /**
     * Control filelds for requirements
     *
     * @param OrderItem $order
     * 
     * @return boolean
     */
    public function controlFields($order)
    {
        $result = true;
        if (!strlen($order->getDataValue('service'))) {
            $order->addStatusMessage(_('Product not specified'), 'error');
            $result = false;
        }
        return $result;
    }

    /**
     * Check if customer is signed in
     * 
     * @return boolean
     */
    public function checkLogin()
    {
        if (get_class(Shared::user()) != 'SpojeNet\System\Customer') {
            $this->addStatusMessage(_('Please sign in to order item'), 'warning');
            $success = false;
        } else {
            $success = true;
        }
        return $success;
    }

    /**
     * Method called when the item was settled
     *
     * @param FakturaVydana $invoice
     * 
     * @return boolean|null Processing result
     */
    public function settled($invoice)
    {
        $this->addStatusMessage(sprintf(_('Item %s was settled with action handled in OrderPlugin code on %s'),
                 implode(',', $this->getMyProductCodes()) , FlexiBeeRO::uncode($invoice)), 'success');
        if (array_key_exists('API',
                Stitek::listToArray($invoice->getDataValue('stitky')))) {
            $this->addStatusMessage(_('Removing API label'),
                Stitek::unsetLabel('API', $invoice) ? 'success' : 'error');
        }
        return true;
    }

    /**
     * Return all invoice items supposed to bee processed by this plugin
     * The Prodct Code is matching and API* label is set
     * 
     * @param FakturaVydana $invoice Invoice to be processed
     * 
     * @return array of data of items with FlexiBee pricelist code $this->productCode
     */
    public function getItemsOfInterest($invoice)
    {
        $myItemData = [];
        $itemsRaw   = $invoice->getDataValue('polozkyFaktury');
        if (!empty($itemsRaw) && is_array($itemsRaw)) {
            $myProductCodes = array_combine($this->getMyProductCodes(),$this->getMyProductCodes());
            foreach ($itemsRaw as $invoiceItem) {
                if (array_key_exists(\AbraFlexi\FlexiBeeRO::code($invoiceItem['kod']), $myProductCodes)) {
                    $myItemData[$invoiceItem['id']] = $invoiceItem;
                }

// array_key_exists('stitky', $invoiceItem) && strstr($invoiceItem['stitky'], 'API')                
            }
        }
        return $myItemData;
    }
}
