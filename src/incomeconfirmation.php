<?php

namespace AbraFlexi\Processor;

/**
 * Meta State Processor.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2022 VitexSoftware
 */
const APP_NAME = 'AbraFlexiIncomeConfirm';

require_once __DIR__.'/../vendor/autoload.php';

Engine::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY',
'EASE_LOGGER', 'SUBJECT','DB_TYPE','DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD'], '../.env');


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
            \Ease\Logger\Regent::singleton()->addStatusMessage(_('Unhandled document type').': '.$subject);
            exit(1);
            break;
    }

    $notifier = new \AbraFlexi\Bricks\PotvrzeniUhrady($engine);

    $engine->addStatusMessage(_('Payment Confirmation sent'),
        $notifier->send() ? 'success' : 'error');
} catch (\AbraFlexi\Exception $exc) {
    
}



