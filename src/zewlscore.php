<?php

namespace AbraFlexi\Processor;

/**
 * CustomerScore obtainer
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2022 VitexSoftware
 */

define('APP_NAME', 'AbraFlexiIncomeConfirm');
require_once __DIR__ . '/../vendor/autoload.php';

Engine::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY', 'EASE_LOGGER', 'SUBJECT'], '../.env');

if ($argc > 1) {
    $docId = $argv[1];
} else {
    $docId = \Ease\Functions::cfg('DOCUMENTID');
}

$engine = new \AbraFlexi\Bricks\Customer($docId);
$zewlScore = $engine->getCustomerScore();

$engine->addStatusMessage(_('Customer %s score: %s'), $engine->adresar->getRecordCode(), $zewlScore);

echo $zewlScore;
