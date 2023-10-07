<?php

namespace AbraFlexi\Processor;

/**
 * WebHook Acceptor & Saver to SQL Cache.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2022 Spoje.Net, 2021-2023 VitexSoftware
 */

define('APP_NAME', 'AbraFlexiChangesProcessor');
require_once __DIR__ . '/../vendor/autoload.php';

Engine::init(['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'], '../.env');

$hooker = new ChangesApi();
if (\Ease\Functions::cfg('APP_DEBUG')) {
    $hooker->logBanner(\Ease\Shared::appName());
}

if (\Ease\Functions::cfg('PROCESSING_ENABLED') == 'True') {
    $lockerPid = $hooker->locked();
    if ($lockerPid == 0) {
        $hooker->lock();
        $hooker->processCachedChanges();
        $hooker->unlock();
    } else {
        $hooker->addStatusMessage(sprintf(
            _('Waiting for PID %d to be done'),
            $lockerPid
        ), 'debug');
    }
} else {
    $hooker->addStatusMessage(_('Changes processing is disabled. (set PROCESSING_ENABLED=True to enable)'), 'warning');
}
