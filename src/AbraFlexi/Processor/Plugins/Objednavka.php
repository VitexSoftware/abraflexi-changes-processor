<?php

namespace AbraFlexi\Processor\Plugins;

/**
 * Order processing Class
 *
 * @author vitex
 */
class ObjednavkaPrijata extends \AbraFlexi\Processor\Plugin {

    public $evidence = 'objednavka-prijata';

    /**
     * Invoice was created
     * 
     * @return boolean operation success
     */
    public function create() {
        $this->addStatusMessage(sprintf('New Order %s %s was created',
                        $this->getDataValue('typDokl'), $this->getDataValue('kod')) . ' ' . $this->getDataValue('firma')->showAs . ' ' . $this->getDataValue('sumCelkem') . ' ' . $this->getDataValue('mena')->showAs);
        return true;
    }

    /**
     * Invoice was updated. What to do now ?
     * 
     * @return boolean Change was processed. Ok remeber it
     */
    public function update() {
        switch ($this->noteToState($this->getDataValue('poznam'))) {
            case '':


                break;
            case '':


                break;
            case '':


                break;

            default:
                break;
        }
        $this->addStatusMessage(sprintf('Processing Updated Order %s ', $this->getDataValue('kod')));
        return true;
    }

    /**
     * 
     * @param string $note
     */
    public function noteToState($note) {
        if (preg_match('^Stav: (.*)$', $note, $stateRaw)) {
            switch (strtolower(\Ease\Functions::rip($stateRaw))) {
                case 'nevyrizena': //Nevyřízená  
                    break;
                case 'probiha': //Probíhá
                    break;
                case 'stornovana'://Stornována  
                //    Storno ()
                    $state = 'stavDoklObch.storno';
                    break;
                case 'vyrizena'://Vyřízena  
                    break;
                default: //    Nespecifikováno ()
                    $state = 'stavDoklObch.nespec';
                    break;
            }
            return $state;
        }


//    Připraveno (stavDoklObch.pripraveno)
//    Schváleno (stavDoklObch.schvaleno)
//    Částečně na cestě (stavDoklObch.castecneNaCeste)
//    Na cestě (stavDoklObch.naCeste)
//    Částečně vydáno/přijato (stavDoklObch.castVydano)
//    Vydáno/přijato (stavDoklObch.vydano)
//    Částečně hotovo (stavDoklObch.castHotovo)
//    Hotovo (stavDoklObch.hotovo)
    }

}
