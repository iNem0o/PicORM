<?php
require __DIR__ . '/../../src/autoload.inc.php';

include_once __DIR__ . '/raw_models.php';
try {
    $pdo = new \PDO('mysql:dbname=testspicorm;host=localhost', 'root', 'root');
} catch(\PicORM\Exception $e) {
    exit($e->getMessage());
}

\PicORM\PicORM::configure(array(
    'datasource' => $pdo
));