<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SpojeNet\System\orderplugins;

/**
 * Description of Common
 *
 * @author vitex
 */
class Pricelist extends \SpojeNet\System\OrderPlugin
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
    public $productCode = null;

    /**
     *
     * @var array
     */
    public $fields = [];

    /**
     * Pricelist Item
     */
    public function setModuleName()
    {
        $this->name = _('Pricelist item');
    }

    /**
     * Add Product Fields to Form
     *
     * @param Ease\TWB\Form $form
     * @return Ease\TWB\Form
     */
    public function formFields($form)
    {
        parent::formFields($form);

        $code = $form->order->getDataValue('kod') ? $form->order->getDataValue('kod')
                : '';

        $count = $form->order->getDataValue('mnozMj') ? $form->order->getDataValue('mnozMj')
                : 1;

        $form->addInput(new \SpojeNet\System\ui\SearchBox('kod', $code,
        ['id' => 'productcode',
        'data-remote-list' => 'productcodes.php',
        'data-list-highlight' => 'true',
        'data-list-value-completion' => 'true'
        ]), _('Product Code'),
        _('ANTENA_2ODB_OUTDOOR'), _('Product code from our pricelist'));

        $form->addInput(new \Ease\Html\InputNumberTag('mnozMj', $count),
            _('Items count'), '', _('Amount requested'));

        $form->addInput(new \Ease\Html\InputTextTag('poznam',
                $form->order->getDataValue('poznam')), _('Note for stuff'),
            _('ASAP Please'), _('Your kind words about ordered item'));

        return $form;
    }

    /**
     * Control Plugin fields
     *
     * @param \SpojeNet\System\OrderItem $order
     * @return boolean
     */
    public function controlFields($order)
    {
        return parent::controlFields($order) && $this->checkCode($order->getDataValue('kod'))
            && $this->checkCount($order->getDataValue('mnozMj'));
    }

    /**
     * Check Count
     *
     * @param int $count 
     * @return boolen check result
     */
    public function checkCount($count)
    {
        return $count > 0;
    }

    /**
     * Check FlexiBee Product Code
     *
     * @param string $number VoIP Number
     * @return boolean
     */
    public function checkCode($code)
    {
        $ok = true;
        if (empty($code)) {
            $ok = false;
        } else {
            $pricelister = new \AbraFlexi\Cenik(\AbraFlexi\FlexiBeeRO::code($code));
            if (empty($pricelister->getMyKey())) {
                $this->addStatusMessage(sprintf(_('Product with code %s does not exist'),
                        $code), 'warning');
            } else {
                $ok = true;
            }
        }
        return $ok;
    }

    /**
     * Make OrderItem from FormData
     *
     * @param Order $order
     * @return array
     */
    public function processFields($order)
    {
        $orderItemData           = parent::processFields($order);
        $orderItemData['mnozMj'] = $order->getDataValue('mnozMj');
        $orderItemData['poznam'] = $order->getDataValue('poznam');
        $orderItemData['cenik']  = \AbraFlexi\FlexiBeeRO::code($order->getDataValue('kod'));
        return $orderItemData;
    }
}
