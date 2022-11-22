<?php

namespace AbraFlexi\Processor;

/**
 * Meta State Processor.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2022 VitexSoftware
 */
define('APP_NAME', 'AbraFlexiIncomeConfirm');
require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists('../.env')) {
    \Ease\Shared::singleton()->loadConfig('../.env', true);
}


define('ID', 87);
define('URI', 'https://flexibee-dev.spoje.net:5434/c/spoje_net_s_r_o_/banka/code:BENCHMARK:1668729004');
define('META', 'income');
define('DISCOVERED', '2022-11-18 01:20:50');
define('PROCESSED', '');
define('CHANGEID', '4751744');
define('DOCUMENTID', 'code:BENCHMARK:1668729004');
define('SUBJECT', 'banka');
define('COMPANY', 'spoje_net_s_r_o_');
define('COMPANYURI', 'https://flexibee-dev.spoje.net:5434/c/spoje_net_s_r_o_');
define('URL', 'https://flexibee-dev.spoje.net:5434');
define('SCHEME', 'https');
define('HOST', 'flexibee-dev.spoje.net');
define('PORT', '5434');
define('LOGIN', 'admin');
define('PASSWORD', 'Ekhuors3');
define('DONEID', '4757321');
define('SOURCEID', '1');
define('EMAIL', '');
define('EASE_MAILTO', '');





foreach (['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY', 'EASE_LOGGER', 'SUBJECT'] as $cfgKey) {
    if (empty(\Ease\Functions::cfg($cfgKey))) {
        echo 'Requied configuration ' . $cfgKey . ' is not set.';
        exit(1);
    }
}

if ($argc > 1) {
    $docId = $argv[1];
} else {
    $docId = \Ease\Functions::cfg('DOCUMENTID');
}

$subject = \Ease\Functions::cfg('SUBJECT');

try {
    switch ($subject) {
        case 'banka':
            $engine = new \AbraFlexi\Banka($docId);
            break;
        case 'pokladna':
            $engine = new \AbraFlexi\Pokladna($docId);
            break;
        default:
            \Ease\Logger\Regent::singleton()->addStatusMessage(_('Unhandled document type') . ': ' . $subject);
            exit(1);
            break;
    }
} catch (\AbraFlexi\Exception $exc) {
    
}



$notifier = new \AbraFlexi\Bricks\PotvrzeniUhrady($engine);

$engine->addStatusMessage(_('Payment Confirmation sent'), $notifier->send() ? 'success' : 'error');

