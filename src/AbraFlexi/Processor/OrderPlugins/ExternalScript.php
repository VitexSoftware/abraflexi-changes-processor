<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SpojeNet\System\orderplugins;

/**
 * Try To run External script
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class ExternalScript extends \SpojeNet\System\OrderPlugin {

    /**
     * Do this when item is settled
     * 
     * @param \SpojeNet\System\FakturaVydana $invoice
     */
    public function settled($invoice) {
        $result = false;
        $itemsOfInterest = $this->getItemsOfInterest($invoice);
        if (count($itemsOfInterest) && (array_key_exists('API',
                        \AbraFlexi\Stitek::listToArray($invoice->getDataValue('stitky'))))) {
            $this->publishEnv($this->getData());
            $argumentor = new \AbraFlexi\FlexiBeeRW(null, ['evidence' => 'atribut']);
            foreach ($itemsOfInterest as $itemOfInterest) {
                $processorArgInfo = $argumentor->getColumnsFromFlexiBee(['id', 'hodnota'],
                        ['cenik' => $product, 'typAtributu' => 'code:ARGUMENT']);
                $customScript = empty($processorArgInfo) ? '' : $processorArgInfo[0]['hodnota'];
                if($customScript){
                    $this->publishEnv($itemOfInterest);
                    system($customScript);
                }
            }
        }

        return $result;
    }

    public function publishEnv(array $env, $prefix = '') {
        foreach ($env as $key => $value) {
            if (!is_array($value)) {
                putenv($prefix . "$env=$value");
            }
        }
    }

}
