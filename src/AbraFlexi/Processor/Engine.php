<?php

/**
 * System.Spoje.Net - Příjemce WebHooku
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2021 Spoje.Net 20201 VitexSoftware
 */

namespace AbraFlexi\Processor;

/**
 * Description of HookReciver
 *
 * @author vitex
 */
class Engine extends \AbraFlexi\Changes {

    /**
     *
     * @var boolean 
     */
    public $locked = false;

    use \Ease\SQL\Orm;

    public $format = 'json';

    /**
     * Changess to process
     * @var array 
     */
    public $changes = null;

    /**
     * Last Change ID availble in AbraFlexi
     * @var int 
     */
    public $globalVersion = null;

    /**
     *
     * @var array 
     */
    public $handlerCache = [];

    /**
     * Posledni zpracovana verze
     * @var int
     */
    public $lastProcessedVersion = null;

    /**
     * Database helper
     * @var \Ease\Brick
     */
    public $sqlHelper = null;

    /**
     * Webhook Processor lockfile
     * @var string 
     */
    private $lockfile = '/tmp/webhook.lock';

    /**
     * Prijmac WebHooku
     */
    public function __construct() {
        parent::__construct();
        $this->lockfile = sys_get_temp_dir() . '/webhook.lock';
        $this->myTable = 'changesapi';
        $this->lastProcessedVersion = $this->getLastProcessedVersion();
        $this->locked = $this->locked();
        $this->debug = true;
    }

    /**
     * Zpracuje změny
     * 
     * @param array $changes
     * 
     * @return array list IDS processed
     */
    function processAbraFlexiChanges(array $changes) {
        $changepos = 0;
        $doneIDd = [];
        foreach ($changes as $change) {
            $changepos++;
            $evidence = $change['@evidence'];
            $inVersion = intval($change['@in-version']);
            $operation = $change['@operation'];
            $id = intval($change['id']);
            $externalIDs = isset($change['external-ids']) ? $change['external-ids'] : [];

            $docId = empty($externalIDs) ? $id : current($externalIDs);

            $handlerClassName = \AbraFlexi\RO::evidenceToClassName($evidence);
            $handlerClass = '\\AbraFlexi\\Processor\\Plugins\\' . $handlerClassName;
            if (class_exists($handlerClass)) {
                $changeMeta = ['evidence' => $evidence, 'operation' => $operation,
                    'external-ids' => $externalIDs,
                    'changeid' => $inVersion];
                $saver = $this->getHandler($handlerClass, $docId, $changeMeta);
                if (($saver->lastResponseCode === 200) && $saver->process($operation) && ($this->debug === true)) {

                    $ident = \AbraFlexi\RO::uncode($saver->getRecordIdent());
                    if (!empty($ident)) {
                        $id .= ' ' . $ident;
                    }
                    $this->addStatusMessage(sprintf(_('Processing Change %s/%s version %d ( %s %s/%s ) Last %d'),
                                    $changepos, count($this->changes), $inVersion,
                                    $operation, $evidence, $id,
                                    $this->lastProcessedVersion), 'success');
                }
            } else {
                $this->addStatusMessage(sprintf( _('Request unexistent module %s'), $handlerClass) , 'warning');
            }
            $this->wipeCacheRecord($inVersion);
            $doneIDd[$inVersion] = $inVersion;
            $this->saveLastProcessedVersion($inVersion);
        }
        return $doneIDd;
    }

    /**
     * Get Changed Record Handler Class
     * 
     * @param string $handlerClass
     * @param int    $id
     * @param array  $changeMeta
     * 
     * @return WebHookHandler
     */
    public function &gethandler($handlerClass, $id, $changeMeta) {
        if (isset($this->handlerCache[$handlerClass][$id])) {
            $this->handlerCache[$handlerClass][$id]->loadFromAbraFlexi($id);
        } else {
            $this->handlerCache[$handlerClass][$id] = new $handlerClass($id, $changeMeta);
            if ($this->handlerCache[$handlerClass][$id]->lastResponseCode != 200) {
                $this->addStatusMessage(sprintf(_('Record %s not found in %s'), json_encode($changeMeta)), 'error');
            }
        }
        return $this->handlerCache[$handlerClass][$id];
    }

    /**
     * Převezme změny z WebHooku do $this->changes
     * 
     * @link https://www.abraflexi.eu/api/dokumentace/ref/changes-api/ Changes API
     * 
     * @param array $changes pole změn nazvz sloupcu Json 
     * 
     * @return int Globální verze poslední přijaté změny
     */
    public function takeApiChanges(array $changes) {
        $result = null;
        if (array_key_exists('winstrom', $changes)) {
            $this->globalVersion = intval($changes['winstrom']['@globalVersion']);
            $this->changes = self::reindexArrayBy($changes['winstrom']['changes'],
                            '@in-version');

            ksort($this->changes);
        }
        $result = is_numeric($changes['winstrom']['next']) ? $changes['winstrom']['next'] - 1 : $this->globalVersion;

        foreach ($this->changes as $change) {
            $changesToLog[] = $change['@evidence'] . ' ' . $change['@operation'] . ':' . $change['id'] . (empty($change['external-ids']) ? '' : json_encode($change['external-ids']) );
        }

        $this->addStatusMessage(sprintf(_('%s Changes to process: %s'),
                        count($this->changes), implode(',', $changesToLog)), 'debug');

        return $result;
    }

    /**
     * Ulozi posledni zpracovanou verzi
     *
     * @param int $version
     * 
     * @return string Restult
     */
    public function saveLastProcessedVersion($version) {
        $this->myTable = 'changesapi';
        $this->createColumn = false;
        $this->lastProcessedVersion = $version;
        $this->myCreateColumn = null;
        $this->deleteFromSQL(['serverurl' => \Ease\Functions::cfg('ABRAFLEXI_URL')]);
        $result = $this->insertToSQL(['serverurl' => \Ease\Functions::cfg('ABRAFLEXI_URL'), 'doneid' => $version]);
        if (is_null($result)) {
            $this->addStatusMessage(_("Last Processed Change ID Saving Failed"),
                    'error');
        } elseif($this->debug === true) {
            $this->addStatusMessage( sprintf( _("Last Processed Change ID %s save"), $version ), $result ? 'success' : 'error');
            
        }
        return $result;
    }

    /**
     * Nacte posledni zpracovanou verzi
     *
     * @return int $version
     */
    public function getLastProcessedVersion() {
        $lastProcessedVersion = null;
        $chRaw = $this->getColumnsFromSQL(['changeid'],
                ['serverurl' => \Ease\Functions::cfg('ABRAFLEXI_URL')]);
        if (isset($chRaw[0]['changeid'])) {
            $lastProcessedVersion = intval($chRaw[0]['changeid']);
        } else {
            $this->addStatusMessage(_("Last Processed Change ID Loading Failed"),
                    'warning');
        }
        return $lastProcessedVersion;
    }

    /**
     * Lock into lockfile
     * 
     * @return int size of saved lockfile in bytes
     */
    public function lock() {
        return file_put_contents($this->lockfile, getmypid());
    }

    /**
     * Webhook processor lock check
     * 
     * @returm locked by PID
     */
    public function locked() {
        return $this->isLocked() ? intval(file_get_contents($this->lockfile)) : 0;
    }

    /**
     * 
     * @return boolean
     */
    public function isProcessRunning() {
        if (!file_exists($this->lockfile) || !is_file($this->lockfile)) {
            return false;
        }
        $pid = file_get_contents($this->lockfile);
        return posix_kill($pid, 0);
    }

    /**
     * 
     * @return boolean
     */
    public function isLocked() {
        $locked = false;
        $lockfilePresent = file_exists($this->lockfile);
        if ($lockfilePresent) {
            if ($this->isProcessRunning()) {
                $locked = true;
            } else {
                $currentProcessPID = file_get_contents($this->lockfile);
                $locFileAge = time() - filemtime($this->lockfile);
                $this->addStatusMessage(sprintf('Ophraned lockfile found. pid: %d age: %s s.',
                                $currentProcessPID, $locFileAge), 'error');
                $this->unlock();
            }
        }
        return $locked;
    }

    /**
     * Remove lockfile
     */
    public function unlock() {
        return file_exists($this->lockfile) ? unlink($this->lockfile) : true;
    }

    /**
     * Load all Webhooks from cache and Process
     * 
     * @return boolean all changes processed successfully ?
     */
    public function processCachedChanges() {
        $result = false;
        $changesRaw = $this->fluent->from('changes_cache')->orderBy('inversion');
        if (!empty($changesRaw)) {
            $changesToProcess = [];
            foreach ($changesRaw as $changeId => $changeDataSaved) {
                $changesToProcess[$changeId] = self::sqlColsToJsonCols($changeDataSaved);
            }
            $this->changes = \Ease\Functions::reindexArrayBy($changesToProcess, '@in-version');
            $changesDone = $this->processAbraFlexiChanges($this->changes);
            if ($changesDone) {
                $this->saveLastProcessedVersion(max($changesDone));
            }
            $result = !empty($changesDone);
        }
        return $result;
    }

    /**
     * conver $sqlData column names to $jsonData column names
     * 
     * @param array $sqlData
     * 
     * @return array
     */
    public static function sqlColsToJsonCols($sqlData) {
        $jsonData['@in-version'] = $sqlData['inversion'];
        $jsonData['id'] = $sqlData['recordid'];
        $jsonData['@evidence'] = $sqlData['evidence'];
        $jsonData['@operation'] = $sqlData['operation'];
        $jsonData['external-ids'] = unserialize(stripslashes($sqlData['externalids']));
        return $jsonData;
    }

    /**
     * Save Json Data to SQL cache
     * @param array $changes
     * 
     * @return int lastChangeID
     */
    public function saveWebhookData($changes) {
        foreach ($changes as $changeId => $apiData) {
            $this->fluent->insertInto('changes_cache')->values(array_merge(['source' => 'abraflexi', 'target' => 'system'], self::jsonColsToSQLCols($apiData)))->execute();
        }
        return intval($apiData['@in-version']);
    }

    /**
     * conver $jsonData column names to $sqlData column names
     * 
     * @param array $sqlData
     * 
     * @return array
     */
    public static function jsonColsToSQLCols($apiData) {
        $sqlData['inversion'] = $apiData['@in-version'];
        $sqlData['recordid'] = $apiData['id'];
        $sqlData['evidence'] = $apiData['@evidence'];
        $sqlData['operation'] = $apiData['@operation'];
        $sqlData['externalids'] = addslashes(serialize(array_key_exists('external-ids',
                                $apiData) ? $apiData['external-ids'] : []));
        return $sqlData;
    }

    /**
     * skip webhooks with 'inversion' less or equal to $this->getLastProcessedVersion()
     * 
     * @param array $webhookJsonData
     * 
     * @return array
     */
    public function onlyFreshHooks($webhooksRawData) {
        $lastProcessed = $this->getLastProcessedVersion();
        foreach ($webhooksRawData as $recId => $webhookRawData) {
            if ($webhookRawData['@in-version'] <= $lastProcessed) {
                unset($webhooksRawData[$recId]);
            }
        }
        return $webhooksRawData;
    }

    /**
     * Empty given change version from cache
     * 
     * @param int $inVersion
     * 
     * @return type
     */
    public function wipeCacheRecord($inVersion) {
        $this->setMyTable('changes_cache');
        $result = $this->deleteFromSQL(['inversion' => $inVersion]);
        
              
        if($this->debug === true){
            $this->addStatusMessage( sprintf( _("Cached change wipe %s (%s remain)"), $inVersion, $this->listingQuery()->count()), $result ? 'success' : 'error');
        }
        $this->setMyTable('flexihistory');
        return $result;
    }

    public function dbApiUrl($apiData) {
        $this->setEvidence($apiData['@evidence']);
        if (array_key_exists('external-ids', $apiData) && !empty($apiData['external-ids'])) {
            $this->setMyKey(current($apiData['external-ids']));
        } else {
            $this->setMyKey($apiData['id']);
        }
        return $this->getApiURL();
    }

}
