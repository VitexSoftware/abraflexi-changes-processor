<?php

namespace AbraFlexi\Processor\Plugins;

/**
 * Description of Adresar
 *
 * @author vitex
 */
class Banka extends \AbraFlexi\Processor\Plugin
{
    /**
     * What we handle ?
     * @var string
     */
    public $evidence = 'banka';

    /**
     * Invoice was created
     *
     * @return boolean operation success
     */
    public function create()
    {
        $this->addStatusMessage(sprintf(
            'New Bank move %s %s was created',
            $this->getDataValue('typDokl'),
            $this->getDataValue('kod')
        ) . ' ' . $this->getDataValue('firma')->showAs . ' ' . $this->getDataValue('sumCelkem') . ' ' . $this->getDataValue('mena')->showAs);
        return true;
    }

    /**
     * Payment metastate
     *
     * @return string
     */
    public function getMetaState(): string
    {
        return $this->isIncome() ? 'income' : parent::getMetaState();
    }

    /**
     * Check if move is income
     *
     * @return boolean
     */
    public function isIncome()
    {
        return $this->getDataValue('typPohybuK') === 'typPohybu.prijem';
    }
}
