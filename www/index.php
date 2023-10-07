<?php

namespace Tqdev\PhpCrudApi;

use AbraFlexi\Processor\Api;
use Tqdev\PhpCrudApi\RequestFactory;
use Tqdev\PhpCrudApi\ResponseUtils;

/**
 * System.Spoje.Net - API
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2021-2023 Spoje.Net
 */
define('APP_NAME', 'AbraFlexiChangesProcessor');
define('EASE_LOGGER', 'console|syslog');
require_once __DIR__ . '/../vendor/autoload.php';

\Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY',
'EASE_LOGGER', 'SUBJECT', 'DB_CONNECTION', 'DB_DATABASE'], '../.env');

$request = RequestFactory::fromGlobals();
$api = new Api();
$response = $api->handle($request);
ResponseUtils::output($response);
$api->logRequest($request);
$api->logResponse($response);
