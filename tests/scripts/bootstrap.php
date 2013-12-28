<?php
require __DIR__ . '/../../src/autoload.inc.php';
$config = require __DIR__ . '/../config.inc.php';

include_once __DIR__ . '/tested_models.php';

try {
    $pdo = new \PDO(
        'mysql:dbname='.$config['database_name'].';host='.$config['database_host'],
        $config['database_user'],
        $config['database_password']
    );
} catch(Exception $e) {
    exit($e->getMessage());
}

\PicORM\PicORM::configure(array(
    'datasource' => $pdo
));

