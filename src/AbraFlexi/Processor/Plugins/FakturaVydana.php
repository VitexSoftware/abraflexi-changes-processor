<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AbraFlexi\Processor\Plugins;

use AbraFlexi\Firma;
use AbraFlexi\Priloha;
use AbraFlexi\Processor\Plugin;
use AbraFlexi\Reminder\PaymentRecievedConfirmation;
use AbraFlexi\Reminder\Upominac;
use AbraFlexi\RO;
use AbraFlexi\RW;

/**
 * Description of FakturaVydana
 *
 * @author vitex
 */
class FakturaVydana extends Plugin {

    use Firma;

    /**
     * Order Data
     * @var array
     */
    public $orderData = null;

    /**
     * Evidence used
     * @var string 
     */
    public $evidence = 'faktura-vydana';

    /**
     * Keep History for current object's evidence
     * @var boolean 
     */
    public $keepHistory = true;

    /**
     * Is invoice Settled
     * 
     * @return boolean
     */
    public function isSettled() {
        $changes = $this->getChanges();
        return isset($changes['datUhr']) && !empty($changes['datUhr']) ? true : false;
    }

    /**
     * Is invoice Dismissed
     * 
     * @return boolean
     */
    public function isStorned() {
        $changes = $this->getChanges();
        return (isset($changes['storno']) && !empty($changes['storno'])) ? true : false;
    }

    /**
     * Obtain Product codes used in this invoice
     * 
     * @return array
     */
    public function getProdcodes() {
        $prodCodes = [];
        $orderItems = $this->getDataValue('polozkyFaktury');
        if (!empty($orderItems) && count($orderItems)) {
            foreach ($orderItems as $orderItem) {
                if (!empty($orderItem['cenik'])) {
                    $prodCodes[$orderItem['cenik']] = $orderItem['cenik'];
                }
            }
        }
        return $prodCodes;
    }

    /**
     * Obtain instanced modules for invoice items
     * 
     * @return \SpojeNet\System\OrderPlugin
     */
    public function orderModulesForInvoiceItems() {
        $orderModules = null;
        $modulesForProducts = $this->getModulesForProducts($this->getProdcodes());
        foreach ($modulesForProducts as $pluginName) {
            $className = '\\AbraFlexi\\Processor\\orderplugins\\OrderPlugins' . $pluginName;
            $orderModules[$pluginName] = new $className($modulesForProducts);
        }
        return $orderModules;
    }

    /**
     * 
     * @param array $prodCodes
     * 
     * @return array
     */
    public function getModulesForProducts($prodCodes) {
        $processors = [];
        foreach ($prodCodes as $prodCode) {
            $processor = self::getProcessorForProduct(RO::code($prodCode));
            if (!empty($processor)) {
                $processors[$prodCode] = $processor;
            }
        }
        return $processors;
    }

    /**
     * 
     * @param sting $product AbraFlexi product CODE
     * 
     * @return string|null
     */
    public static function getProcessorForProduct($product) {
        $atributor = new RW(null, ['evidence' => 'atribut']);
        $atribut = $atributor->getColumnsFromAbraFlexi(['id', 'valString'],
                ['cenik' => $product, 'typAtributu' => 'code:API']);
        return empty($atribut) ? null : $atribut[0]['valString'];
    }

    /**
     * Invoice was created
     * @return boolean operation success
     */
    public function create() {
        $this->addStatusMessage(sprintf('New invoice %s %s was created',
                        $this->getDataValue('typDokl'), $this->getDataValue('kod')) . ' ' . $this->getDataValue('firma@showAs') . ' ' . $this->getDataValue('sumCelkem') . ' ' . $this->getDataValue('mena@showAs'));
        return true;
    }

    /**
     * Invoice was updated. What to do now ?
     * 
     * @return boolean Change was processed. Ok remeber it
     */
    public function update() {
        if ($this->isSettled()) {
            $this->addStatusMessage(sprintf('Processing settled invoice %s ',
                            $this->getDataValue('kod')));
            $orderModules = $this->orderModulesForInvoiceItems();
            if (!empty($orderModules) && count($orderModules)) {
                $this->loadOrderData();
                foreach ($orderModules as $orderModule) {
                    $this->addStatusMessage(sprintf(_('Settle Item(s) %s by %s'),
                                    implode(',', $orderModule->getMyProductCodes()),
                                    str_replace('\\', '/', get_class($orderModule))));
                    $orderModule->settled($this);
                }
            }

            $adrHelper = $this->getFirmaObject();

            if (!empty($this->getDataValue('kontaktEmail'))) {
                $notify = $this->getDataValue('kontaktEmail');
            } else {
                $notify = $adrHelper->getNotificationEmailAddress();
            }

            $engine = new Upominac();
            $zewlScore = $engine->getCustomerScore($adrHelper->getMyKey());

            if (!strstr($this->getDataValue('stitky'), 'SETTLE_NOTIFIED')) {
                $this->setDataValue('email', $notify);
                $potvrzovac = new PaymentRecievedConfirmation($this);
                if ($potvrzovac->send()) {
                    $this->insertToAbraFlexi(['id' => $this->getRecordIdent(),
                        'stitky' => 'SETTLE_NOTIFIED']);
                }
            } elseif ($this->debug === true) {
                $this->addStatusMessage(_('Settle is already notified'), 'warning');
            }
        }
        return true;
    }

    /**
     * take deata from attachment order.json
     */
    public function loadOrderData() {
        $attachments = Priloha::getAttachmentsList($this);
        foreach ($attachments as $attachment) {
            if ($attachment['nazSoub'] == 'order.json') {
                $orderJson = Priloha::getAttachment($attachment['id']);
                $this->orderData = json_decode($orderJson, true)[0];
            }
        }
    }

    /**
     * Discover Invoice meta state
     * 
     * @return string settle|storno|remind1|remind2|redmind3|create|update|delete
     */
    public function getMetaState() {
        $metaState = $this->operation;
        if ($metaState == 'update') {
            if ($this->isSettled()) {
                $metaState = 'settled';
            }
            if ($this->isStorned()) {
                $metaState = 'storno';
            }
            foreach ([1, 2, 3] as $r) {
                if ($this->isReminded($r)) {
                    $metaState = 'remind' . $r;
                }
            }
        }
        return $metaState;
    }

}
