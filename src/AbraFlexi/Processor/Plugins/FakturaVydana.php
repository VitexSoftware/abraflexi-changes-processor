<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AbraFlexi\Processor\Plugins;

use AbraFlexi\firma;
use AbraFlexi\Processor\Plugin;

/**
 * Description of FakturaVydana
 *
 * @author vitex
 */
class FakturaVydana extends Plugin {

    use firma;
    
    use \AbraFlexi\subItems;

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
        try {
            $changes = $this->getChanges();
        } catch (\AbraFlexi\Exception $exc) {
            $changes = [];
        }
        return isset($changes['datUhr']) && !empty((string) $changes['datUhr']) ? true : false;
    }

    /**
     * Is invoice Dismissed
     * 
     * @return boolean
     */
    public function isStorned() {
        try {
            $changes = $this->getChanges();
        } catch (\AbraFlexi\Exception $exc) {
            $changes = [];
        }
        return (isset($changes['storno']) && !empty($changes['storno'])) ? true : false;
    }

    /**
     * Invoice was created
     * 
     * @return boolean operation success
     */
    public function create() {
        $this->addStatusMessage(sprintf('New invoice %s %s was created',
                        $this->getDataValue('typDokl'), $this->getDataValue('kod')) . ' ' . $this->getDataValue('firma')->showAs . ' ' . $this->getDataValue('sumCelkem') . ' ' . $this->getDataValue('mena')->showAs);
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
        }
        return true;
    }

    /**
     * Discover Invoice meta state
     * 
     * @return string settle|storno|remind1|remind2|remind3|penalised|create|update|delete
     */
    public function getMetaState() {
        if (is_null($this->metaState)) {
            $this->metaState = $this->operation;
            if ($this->metaState == 'update') {
                foreach ([1, 2, 3] as $r) {
                    if ($this->isReminded($r)) {
                        $this->metaState = 'remind' . $r;
                    }
                }
                if ($this->isReminded(4)) {
                    $this->metaState = 'penalised';
                }
                if ($this->isSettled()) {
                    $this->metaState = 'settled';
                }
                if ($this->isStorned()) {
                    $this->metaState = 'storno';
                }
            }
        }
        return $this->metaState;
    }

    /**
     * Check reminds & penalisation dates
     * 
     * @param int $r 
     * 
     * @return boolean
     */
    public function isReminded($r) {
        $cols = [1 => 'datUp1', 2 => 'datUp2', 3 => 'datSmir', 4 => 'datPenale'];
        return empty((string) $this->getDataValue($cols[$r])) === false;
    }

}
