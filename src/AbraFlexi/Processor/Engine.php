<?php

/**
 * Changes Processor engine class
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2021 VitexSoftware
 */

namespace AbraFlexi\Processor;

/**
 * Description of HookReciver
 *
 * @author vitex
 */
class Engine extends \Ease\SQL\Engine
{
    /**
     *
     * @var boolean
     */
    public $locked = false;

    /**
     *
     * @var string
     */
    public $format = 'json';

    /**
     * Changes to process
     * @var array
     */
    public $changes = null;

    /**
     * Last Change ID availble in AbraFlexi
     * @var int
     */
    public $globalVersion = null;

    /**
     * Evidence handler cache
     * @var array
     */
    public $handlerCache = [];

    /**
     * Cache for Notify object
     * @var array
     */
    public $notifiers = [];

    /**
     * Last processed versions
     * @var int
     */
    public $lastProcessedVersions = [];

    /**
     * Database helper
     * @var \Ease\Brick
     */
    public $sqlHelper = null;

    /**
     * Web hook Processor lock file
     * @var string
     */
    protected $lockfile = '/tmp/webhook.lock';

    /**
     * Current SourceID
     * @var int
     */
    private $sourceId;

    /**
     *
     * @var type
     */
    private $myCreateColumn;

    /**
     *
     * @var array
     */
    protected $credentials = [];

    /**
     *
     * @var array
     */
    private $lastProcessedVersion;

    /**
     * Processor engine class
     */
    public function __construct($options = [])
    {
        parent::__construct(null, $options);
        $this->debug = \Ease\Functions::cfg('DEBUG');
        $this->lockfile = sys_get_temp_dir() . '/webhook.lock';
        $this->locked = $this->locked();
        Plugin::loadClassesInDir(__DIR__ . '/Notify');
        $this->loadAbraFlexiServers();
    }

    /**
     * Changes processor
     *
     * @param array $changes
     *
     * @return array list IDS processed
     */
    function processAbraFlexiChanges(array $changes)
    {
        $changepos = 0;
        $doneIDd = [];
        foreach ($changes as $change) {
            $changepos++;
            $evidence = $change['@evidence'];
            $inVersion = intval($change['@in-version']);
            $operation = $change['@operation'];
            $source = $change['@source'];
            $this->sourceId = $change['@sourceid'];
            $id = intval($change['id']);
            $externalIDs = isset($change['external-ids']) ? $change['external-ids'] : [];

            $docId = empty($externalIDs) ? $id : current($externalIDs);

            $handlerClassName = \AbraFlexi\RO::evidenceToClassName($evidence);
            $handlerClass = '\\AbraFlexi\\Processor\\Plugins\\' . $handlerClassName;

            if (class_exists($handlerClass)) {
                $changeMeta = array_merge(
                    \AbraFlexi\RO::companyUrlToOptions($source),
                    [
                            'evidence' => $evidence,
                            'sourceid' => $this->sourceId,
                            'operation' => $operation,
                            'external-ids' => $externalIDs,
                            'changeid' => $inVersion,
                            'user' => $this->credentials[$source]['login'],
                            'password' => $this->credentials[$source]['password'],
                            'debug' => boolval($this->debug)
                        ]
                );

                $saver = $this->getHandler($handlerClass, $docId, $changeMeta);
                if (($saver->lastResponseCode === 200) && $saver->process($operation)) {
                    $ident = \AbraFlexi\RO::uncode($saver->getRecordIdent());
                    if (!empty($ident)) {
                        $id .= ' ' . $ident;
                    }
                    $this->addStatusMessage(
                        sprintf(
                            _('Processing Change %s/%s version %d  ⇶ %s ( %s %s/%s ) Last %d'),
                            $changepos,
                            count($this->changes),
                            $inVersion,
                            $saver->getMetaState(),
                            $operation,
                            $evidence,
                            $id,
                            $this->lastProcessedVersions[$this->sourceId]
                        ),
                        'success'
                    );

                    foreach (\Ease\Functions::classesInNamespace('AbraFlexi\Processor\Notify') as $notifierClass) {
                        if (!array_key_exists($notifierClass, $this->notifiers)) {
                            $toInstance = '\\AbraFlexi\\Processor\\Notify\\' . $notifierClass;
                            $this->notifiers[$notifierClass] = new $toInstance();
                        }
                        $this->notifiers[$notifierClass]->notify($saver);
                    }
                }
            } else {
                if ($this->debug) {
                    $this->addStatusMessage(sprintf(
                        _('Request unexistent module %s for %s'),
                        $handlerClass,
                        $docId
                    ), 'warning');
                }
            }
            $this->wipeCacheRecord($inVersion); //TODO HERE ?
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
     * @return \AbraFlexi\Processor\Plugin
     */
    public function &gethandler($handlerClass, $id, $changeMeta)
    {
        if (isset($this->handlerCache[$handlerClass][$id])) {
            $this->handlerCache[$handlerClass][$id]->loadFromAbraFlexi($id);
        } else {
            $this->handlerCache[$handlerClass][$id] = new $handlerClass(
                $id,
                array_merge($changeMeta, ['database' => $this->database])
            );
            if ($this->handlerCache[$handlerClass][$id]->lastResponseCode != 200) {
                $this->addStatusMessage(sprintf(
                    _('Record %s not found in %s'),
                    $id,
                    json_encode($changeMeta)
                ), 'error');
            }
        }
        $this->handlerCache[$handlerClass][$id]->sourceId = $changeMeta['sourceid'];
        $this->handlerCache[$handlerClass][$id]->setUp($changeMeta);
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
    public function takeApiChanges(array $changes)
    {
        $result = null;
        $changesToLog = [];
        if (array_key_exists('winstrom', $changes)) {
            $this->globalVersion = intval($changes['winstrom']['@globalVersion']);
            $this->changes = \Ease\Functions::reindexArrayBy(
                $changes['winstrom']['changes'],
                '@in-version'
            );

            ksort($this->changes);
        }
        $result = is_numeric($changes['winstrom']['next']) ? $changes['winstrom']['next'] - 1 : $this->globalVersion;

        foreach ($this->changes as $change) {
            $changesToLog[] = $change['@evidence'] . ' ' . $change['@operation'] . ':' . $change['id'] . (empty($change['external-ids']) ? '' : json_encode($change['external-ids']) );
        }

        $this->addStatusMessage(sprintf(
            _('%s Changes to process: %s'),
            count($this->changes),
            implode(',', $changesToLog)
        ), 'debug');

        return $result;
    }

    /**
     * Ulozi posledni zpracovanou verzi
     *
     * @param int $version
     *
     * @return string Restult
     */
    public function saveLastProcessedVersion($version)
    {
        $this->myTable = 'changesapi';
        $this->createColumn = false;
        $this->lastProcessedVersion = $version;
        $this->myCreateColumn = null;
        $result = $this->updateToSQL(['doneid' => $version, 'id' => $this->sourceId]);
        if ($this->debug === true) {
            $this->addStatusMessage(sprintf(
                _("Last Processed Change ID %s saved for %s"),
                $version,
                $this->serverurl()
            ), $result ? 'success' : 'error');
        }
        $this->myTable = 'flexihistory';
        return $result;
    }

    public function serverurl()
    {
        return \Ease\Functions::cfg('ABRAFLEXI_URL') . '/c/' . \Ease\Functions::cfg('ABRAFLEXI_COMPANY');
    }

    /**
     * Nacte posledni zpracovanou verzi
     *
     * @return int $version
     */
    public function getLastProcessedVersion()
    {
        $lastProcessedVersion = null;
        $chRaw = $this->getColumnsFromSQL(
            ['changeid'],
            ['serverurl' => $this->serverurl()]
        );
        if (isset($chRaw[0]['changeid'])) {
            $lastProcessedVersion = intval($chRaw[0]['changeid']);
        } else {
            $this->addStatusMessage(
                _("Last Processed Change ID Loading Failed"),
                'warning'
            );
        }
        return $lastProcessedVersion;
    }

    /**
     * Lock into lock file
     *
     * @return int size of saved lock file in bytes
     */
    public function lock()
    {
        return file_put_contents($this->lockfile, getmypid());
    }

    /**
     * Web hook processor lock check
     *
     * @returm locked by PID
     */
    public function locked()
    {
        return $this->isLocked() ? intval(file_get_contents($this->lockfile)) : 0;
    }

    /**
     *
     * @return boolean
     */
    public function isProcessRunning()
    {
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
    public function isLocked()
    {
        $locked = false;
        $lockfilePresent = file_exists($this->lockfile);
        if ($lockfilePresent) {
            if ($this->isProcessRunning()) {
                $locked = true;
            } else {
                $currentProcessPID = file_get_contents($this->lockfile);
                $locFileAge = time() - filemtime($this->lockfile);
                $this->addStatusMessage(sprintf(
                    'Ophraned lockfile found. pid: %d age: %s s.',
                    $currentProcessPID,
                    $locFileAge
                ), 'error');
                $this->unlock();
            }
        }
        return $locked;
    }

    /**
     * Remove lock file
     */
    public function unlock()
    {
        return file_exists($this->lockfile) ? unlink($this->lockfile) : true;
    }

    /**
     *
     */
    public function loadAbraFlexiServers()
    {
        $this->credentials = \Ease\Functions::reindexArrayBy($this->getFluentPDO()->from('changesapi')->select(
            'id,serverurl,login,password,doneid',
            true
        )->fetchAll(), 'serverurl');
        foreach ($this->credentials as $flexiServer) {
            $this->lastProcessedVersions[$flexiServer['id']] = intval($flexiServer['doneid']);
        }
    }

    /**
     * Load all web hooks from cache and Process
     *
     * @return boolean all changes processed successfully ?
     */
    public function processCachedChanges()
    {
        $result = false;
        $changesRaw = $this->fluent->from('changes_cache')->select('serverurl')->leftJoin('changesapi ON changes_cache.source=changesapi.id')->orderBy('inversion');
        if ($changesRaw->count()) {
            $changesToProcess = [];
            foreach ($changesRaw as $changeId => $changeDataSaved) {
                $changesToProcess[$changeId] = self::sqlColsToJsonCols($changeDataSaved);
            }
            $this->changes = \Ease\Functions::reindexArrayBy(
                $changesToProcess,
                '@in-version'
            );
            $changesDone = $this->processAbraFlexiChanges($this->changes);

            $result = !empty($changesDone);
        } elseif (\Ease\Functions::cfg('APP_DEBUG')) {
            $this->addStatusMessage('No records to process found');
        }
        return $result;
    }

    /**
     * convert $sqlData column names to $jsonData column names
     *
     * @param array $sqlData
     *
     * @return array
     */
    public static function sqlColsToJsonCols($sqlData)
    {
        $jsonData['@in-version'] = $sqlData['inversion'];
        $jsonData['id'] = $sqlData['recordid'];
        $jsonData['@source'] = $sqlData['serverurl'];
        $jsonData['@sourceid'] = intval($sqlData['source']);
        $jsonData['@evidence'] = $sqlData['evidence'];
        $jsonData['@operation'] = $sqlData['operation'];
        $jsonData['external-ids'] = unserialize(stripslashes($sqlData['externalids']));
        return $jsonData;
    }

    /**
     * Save json Data to SQL cache
     *
     * @param array $changes
     *
     * @return int lastChangeID
     */
    public function saveWebhookData($changes)
    {
        $inversion = 0;
        foreach ($changes as $changeId => $apiData) {
            $this->fluent->insertInto('changes_cache')->values(array_merge(['source' => 'abraflexi',
                'target' => 'system'], self::jsonColsToSQLCols($apiData)))->execute();
            $inversion = intval($apiData['@in-version']);
        }
        return $inversion;
    }

    /**
     * convert $jsonData column names to $sqlData column names
     *
     * @param array $apiData
     *
     * @return array
     */
    public static function jsonColsToSQLCols($apiData)
    {
        $sqlData['inversion'] = $apiData['@in-version'];
        $sqlData['recordid'] = $apiData['id'];
        $sqlData['evidence'] = $apiData['@evidence'];
        $sqlData['operation'] = $apiData['@operation'];
        $sqlData['externalids'] = addslashes(serialize(array_key_exists(
            'external-ids',
            $apiData
        ) ? $apiData['external-ids'] : []));
        return $sqlData;
    }

    /**
     * skip web hooks with 'inversion' less or equal to $this->getLastProcessedVersion()
     *
     * @param array $webhookJsonData
     *
     * @return array
     */
    public function onlyFreshHooks($webhooksRawData)
    {
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
    public function wipeCacheRecord($inVersion)
    {
        $this->setMyTable('changes_cache');
        $result = $this->deleteFromSQL(['inversion' => $inVersion, 'source' => $this->sourceId]);
        if ($this->debug === true) {
            $this->addStatusMessage(
                sprintf(
                    _("Cached change wipe %s (%s remain)"),
                    $inVersion,
                    $this->listingQuery()->count()
                ),
                $result ? 'success' : 'error'
            );
        }
        $this->setMyTable('flexihistory');
        return $result;
    }

    public function getLastProcessedVersions()
    {
        return [];
    }
}
