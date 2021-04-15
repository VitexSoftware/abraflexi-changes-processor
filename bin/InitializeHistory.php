#!/usr/bin/php -f
<?php

namespace AbraFlexi\Processor;

/**
 * System.Spoje.Net - WebHook Acceptor & Saver to SQL Cache.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2020 Spoje.Net
 */
define('APP_NAME', 'HistoryInitializer');
define('EASE_LOGGER', 'console|syslog');
require_once __DIR__ . '/../vendor/autoload.php';

\Ease\Shared::singleton()->loadConfig('../.env', true);

$prehistoric = new FlexiHistory(null, ['operation' => 'import']);
$prehistoric->importHistory();
