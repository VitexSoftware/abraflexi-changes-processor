<?php

namespace AbraFlexi\Processor;

/**
 * Description of WebHookHandler
 *
 * @author vitex
 */
abstract class Plugin extends \AbraFlexi\RW {

    use \Ease\SQL\Orm;

    /**
     * Current processed Change id
     * @var int
     */
    public $changeid = null;

    /**
     * WebHook API operation
     * @var string create|update|delete
     */
    public $operation = null;

    /**
     * External IDs for current record
     * @var array 
     */
    public $extids = [];

    /**
     * Database Helper
     * @var \Ease\Brick
     */
    public $dbHelper = null;

    /**
     * Keep History for current object's evidence
     * @var boolean 
     */
    public $keepHistory = true;

    /**
     * Cache Handler
     * 
     * @var FlexiHistory
     */
    private $cache = null;

    /**
     * Handle Incoming change
     *
     * @param int $id changed record id
     * @param array $options 
     */
    public function __construct($id, $options) {
        parent::__construct($id, $options);
        $this->cache = new FlexiHistory();
        $this->myTable = 'flexihistory';
        $this->createColumn = 'when';
    }

    /**
     * SetUp Object to be ready for connect
     *
     * @param array $options Object Options (company,url,user,password,evidence,
     *                                       prefix,defaultUrlParams,debug)
     */
    public function setUp($options = []) {
        parent::setUp($options);
        if (isset($options['changeid'])) {
            $this->changeid = $options['changeid'];
        }
        if (isset($options['operation'])) {
            $this->setOperation($options['operation']);
            if ($options['operation'] == 'delete') {
                $this->ignore404(true);
            }
        }
        if (isset($options['external-ids'])) {
            $this->extids = $options['external-ids'];
        }
    }

    /**
     * @todo Ukladat jen potrebna data
     */
    public function saveHistory() {
        $recordId = $this->getMyKey();
        $evidence = $this->getEvidence();
        if ($this->getLastHistoryState($evidence, $recordId) != $this->getData()) {
            $change = [
                'operation' => $this->operation,
                'evidence' => $evidence,
                'recordid' => $recordId,
                'json' => $this->dblink->addslashes(json_encode($this->getData()))];
            if ($this->changeid) {
                $change['changeid'] = $this->changeid;
            }
            $this->insertToSQL($change);
        }
    }

    public function getLastHistoryState($evidence, $recordId) {
        $lastChangeJson = $this->dblink->queryToValue('SELECT json FROM flexihistory WHERE recordid=' . $recordId . ' ORDER BY `when` DESC LIMIT 1 ');
        return empty($lastChangeJson) ? null : json_decode($lastChangeJson, true);
    }

    /**
     *
     * @param type $operation
     */
    public function setOperation($operation) {
        $this->operation = $operation;
    }

    /**
     *
     */
    public function process($operation) {
        $result = false;
        switch ($operation) {
            case 'create':
                if ($this->keepHistory) {
                    $this->createRecordHistory();
                }
                $result = $this->create();
                break;
            case 'update':
                $result = $this->update();
                if ($this->keepHistory) {
                    $this->updateRecordHistory();
                }
                break;
            case 'delete':
                $result = $this->delete();
                if ($this->keepHistory) {
                    $this->deleteRecordHistory();
                }
                break;
        }
        return $result;
    }

    /**
     * Trigger me in case of creating new document
     */
    public function create() {
        if ($this->debug === true) {
            $this->addStatusMessage(\AbraFlexi\RO::uncode($this->getRecordIdent()) . ': ' . _('No Create Action Defined'),
                    'debug');
        }
        return null;
    }

    /**
     * I care for every document change
     */
    public function update() {
        if ($this->debug === true) {
            $this->addStatusMessage(\AbraFlexi\RO::uncode($this->getRecordIdent()) . ': ' . _('No Update Action Defined') . ' ' . json_encode($this->getChanges()),
                    'debug');
        }
        return null;
    }

    /**
     * Call me to say goodbye your record 
     */
    public function delete() {
        if ($this->debug === true) {
            $this->addStatusMessage(\AbraFlexi\RO::uncode($this->getRecordIdent()) . ': ' . _('No Delete Action Defined'),
                    'debug');
        }
        return null;
    }

    /**
     * 
     * @return array|null
     */
    public function getCurrentData() {
        $dataRaw = $this->getColumnsFromAbraFlexi('*', ['id' => $this->getMyKey()]);
        return count($dataRaw) ? $dataRaw[0] : null;
    }

    /**
     * 
     * @return array
     */
    public function getPreviousData() {
        $prevData = $this->cache->listingQuery()->where('evidence', $this->getEvidence())->where('recordid', $this->getMyKey())->fetch();
        return (!empty($prevData) && count($prevData) ? json_decode($prevData['json']): [] );
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

    /**
     * 
     */
    public function getChangedData() {
        
    }

    /**
     * 
     * @return int
     */
    public function createRecordHistory() {
        $recordId = $this->getMyKey();
        $evidence = $this->getEvidence();
        $change = [
            'operation' => $this->operation,
            'evidence' => $evidence,
            'recordid' => $recordId,
            'json' => addslashes(json_encode($this->getData()))];
        return $this->cache->insertToSQL($change);
    }

    public function updateRecordHistory() {
        return $this->cache->updateToSQL(['operation' => $this->operation, 'json' => addslashes(json_encode($this->getData()))], ['evidence' => $this->getEvidence(), 'recordid' => $this->getMyKey()]);
    }

    public function deleteRecordHistory() {
        return $this->cache->deleteFromSQL(['recordid' => $this->getMyKey(), 'evidence' => $this->getEvidence()]);
    }

}
