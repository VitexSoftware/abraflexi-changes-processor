<?php
namespace AbraFlexi\Processor\Plugins;

/**
 * Description of FakturaPrijata
 *
 * @author vitex
 */
class InterniDoklad  extends \AbraFlexi\Processor\Plugin
{
    /**
     * Order Data
     * @var array
     */
    public $orderData   = null;
    /**
     *
     * @var string 
     */
    public $evidence    = 'interni-doklad';

    /**
     * Keep History for current object's evidence
     * @var boolean 
     */
    public $keepHistory = true;

    /**
     * Invoice was inserted. What to do now ?
     * 
     * @return boolean Change was processed. Ok remeber it
     */
    public function create()
    {
        switch (\AbraFlexi\RO::uncode($this->getDataValue('typDokl'))) {
            case 'ZBYTEK NÁKLAD':
            case 'ZBYTEK VÝNOS':
                $this->addStatusMessage( $this , 'warning');
                break;

            default:
                break;
        }

        return true;
    }

}
