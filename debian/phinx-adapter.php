<?php

/**
 * Abra Flexi ChangesProcessor - Phinx database adapter.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2021-2022 Vitex Software
 */


require_once '/var/lib/composer/abraflexi-changes-processor/autoload.php';

$cfg = '/etc/abraflexi-changes-processor/.env';
if(file_exists($cfg)){
    \Ease\Shared::singleton()->loadConfig($cfg, true);
}

$prefix = file_exists('./db/') ? './db/' : '../db/';

$sqlOptions = [];

if (strstr(\Ease\Functions::cfg('DB_CONNECTION'), 'sqlite')) {
    $sqlOptions['database'] = __DIR__ . '/' . basename(\Ease\Functions::cfg('DB_DATABASE'));
    if (!file_exists($sqlOptions['database'])) {
        file_put_contents($sqlOptions['database'], '');
    }
}
$engine = new \Ease\SQL\Engine(null, $sqlOptions);
$cfg = [
    'paths' => [
        'migrations' => [$prefix . 'migrations'],
        'seeds' => [$prefix . 'seeds/']
    ],
    'environments' =>
    [
        'default_database' => 'production',
        'production' => [
            'adapter' => \Ease\Functions::cfg('DB_TYPE'),
            'name' => $engine->database,
            'connection' => $engine->getPdo($sqlOptions)
        ]
    ]
];

return $cfg;
