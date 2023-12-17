<?php

/**
 * Changes API Hanfler.
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2022 VitexSoftware
 */

namespace AbraFlexi\Processor;

/**
 * Description of Api
 *
 * @author vitex
 */
class ChangesApi extends Engine
{
    /**
     *
     * @var string
     */
    public $myTable = 'changesapi';

    /**
     * Register new data source
     *
     * @param string $source uri
     *
     * @return int new source ID
     */
    public function registerApi($source = null)
    {
        if (empty($source)) {
            $source = $this->sourceUri();
        }
        $sourceId = $this->insertToSQL(['serverurl' => $source, 'changeid' => 0]);
        if (is_null($sourceId)) {
            $this->addStatusMessage(sprintf(_("Source registering of %s Failed"), $source), 'error');
        } else {
            if ($this->debug === true) {
                $this->addStatusMessage(sprintf(_('New source registered %s #%d Saved'), $source, $sourceId));
            }
        }
        return $sourceId;
    }

    /**
     *
     * @return int
     */
    public function getSourceId()
    {
        return $this->listingQuery()->select('id')->where('serverurl', $this->sourceUri())->fetchColumn(0);
    }

    /**
     *
     * @return string
     */
    public function sourceUri()
    {
        return \Ease\Functions::cfg('ABRAFLEXI_URL') . '/c/' . \Ease\Functions::cfg('ABRAFLEXI_COMPANY');
    }
}
