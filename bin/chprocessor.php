<?php

namespace AbraFlexi\Processor;

/**
 * WebHook Acceptor & Saver to SQL Cache.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2020 Spoje.Net, 2021-2022 VitexSoftware
 */
define('APP_NAME', 'AbraFlexiChangesProcessor');
define('EASE_LOGGER', 'console|syslog');
require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists('../.env')) {
    \Ease\Shared::singleton()->loadConfig('../.env', true);
}

$hooker = new Engine();
$hooker->logBanner();
//$hooker->debug = true;
//$hooker->debug = true;
$lockerPid = $hooker->locked();
if ($lockerPid == 0) {
    $hooker->lock();
    $hooker->processCachedChanges();
    $hooker->unlock();
} else {
    $hooker->addStatusMessage(sprintf(_('Waiting for PID %d to be done'),
                    $lockerPid), 'debug');
}
