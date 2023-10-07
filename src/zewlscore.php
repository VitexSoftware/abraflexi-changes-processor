<?php

namespace AbraFlexi\Processor;

/**
 * CustomerScore obtainer
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2022-2023 VitexSoftware
 */

define('APP_NAME', 'AbraFlexiIncomeConfirm');
require_once __DIR__ . '/../vendor/autoload.php';

\Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY', 'EASE_LOGGER', 'SUBJECT'], '../.env');

if ($argc > 1) {
    $docId = $argv[1];
} else {
    $docId = \Ease\Functions::cfg('DOCUMENTID');
}

// Throw error if no document ID is provided
if (empty($docId)) {
    throw new \Ease\Exception(_('No Customer ID "DOCUMENTID" provided'));
}

$engine = new \AbraFlexi\Bricks\Customer($docId);
$zewlScore = $engine->getCustomerScore();

$engine->addStatusMessage(_('Customer %s score: %s'), $engine->adresar->getRecordCode(), $zewlScore);

echo $zewlScore;
