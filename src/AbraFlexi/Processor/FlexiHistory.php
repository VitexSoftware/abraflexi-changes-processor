<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AbraFlexi\Processor;

/**
 * Description of FlexiHistory
 *
 * @author vitex
 */
class FlexiHistory extends \Ease\SQL\Engine {

    public $myTable = 'flexihistory';
    public $options = [];
    public $keyColumn = 'recordid';

    /**
     * 
     * @var \AbraFlexi\RW
     */
    public $abraFlexi = null;

    /**
     * 
     * @param type $identifier
     * @param type $options
     */
    public function __construct($identifier = null, $options = []) {
        parent::__construct($identifier, $options);
        $this->abraFlexi = new \AbraFlexi\RW($identifier, $options);
        $this->options = $options;
    }
   
    /**
     * 
     * @return \AbraFlexi\Processor\handlerClass
     */
    public function getPlugins() {
        $plugins = [];
        $d = dir(dirname(__FILE__) . '/Plugins/');
        while (false !== ($entry = $d->read())) {
            if (strstr($entry, '.php')) {
                $handlerClassName = pathinfo($entry, PATHINFO_FILENAME);
                $handlerClass = '\\AbraFlexi\\Processor\\Plugins\\' . $handlerClassName;
                if (class_exists($handlerClass)) {
                    $plugins[$handlerClassName] = new $handlerClass(null, ['history' => $this]);
                };
            }
        }
        $d->close();

        return $plugins;
    }

    public function importHistory() {
        if ($this->listingQuery()->count()) {
            $this->addStatusMessage(sprintf(_('History table %s is not empty'), $this->getMyTable()), 'warning');
        } else {
            $this->abraFlexi->logBanner(\Ease\Functions::cfg('APP_NAME'));
            foreach ($this->getPlugins() as $plugin) {
                $position = 0;
                $plugin->addStatusMessage('Processing: ' . $plugin->getEvidenceURL());
                $ids = $plugin->getColumnsFromAbraFlexi(['id'], ['limit' => 0]);
                $allids = $ids ? count($ids) : 0;
                $this->addStatusMessage(sprintf(_('%d records found in evidence %s'), $allids, $plugin->getEvidence()));
                foreach ($ids as $id) {
                    $position++;
                    if ($plugin->loadFromAbraFlexi(intval($id['id']))) {
                        $info = $position . '/' . $allids . ' ' . $plugin->getDataValue('kod');
                        $result = $plugin->importRecord();
                        $plugin->addStatusMessage(sprintf(_('Saving record %s into history table'), $info), $result ? 'success' : 'error');
                    }
                }
            }
        }
    }

    public function getLastHistoryState($evidence, $recordId) {
        $lastChangeJson = $this->listingQuery()->where('recordid', $recordId)->orderBy('when DESC')->limit(1);
        return empty($lastChangeJson) ? null : json_decode($lastChangeJson, true);
    }

    /**
     * 
     * @return array|null
     */
    public function getCurrentData() {
        $dataRaw = $this->abraFlexi->getColumnsFromAbraFlexi('*', ['id' => $this->getMyKey()]);
        return count($dataRaw) ? $dataRaw[0] : null;
    }

    /**
     * 
     * @return array
     */
    public function getPreviousData() {
        $prevData = $this->cache->listingQuery()->where('evidence', $this->getEvidence())->where('recordid', $this->getMyKey())->fetch();
        return (!empty($prevData) && count($prevData) ? json_decode($prevData['json']) : [] );
    }

    /**
     * 
     * @return array
     */
    public function getChanges() {
        $previous = $this->getPreviousData();
        if (empty($previous)) {
            $previous = $this->getData();
        } else {
            $previous = array_diff($this->getData(), $previous);
        }
        return $previous;
    }

}
