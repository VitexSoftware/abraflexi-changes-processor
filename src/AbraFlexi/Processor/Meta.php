<?php

/**
 * Meta State Processor.
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2022 VitexSoftware
 */

namespace AbraFlexi\Processor;

/**
 * Description of Meta
 *
 * @author vitex
 */
class Meta extends Engine
{
    /**
     * We work with table meta
     * @var string
     */
    public $myTable = 'meta';

    /**
     * Webhook Processor lockfile
     * @var string
     */
    protected $lockfile = '/tmp/meta.lock';

    /**
     *
     * @return type
     */
    public function unprocessed()
    {
        return $this->listingQuery()->where('processed IS NULL')->where('after IS NULL OR after > NOW()')->orderBy('id');
    }

    /**
     *
     * @return type
     */
    public function firstUnprocessed()
    {
        return $this->unprocessed()->limit(1);
    }

    /**
     * Handle Metastate
     *
     * @param array $meta
     *
     * @return type
     */
    public function handle($meta)
    {
        $result = [];
        $this->setMyKey($meta['id']);
        $this->setObjectName();
        $components = parse_url($meta['uri']);
        $pathParts = explode('/', $components['path']);
        $meta['documentID'] = urldecode($pathParts[4]);
        $meta['subject'] = $pathParts[3];
        $meta['company'] = $pathParts[2];
        $meta['companyuri'] = $components['scheme'] . '://' . $components['host'] . ':' . $components['port'] . '/c/' . $meta['company'];
        $meta['url'] = $components['scheme'] . '://' . $components['host'] . ':' . $components['port'];
        $meta = array_merge($meta, $components);

        if (array_key_exists($meta['companyuri'], $this->credentials)) {
            $meta['login'] = $this->credentials[$meta['companyuri']]['login'];
            $meta['password'] = $this->credentials[$meta['companyuri']]['password'];
            $meta['doneid'] = $this->credentials[$meta['companyuri']]['doneid'];
            $meta['sourceid'] = $this->credentials[$meta['companyuri']]['id'];
        }

        $rules = $this->getRulesFor($meta);
        $commands = $this->getCommandsFor($rules);

        if ($commands) {
            foreach ($commands as $command) {
                $result[$command] = $this->executeCommand($command, $meta);
            }
        }
        $this->setMetaProcessed($meta['id']);
        return $result;
    }

    /**
     * Set Meta Record as processed
     *
     * @param int $metaID
     *
     * @return type
     */
    public function setMetaProcessed($metaID)
    {
        return $this->getFluentPDO()->update($this->getMyTable())->set(
            'processed',
            new \Envms\FluentPDO\Literal('NOW()')
        )->where('id', $metaID)->execute();
    }

    /**
     *
     */
    public function processMetas()
    {
        foreach ($this->unprocessed() as $meta) {
            $this->handle($meta);
        }
    }

    /**
     * Obtaing Commands to handle given meta state
     *
     * @param array $meta
     *
     * @return array of commands
     */
    public function getRulesFor($meta)
    {

//    [id] => 1
//    [uri] => https://flexibee-dev.spoje.net:5434/c/spoje_net_s_r_o_/faktura-vydana/code:VF1-4698%2F2022
//    [meta] => penalised
//    [discovered] => 2022-09-29 01:25:55
//    [processed] =>
//    [documentID] => code:VF1-4698/2022
//    [subject] => faktura-vydana
//    [company] => spoje_net_s_r_o_
//    [scheme] => https
//    [host] => flexibee-dev.spoje.net
//    [port] => 5434
//    [path] => /c/spoje_net_s_r_o_/faktura-vydana/code:VF1-4698%2F2022
//+----+---------------------+------+--------------------+--------+-------------------------+
//| id | company             | host | subject            | meta   | command                 |
//+----+---------------------+------+--------------------+--------+-------------------------+
//|  1 | *                   | *    | adresar            | SMS    | abraflexi-send-sms      |
//|  2 | image_office_s_r_o_ | *    | objednavka-prijata | NOTE   | image-office-note-state |
//|  3 | *                   | *    | adresar            | create | abraflexi2office365     |
//|  4 | *                   | *    | adresar            | update | abraflexi2office365     |
//+----+---------------------+------+--------------------+--------+-------------------------+

        $rules = $this->getFluentPDO()->from('rules')->select('command', true)
                ->where("host", [$meta['host'], '-'])
                ->where("company", [$meta['company'], '-'])
                ->where("subject", [$meta['subject'], '-'])->where(
                    'meta',
                    $meta['meta']
                )->disableSmartJoin();

        return $rules;
    }

    /**
     * Get Commands for query
     *
     * @param \Envms\FluentPDO\Query $rules
     *
     * @return array
     */
    public function getCommandsFor($rules)
    {
        $commands = [];
        while ($command = $rules->fetch()) {
            $commands[] = $command['command'];
        }
        return $commands;
    }

    /**
     * Run Command
     *
     * @param string $command
     * @param array $meta Command Metainfo
     *
     * @return type
     */
    public function executeCommand($command, $meta)
    {
        $stdout = '';
        $stderr = '';
        $meta['email'] = '';

        $envNames = [
            'ABRAFLEXI_URL' => $meta['url'],
            'ABRAFLEXI_LOGIN' => $meta['login'],
            'ABRAFLEXI_PASSWORD' => $meta['password'],
            'ABRAFLEXI_COMPANY' => $meta['company'],
            'EASE_MAILTO' => $meta['email'],
            'EASE_LOGGER' => empty($meta['email']) ? 'syslog' : 'syslog|email',
            'PATH' => \Ease\Functions::cfg('PATH', '/usr/bin:/usr/local/bin')
        ];

        foreach (array_merge($meta, $envNames) as $envName => $sqlValue) {
            $this->addStatusMessage(sprintf(
                _('Setting Environment %s to %s'),
                strtoupper($envName),
                $sqlValue
            ), 'debug');
            putenv(strtoupper($envName) . '=' . $sqlValue);
        }

        $exec = $command;
        $cmdparams = '';
        if ($this->debug) {
            $this->addStatusMessage('start: ' . $exec . ' ' . $cmdparams, 'debug');
        }
        $this->addStatusMessage(
            $exec . ' done',
            $this->shellExec($exec . ' ' . $cmdparams, $stdout, $stderr) ? 'warning'
            : 'success'
        );

        if ($stdout) {
            foreach (explode("\n", $stdout) as $row) {
                $this->addStatusMessage($row, 'success');
            }
        }
        if ($stderr) {
            foreach (explode("\n", $stderr) as $row) {
                $this->addStatusMessage($row, 'error');
            }
        }

        if ($this->debug) {
            $this->addStatusMessage('end: ' . $exec, 'debug');
        }
        return $command;
    }

    /**
     * Perform subcommand
     *
     * @param string $cmd
     * @param string $stdout
     * @param string $stderr
     *
     * @return int
     */
    function shellExec($cmd, &$stdout = null, &$stderr = null)
    {
        $proc = proc_open(
            $cmd,
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        return proc_close($proc);
    }

    /**
     *
     *
     * @param array $newItemData
     *
     * @return int
     */
    public function insertItem(array $newItemData)
    {
        $jobId = $this->insertToSQL($newItemData);
        if (array_key_exists('job', $newItemData) === false) {
            $this->updateToSQL(['id' => $jobId, 'job' => $jobId]);
        }
        return $jobId;
    }

    /**
     *
     * @param \AbraFlexi\RO $afrecord
     * @param string $metaState
     *
     * @return type
     */
    public function insertObject(
        \AbraFlexi\RO $afrecord,
        string $metaState,
        $changeid = 0
    ) {
        return $this->insertItem(['uri' => $afrecord->getApiURL(), 'meta' => $metaState,
                'changeid' => $changeid]);
    }
}
