<?php

namespace AbraFlexi\Processor;

/**
 * Settled invoice Analyser
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2022 VitexSoftware
 */
define('APP_NAME', 'SettledInvoice');
require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists('../.env')) {
    \Ease\Shared::singleton()->loadConfig('../.env', true);
}

$invoicer = new Plugins\FakturaVydana(\Ease\Functions::cfg('DOCUMENTID'), []);
if (\Ease\Functions::cfg('APP_DEBUG') == 'True') {
    $invoicer->logBanner(\Ease\Shared::appName());
}

$subItems = $invoicer->getSubItems();

if ($subItems) {
    $metar = new Meta();
    foreach ($subItems as $subItem) {
        if ($subItem['typPolozkyK'] == "typPolozky.katalog") {
            $metar->insertObject(new \AbraFlexi\FakturaVydanaPolozka((int) $subItem['id'], ['autoload' => false]), 'settled', \Ease\Functions::cfg('CHANGEID', 0));
        }
    }
}
