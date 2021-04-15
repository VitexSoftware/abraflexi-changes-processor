<?php

namespace AbraFlexi\Processor;

/**
 * System.Spoje.Net - WebHook Acceptor & Saver to SQL Cache.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2020 Spoje.Net
 */
define('APP_NAME', 'WebHookAcceptor');
define('EASE_LOGGER', 'console|syslog');
require_once __DIR__ . '/../vendor/autoload.php';

\Ease\Shared::singleton()->loadConfig('../.env', true);

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
