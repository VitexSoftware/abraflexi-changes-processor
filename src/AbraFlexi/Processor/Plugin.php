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
     * Record source system url
     * 
     * @var string
     */
    public $sourceId = 0;

    /**
     * 
     * @var string
     */
    protected $metaState = null;

    /**
     * 
     * @var string
     */
    private $createColumn;

    /**
     * 
     * @var string
     */
    private $myTable = 'changes_cache';

    /**
     * Handle Incoming change
     *
     * @param int   $id      changed record id
     * @param array $options 
     */
    public function __construct($id, $options) {
        $this->myTable = 'changes_cache';
        $this->createColumn = 'when';
        $this->throwException = false;
        parent::__construct($id, $options);
        $this->cache = array_key_exists('history', $options) ? $options['history'] : new FlexiHistory(null, $this->getConnectionOptions());
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
     * create new cache record
     * 
     * @return integer Id of inserted row
     */
    public function importRecord() {
        $recordId = $this->getMyKey();
        $evidence = $this->getEvidence();
        $change = [
            'operation' => 'import',
            'evidence' => $evidence,
            'recordid' => $recordId,
            'source' => $this->sourceId,
            'json' => self::serialize($this->getData())];
        if ($this->changeid) {
            $change['changeid'] = $this->changeid;
        }
        return $this->cache->insertToSQL($change);
    }

    /**
     * 
     * @return int
     */
    public function checkRecordPresence() {
        return $this->cache->listingQuery()->where('evidence', $this->getEvidence())->where('recordid', $this->getMyKey())->where('source', $this->sourceId)->count();
    }

    /**
     * @todo Ukladat jen potrebna data
     * @param int $cutId delete all records bigger than id
     */
    public function saveHistory($cutId = null) {
        $recordId = $this->getMyKey();
        $evidence = $this->getEvidence();
        if ($this->cache->getLastHistoryState($evidence, $recordId) != $this->getData()) {
            $change = [
                'operation' => $this->operation,
                'evidence' => $evidence,
                'recordid' => $recordId,
                'json' => self::serialize($this->getData())];
            if ($this->changeid) {
                $change['changeid'] = $this->changeid;
            }
            if ($cutId) {
                $this->cache->cutFlexiHistory($cutId);
            }
            $this->cache->insertToSQL($change);
        }
    }

    /**
     * 
     * @param string $evidence
     * @param string $recordId
     * 
     * @return type
     */
    public function getLastHistoryState($evidence, $recordId) {
        $lastChangeJson = $this->cache->listingQuery()->where('recordid', $recordId)->orderBy('when DESC')->limit(1);
        return empty($lastChangeJson) ? null : json_decode($lastChangeJson, true);
    }

    /**
     * Current operation
     * @param string $operation
     */
    public function setOperation($operation) {
        $this->operation = $operation;
    }

    /**
     * Process current change
     * 
     * @return boolean operation restult
     */
    public function process($operation) {
        $result = false;
        $this->metaState = null;
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
     * 
     * @return string
     */
    public function getMetaState() {
        if ($this->debug === true) {
            $this->addStatusMessage(_('MetaState processing is not yet implemented'), 'warning');
        }
        return is_null($this->metaState) ? $this->operation : $this->metaState;
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
            $this->addStatusMessage(\AbraFlexi\RO::uncode($this->getRecordIdent()) . ': ' . _('No Update Action Defined'), 'debug');
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
        $prevData = $this->cache->listingQuery()->where('evidence', $this->getEvidence())->where('source', $this->sourceId)->where('recordid', $this->getMyKey())->fetch();
        if (($this->debug === true) && empty($prevData)) {
            $this->addStatusMessage(sprintf(_('No cached data for %s %s found'), $this->getEvidence(), $this->getRecordIdent()), empty($prevData) ? 'error' : 'success' );
        }

        return (!empty($prevData) && count($prevData)) ? array_merge(self::unserialize($prevData['json']), $prevData) : [];
    }

    /**
     * get Changes to previous record
     * 
     * @return array
     */
    public function getChanges() {
        if ($this->operation == 'create') {
            $change = $this->getData();
        } else {
            $previous = $this->getPreviousData();
            if (empty($previous)) {
                throw new \AbraFlexi\Exception(sprintf(_('No FlexiHistory for %s %s'), $this->evidence, $this->getRecordIdent()), $this);
            } else {
                $change = $this->dataDifference($this->getData(), $previous);
            }
        }
        return $change;
    }

    /**
     * Discover difference between two AbraFlexi records
     * 
     * @param array $data   Old Record
     * @param array $datb   New Record
     * 
     * @return array different columns 
     */
    public function dataDifference($data, $datb) {
        $flexiData = is_array($data) ? $this->normalizeArray($data) : [];
        $sqlData = is_array($datb) ? $this->normalizeArray($datb) : [];
        $diff = \Rogervila\ArrayDiffMultidimensional::compare($flexiData, $sqlData);
        return $diff;
    }

    /**
     * Prepare AbraFlexi data to be comapred
     * 
     * @param array $record raw data
     * 
     * @return array
     */
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
                                    $record[$column][$key] = is_array($data) ? $this->normalizeArray($data) : $data;
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
            'json' => self::serialize($this->getData()),
            'source' => $this->sourceId,
            'changeid' => $this->changeid,
            'code' => $this->getRecordCode(),
            'meta' => $this->getMetaState()
        ];
        $result = $this->cache->insertToSQL($change);
        if ($this->debug === true) {
            $this->addStatusMessage(sprintf(_('Creating new cache record for %s %s'), $this->getEvidence(), $this->getRecordIdent()), empty($result) ? 'error' : 'success' );
        }
        return $result;
    }

    /**
     * 
     * @return int
     */
    public function updateRecordHistory() {
//        $me = ['evidence' => $this->getEvidence(), 'recordid' => $this->getMyKey(), 'source' => $this->sourceId];
//        if (($this->debug === true) && empty($this->cache->listingQuery()->where($me)->count())) {
        $result = $this->createRecordHistory();
//        }
//
//
//        $result = $this->cache->updateToSQL(['recordid' => $me['recordid'], 'operation' => $this->operation, 'source' => $this->sourceId, 'meta' => $this->getMetaState(), 'json' => self::serialize($this->getData())], $me);
        if ($this->debug === true) {
            $this->addStatusMessage(sprintf(_('Updating cache record for %s %s'), $this->getEvidence(), $this->getRecordIdent()), empty($result) ? 'error' : 'success' );
        }
        return $result;
    }

    /**
     * Serialize AbraFlexi data before storing
     * 
     * @param array $abraflexiData 
     * 
     * @return strinh
     */
    public function serialize($abraflexiData) {
        return serialize($abraflexiData);
    }

    /**
     * Restre AbraflexiData
     * 
     * @param string $abraflexiString
     * 
     * @return array
     */
    public function unserialize($abraflexiString) {
        return unserialize($abraflexiString);
    }

    /**
     * 
     * @return type
     */
    public function deleteRecordHistory() {
        $result = $this->cache->deleteFromSQL(['recordid' => $this->getMyKey(), 'evidence' => $this->getEvidence()]);
        if ($this->debug === true) {
            $this->addStatusMessage(sprintf(_('Removing cache record for %s %s'), $this->getEvidence(), $this->getRecordIdent()), empty($result) ? 'error' : 'success' );
        }
        return $result;
    }

    /**
     * Get All Classes in namespace
     * 
     * @param string $namespace
     * 
     * @return array<string>
     */
    public static function classesInNamespace($namespace) {
        $namespace .= '\\';
        $allClasses = get_declared_classes();
        print_r(asort($allClasses));
        $myClasses = array_filter(get_declared_classes(), function ($item) use ($namespace) {
            return substr($item, 0, strlen($namespace)) === $namespace;
        });
        $theClasses = [];
        foreach ($myClasses AS $class):
            $theParts = explode('\\', $class);
            $theClasses[] = end($theParts);
        endforeach;
        return $theClasses;
    }

    /**
     * Retrurn number of loadable classes in given path
     * 
     * @param string $path
     * 
     * @return int
     */
    public static function loadClassesInDir($path) {
        $found = 0;

        if (is_dir($path)) {

            $d = dir($path);

            while (false !== ($entry = $d->read())) {
                if (pathinfo($entry, PATHINFO_EXTENSION) == 'php') {
                    include_once $path . '/' . $entry;
                }
            }
            $d->close();
        }
        return $found;
    }

}
