<?php

namespace AbraFlexi\Processor\Plugins;

/**
 * Description of Adresar
 *
 * @author vitex
 */
class Banka  extends \AbraFlexi\Processor\Plugin
{
    public $evidence = 'banka';

    /**
     * Match Payment when create
     */
    public function create()
    {
        if ($this->getDataValue('typPohybuK') == 'typPohybu.prijem') {
            if ($this->getDataValue('sparovano') == 'false') {
                $steamer = new \AbraFlexi\Bricks\ParovacFaktur(\Ease\Shared::instanced()->configuration);
                
                
                
// TODO: Match Invoice:        $steamer->outInvoiceMatchByBank( $steamer->findBestPayment([$this->getData()], $invoice) $invoiceData, );
            }
        }
    }
}
