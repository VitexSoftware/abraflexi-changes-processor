<?php

namespace AbraFlexi\Processor\Plugins;

/**
 * Order processing Class
 *
 * @author vitex
 */
class ObjednavkaPrijata extends \AbraFlexi\Processor\Plugin
{
    public $evidence = 'objednavka-prijata';

    /**
     * Invoice was created
     *
     * @return boolean operation success
     */
    public function create()
    {
        $this->addStatusMessage(sprintf(
            'New Order %s %s was created',
            $this->getDataValue('typDokl'),
            $this->getDataValue('kod')
        ) . ' ' . $this->getDataValue('firma')->showAs . ' ' . $this->getDataValue('sumCelkem') . ' ' . $this->getDataValue('mena')->showAs);
        return true;
    }

    /**
     * Invoice was updated. What to do now ?
     *
     * @return boolean Change was processed. Ok remeber it
     */
    public function update()
    {
        $this->addStatusMessage(sprintf('Processing Updated Order %s ', $this->getDataValue('kod')));
        return true;
    }

    /**
     * Discover current MetaState
     *
     * @return int
     */
    public function getMetaState()
    {
        try {
            $changes = $this->getChanges();
        } catch (\AbraFlexi\Exception $exc) {
            $changes = [];
        }
        if (array_key_exists('poznam', $changes) && strlen($changes['poznam'])) {
            $this->metaState = 'NOTE';
        }
        return is_null($this->metaState) ? $this->operation : $this->metaState;
    }
}
