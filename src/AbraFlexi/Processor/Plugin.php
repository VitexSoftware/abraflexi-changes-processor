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
        $this->cache = array_key_exists('history', $options) ? $options['history'] : new FlexiHistory();
        $this->myTable = 'flexihistory';
        $this->createColumn = 'when';
        $this->debug = true;
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
     * 
     * @return integer
     */
    public function importRecord() {
        $recordId = $this->getMyKey();
        $evidence = $this->getEvidence();
        $change = [
            'operation' => 'import',
            'evidence' => $evidence,
            'recordid' => $recordId,
            'json' => addslashes(json_encode($this->getData()))];
        if ($this->changeid) {
            $change['changeid'] = $this->changeid;
        }
        return $this->cache->insertToSQL($change);
    }

    /**
     * @todo Ukladat jen potrebna data
     */
    public function saveHistory() {
        $recordId = $this->getMyKey();
        $evidence = $this->getEvidence();
        if ($this->cache->getLastHistoryState($evidence, $recordId) != $this->getData()) {
            $change = [
                'operation' => $this->operation,
                'evidence' => $evidence,
                'recordid' => $recordId,
                'json' => addslashes(json_encode($this->getData()))];
            if ($this->changeid) {
                $change['changeid'] = $this->changeid;
            }
            $this->cache->insertToSQL($change);
        }
    }

    public function getLastHistoryState($evidence, $recordId) {
        $lastChangeJson = $this->cache->listingQuery()->where('recordid', $recordId)->orderBy('when DESC')->limit(1);
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
                $this->createRecordHistory();
                $result = $this->create();
                break;
            case 'update':
                $result = $this->update();
                $this->updateRecordHistory();
                break;
            case 'delete':
                $result = $this->delete();
                $this->deleteRecordHistory();
                break;
        }
        return $result;
    }

    /**
     * Discover current MetaState
     * @return int
     */
    public function getMetaState() {
        if ($this->debug === true) {
            $this->addStatusMessage(_('MetaState processing is not yet implemented'), 'warning');
        }
        return $this->operation;
    }

    /**
     * Meta State of 
     * 
     * @return boolean meta saved
     */
    public function updateMetaState() {
        $meta = $this->getMetaState();
        return empty($meta) ? 0 : $this->cache->updateToSQL(['meta' => $meta], ['recordid' => $this->getMyKey(), 'evidence' => $this->getEvidence()]);
    }

    /**
     * Trigger me in case of creating new document
     */
    public function create() {
        if ($this->debug === true) {
            $this->addStatusMessage(\AbraFlexi\RO::uncode($this->getRecordIdent()) . ': ' . _('No Create Action Defined'),
                    'debug');
        }
        return true;
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
        if (($this->debug === true) && empty($prevData)) {
            $this->addStatusMessage(sprintf(_('No cached data for %s %s found'), $this->getEvidence(), $this->getRecordIdent()), empty($result) ? 'error' : 'success' );
        }

        return (!empty($prevData) && count($prevData)) ? array_merge(self::jsonToData($prevData['json']), $prevData) : [];
    }

    public static function jsonToData($json) {
        return json_decode(stripslashes($json), true);
    }

    public function dataToJson(array $data) {
        return addslashes(json_encode($data));
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

            $previous = $this->dataDifference($this->getData(), $previous);
        }
        return $previous;
    }

    public function dataDifference($data, $datb) {
        $flexiData = $this->normalizeArray($data);
        $sqlData = $this->normalizeArray($datb);
        return \Rogervila\ArrayDiffMultidimensional::compare($flexiData, $sqlData);
    }

    public function normalizeArray($record) {
        $evidence = $this->getEvidence();
        foreach ($record as $column => $value) {
            if (strstr($column, '@')) { //Skip Caption columns
                unset($record[$column]);
            } else {
                $columnInfo = $this->getColumnInfo($column, $evidence);
                if (is_null($columnInfo)) {
                    $this->addStatusMessage(sprintf(_('Unknown response field %s. (Please update library or static definitions)'), $column . '@' . $evidence), 'debug');
                } else {
                    switch ($columnInfo['type']) {
                        case 'datetime':
                            $record[$column] = (empty($value) ? null : (is_array($value) ? $value['date'] : ( is_object($value) ? \AbraFlexi\RO::dateToFlexiDateTime($value) : $value ) ));
                            break;
                        case 'date':
                            $record[$column] = (empty($value) ? null : (is_array($value) ? $value['date'] : ( is_object($value) ? \AbraFlexi\RO::dateToFlexiDate($value) : $value ) ));
                            break;
                        default :
                            if (is_array($value)) {
                                foreach ($value as $key => $data) {
                                    $record[$column][$key] = $this->normalizeArray($data);
                                }
                            } else {
                                $record[$column] = strval($record[$column]);
                            }
                            break;
                    }
                }
            }
        }
        return $record;
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
            'json' => addslashes(json_encode($this->getData())),
            'meta' => $this->getMetaState()
        ];
        $result = $this->cache->insertToSQL($change);
        if ($this->debug === true) {
            $this->addStatusMessage(sprintf(_('Creating new cache record for %s %s'), $this->getEvidence(), $this->getRecordIdent()), empty($result) ? 'error' : 'success' );
        }
        return $result;
    }

    public function updateRecordHistory() {
        $me = ['evidence' => $this->getEvidence(), 'recordid' => $this->getMyKey()];
        if (($this->debug === true) && empty($this->cache->listingQuery()->where($me)->count())) {
            $this->createRecordHistory();
        }
        $result = $this->cache->updateToSQL(['operation' => $this->operation, 'json' => addslashes(json_encode($this->getData()))], $me);
        if ($this->debug === true) {
            $this->addStatusMessage(sprintf(_('Updating cache record for %s %s'), $this->getEvidence(), $this->getRecordIdent()), empty($result) ? 'error' : 'success' );
        }
        return $result;
    }

    public function deleteRecordHistory() {
        return $this->cache->deleteFromSQL(['recordid' => $this->getMyKey(), 'evidence' => $this->getEvidence()]);
    }

}
