<?php

/**
 * API Handler.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2022 VitexSoftware
 */

namespace AbraFlexi\Processor;

use Ease\Functions;
use Ease\Logger\Logging;
use Tqdev\PhpCrudApi\Config;
use Tqdev\PhpCrudApi\RequestUtils;
use Tqdev\PhpCrudApi\ResponseUtils;

/**
 * Description of Api
 *
 * @author vitex
 */
class Api extends \Tqdev\PhpCrudApi\Api {

    use Logging;

    public function __construct() {
        $config = new Config([
            'driver' => Functions::cfg('DB_TYPE'),
            'address' => Functions::cfg('DB_HOST'),
            'port' => Functions::cfg('DB_PORT'),
            'username' => Functions::cfg('DB_USERNAME'),
            'password' => Functions::cfg('DB_PASSWORD'),
            'database' => Functions::cfg('DB_DATABASE'),
            'debug' => boolval(Functions::cfg('DEBUG')),
            'basePath' => '/EASE/abraflexi-changes-processor/www/'
        ]);
        parent::__construct($config);
    }

    public function logRequest($request) {
        $this->addStatusMessage(RequestUtils::toString($request));
    }

    public function logResponse($response) {
        $this->addStatusMessage(ResponseUtils::toString($response));
    }

}
