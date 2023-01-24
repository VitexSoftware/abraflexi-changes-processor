<?php

namespace AbraFlexi\Processor;

/**
 * Meta State Processor.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2022 VitexSoftware
 */
define('APP_NAME', 'MetaExecutor');
require_once __DIR__ . '/../vendor/autoload.php';

Engine::init(['DB_TYPE','DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD'], '../.env');

$metar = new Meta();

if (\Ease\Functions::cfg('META_PROCESSING_ENABLED') == 'True') {
    $lockerPid = $metar->locked();
    if ($lockerPid == 0) {
        $metar->lock();
        $metar->processMetas();
        $metar->unlock();
    } else {
        $metar->addStatusMessage(sprintf(_('Waiting for PID %d to be done'),
                        $lockerPid), 'debug');
    }
} else {
    $metar->addStatusMessage(_('Changes processing is disabled. (set META_PROCESSING_ENABLED=True to enable)'), 'warning');
}
