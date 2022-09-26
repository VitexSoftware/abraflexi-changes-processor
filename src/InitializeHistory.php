#!/usr/bin/php -f
<?php

namespace AbraFlexi\Processor;

/**
 * System.Spoje.Net - WebHook Acceptor & Saver to SQL Cache.
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2017-2020 Spoje.Net 2021-2022 VitexSoftware
 */
define('APP_NAME', 'HistoryInitializer');
require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists('../.env')) {
    \Ease\Shared::singleton()->loadConfig('../.env', true);
}

$changesApi = new ChangesApi();
$sourceId = $changesApi->getSourceId();
if (empty($sourceId)) {
    $sourceId = $changesApi->registerApi();
}
$prehistoric = new FlexiHistory(null, ['operation' => 'import']);

if ($sourceId) {
    $prehistoric->importHistory($sourceId);
} else {
    $prehistoric->addStatusMessage(_('Source not found in database.'), 'error');
}
