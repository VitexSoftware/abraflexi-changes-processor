<?php
/**
 * API Handler.
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2022 VitexSoftware
 */

namespace AbraFlexi\Processor;

use Ease\Functions;
use Ease\Logger\Logging;
use Tqdev\PhpCrudApi\Api as CrudApi;
use Tqdev\PhpCrudApi\Config\Config;
use Tqdev\PhpCrudApi\RequestUtils;
use Tqdev\PhpCrudApi\ResponseUtils;

/**
 * Description of Api
 *
 * @author vitex
 */
class Api extends CrudApi
{

    use Logging;

    public function __construct()
    {
        $config = new Config([
            'driver' => Functions::cfg('DB_TYPE'),
            'address' => Functions::cfg('DB_HOST'),
            'port' => Functions::cfg('DB_PORT'),
            'username' => Functions::cfg('DB_USERNAME'),
            'password' => Functions::cfg('DB_PASSWORD'),
            'database' => Functions::cfg('DB_DATABASE'),
            'debug' => boolval(Functions::cfg('DEBUG')),
            'basePath' => \Ease\Functions::cfg('BASEPATH',
                '/EASE/abraflexi-changes-processor/www/')  // parse_url(\Ease\WebPage::phpSelf(),PHP_URL_PATH)
        ]);
        parent::__construct($config);
    }

    public function logRequest($request)
    {
        $this->addStatusMessage(RequestUtils::toString($request));
    }

    public function logResponse($response)
    {
        $this->addStatusMessage(ResponseUtils::toString($response));
    }
}