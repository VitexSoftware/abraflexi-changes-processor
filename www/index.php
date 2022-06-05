<?php

namespace Tqdev\PhpCrudApi;

use AbraFlexi\Processor\Api;
use Tqdev\PhpCrudApi\RequestFactory;
use Tqdev\PhpCrudApi\ResponseUtils;

/**
 * System.Spoje.Net - API
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2021 Spoje.Net
 */
define('APP_NAME', 'AbraFlexiChangesProcessor');
define('EASE_LOGGER', 'console|syslog');
require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists('../.env')) {
    \Ease\Shared::singleton()->loadConfig('../.env', true);
}


$request = RequestFactory::fromGlobals();
$api = new Api();
$response = $api->handle($request);
ResponseUtils::output($response);
$api->logRequest($request);
$api->logResponse($response);
