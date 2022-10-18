<?php

namespace AbraFlexi\Processor\Plugins;

/**
 * Description of Adresar
 *
 * @author vitex
 */
class Banka  extends \AbraFlexi\Processor\Plugin
{
    /**
     * What we handle ?
     * @var string
     */
    public $evidence = 'banka';
    
    /**
     * Payment metastate
     * 
     * @return string
     */
    public function getMetaState(): int {
        return $this->isIncome() ? 'income' : parent::getMetaState();
    }

    /**
     * Check if move is income
     * 
     * @return string
     */
    public function isIncome() {
        return $this->getDataValue('typPohybuK') === 'typPohybu.prijem';
    }
    
}
