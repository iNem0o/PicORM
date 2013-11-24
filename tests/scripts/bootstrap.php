<?php
require __DIR__ . '/../../src/autoload.inc.php';

include_once __DIR__ . '/tested_models.php';

try {
    $pdo = new \PDO('mysql:dbname=testspicorm;host=localhost', 'root', 'root');
} catch(Exception $e) {
    exit($e->getMessage());
}

$pdo->query(file_get_contents('scripts/tested_schema.sql'));

\PicORM\PicORM::configure(array(
    'datasource' => $pdo
));

