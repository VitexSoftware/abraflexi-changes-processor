<?php

namespace AbraFlexi\Processor;

/**
 * AbraFlexi document Analyzer.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2023 VitexSoftware
 */
define('APP_NAME', 'AbraFlexiChangesProcessor');
require_once __DIR__.'/../vendor/autoload.php';

if (file_exists('../.env')) {
    \Ease\Shared::singleton()->loadConfig('../.env', true);
}

foreach (['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY',
'EASE_LOGGER', 'SUBJECT', 'DB_CONNECTION', 'DB_DATABASE'] as $cfgKey) {
    if (empty(\Ease\Functions::cfg($cfgKey))) {
        echo 'Requied configuration '.$cfgKey.' is not set.';
        exit(1);
    }
}

if ($argc > 1) {
    $docId = $argv[1];
} else {
    $docId = \Ease\Functions::cfg('DOCUMENTID');
}
$subject = \Ease\Functions::cfg('SUBJECT');

$engine = new Document($docId, ['evidence' => $subject]);
if (\Ease\Functions::cfg('APP_DEBUG')) {
    $engine->logBanner(\Ease\Shared::appName());
}

$metaEngine = new Meta();

foreach ($engine->getSubObjects() as $subObject) {
    // LABEL ID ?
    $labels = $subject->getLabels();
    if (is_array($labels)) {
        $meta = 'UHRADA_API_POLOZKY_DOKLADU';
        foreach (preg_grep('/\b$API\b/i', $labels) as $label) {
            $metaData = ['uri' => $subObject->getApiURL(), 'meta' => $meta, 'properties' => json_encode([
                    'LABEL' => $label])];
            $metaEngine->addStatusMessage('Emit META by product '.$subObject->getRecordCode().' attribute : '.$metaData['uri'].'#'.$meta,
                $metaEngine->insertItem($metaData) ? 'success' : 'error');
        }
    }
    // MEMBER OF Subtree
    //https://flexibee-dev.spoje.net:5434/c/spoje_net_s_r_o_/strom.json?detail=full
    //https://podpora.flexibee.eu/cs/articles/4722195-filtrovani-zaznamu
    // Executor atribut set
    $meta = $engine->getMetaForProduct($subObject->getRecordCode());
    if ($meta) {
        $metaData = ['uri' => $subObject->getApiURL(), 'meta' => $meta];
        $metaEngine->addStatusMessage('Emit META by product '.$subObject->getRecordCode().' attribute : '.$metaData['uri'].'#'.$meta,
            $metaEngine->insertItem($metaData) ? 'success' : 'error');
    }
    //print_r($subObject->getData());
    // TODO: Skupina Zbozi
}
