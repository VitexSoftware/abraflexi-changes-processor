<?php

namespace AbraFlexi\Processor\OrderPlugins;

use Ease\Html\InputNumberTag;
use Ease\Html\InputSearchTag;
use Ease\Html\InputTextTag;
use Ease\Shared;
use Ease\TWB4\Form;
use SpojeNet\System\Order;
use SpojeNet\System\OrderItem;
use SpojeNet\System\OrderPlugin;
use SpojeNet\System\ui\SearcherForInput;

/**
 * Common Item
 *
 * @author vitex
 */
class Common extends OrderPlugin {

    use SearcherForInput;

    /**
     * Name of Product/Service
     * @var string
     */
    public $name = null;

    /**
     * FlexiBee storage item Code
     * @var string regexp
     */
    public $productCode = null;

    /**
     * Values
     * @var array
     */
    public $fields = [];

    /**
     * Offer this Item only to Customer with this extId filled ext:ipex:12345
     * @var string ipex|subreg|etc ...
     */
    public $reqExtPrefix = null;

    /**
     * Common Order Item
     */
    public function setModuleName() {
        $this->name = _('Common item');
    }

    /**
     * Add Product Fields to Form
     *
     * @param Form $form
     * 
     * @return Form
     */
    public function formFields($form) {
        parent::formFields($form);

        $nazev = $form->order->getDataValue('nazev') ? $form->order->getDataValue('nazev') : '';

        $count = $form->order->getDataValue('count') ? $form->order->getDataValue('count') : 1;

        $price = $form->order->getDataValue('cenaZaklBezDph') ? $form->order->getDataValue('cenaZaklBezDph') : 0;

        $pricevat = $form->order->getDataValue('cenaZaklVcDph') ? $form->order->getDataValue('cenaZaklVcDph') : 0;

        $searchInput = new InputSearchTag('nazev', $nazev);

        $form->addInput($searchInput, _('Product Name'),
                _('Lenovo IdeaPad 110S-11IBR'), _('Commont product name you need'));

        $form->addInput(new InputNumberTag('cenaZaklBezDph', $price,
                        ['min' => 1, 'id' => 'cenaZaklBezDph', 'step' => '0.01']),
                _('Item price') . ' (' . _('without VAT') . ')');

        $form->addInput(new InputNumberTag('cenaZaklVcDph',
                        $pricevat,
                        ['min' => 1, 'id' => 'cenaZaklVcDph', 'step' => '0.01']),
                _('Requied Credit Value') . ' (' . _('with VAT') . ')');

        Shared::webPage()->addJavaScript('
$(\'#cenaZaklVcDph\').change(function(){
    var vat = 21
    var coeficient = vat / (100 + vat);
    var price = parseInt($(this).val());
    var dph = coeficient * price;
    $(\'#cenaZaklBezDph\').val( ( price - dph ).toFixed(2) );
}).change()
');

        Shared::webPage()->addJavaScript('
$(\'#cenaZaklBezDph\').change(function(){
    var vat = 21;
    var coeficient = (1 + (vat / 100));
    $(\'#cenaZaklVcDph\').val( ( $(\'#cenaZaklBezDph\').val() * coeficient ).toFixed(2) );
}).change()
');

        $form->addInput(new InputNumberTag('count', $count),
                _('Items count'), '', _('Amount requested'));

        $form->addInput(new InputTextTag('poznam',
                        $form->order->getDataValue('note')), _('Note for stuff'),
                _('ASAP Please'), _('Your kind words about ordered item'));

        return $form;
    }

    /**
     * Control Plugin fields
     *
     * @param OrderItem $order
     * 
     * @return boolean
     */
    public function controlFields($order) {
        return $this->checkPrices($order) && $this->checkNazev($order->getDataValue('nazev')) && $this->checkCount($order->getDataValue('count'));
    }

    /**
     * 
     * @param type $order
     * @return type
     */
    public function checkPrices($order) {
        $result = $order->getDataValue('cenaZaklBezDph') + $order->getDataValue('cenaZaklVcDph');
        if (!$result) {
            $this->addStatusMessage(sprintf(_('Product price can\'t be zero')),
                    'warning');
        }
        return boolval($result);
    }

    /**
     * Check Count
     *
     * @param int $count 
     * 
     * @return boolen check result
     */
    public function checkCount($count) {
        return $count > 0;
    }

    /**
     * Check Custom Product Name
     *
     * @param string $nazev Item name
     * 
     * @return boolean
     */
    public function checkNazev($nazev) {
        $ok = true;
        if (empty($nazev)) {
            $this->addStatusMessage(sprintf(_('Product name can\'t be empty'),
                            $nazev), 'warning');
            $ok = false;
        }
        return $ok;
    }

    /**
     * Make OrderItem from FormData
     *
     * @param Order $order
     * 
     * @return array
     */
    public function processFields($order) {
        $orderItemData = parent::processFields($order);
        $orderItemData['nazev'] = $order->getDataValue('nazev');
        $orderItemData['typCenyDphK'] = $order->getDataValue('typCenyDphK');
        $orderItemData['mnozMj'] = $order->getDataValue('count');
        $orderItemData['poznam'] = $order->getDataValue('poznam');
        $orderItemData['cenaZaklBezDph'] = $order->getDataValue('cenaZaklBezDph');
        $orderItemData['cenaMj'] = $order->getDataValue('cenaZaklVcDph');
        $orderItemData['cenaZaklVcDph'] = $order->getDataValue('cenaZaklVcDph');
        $orderItemData['typPolozkyK'] = 'typPolozky.obecny';
        $orderItemData['typCenyDphK'] = 'typCeny.sDph';
        $orderItemData['typSzbDphK'] = 'typSzbDph.dphZakl';
        unset($orderItemData['cenik']);
        return $orderItemData;
    }

}
