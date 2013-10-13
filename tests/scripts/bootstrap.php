<?php
require __DIR__ . '/../../src/autoload.inc.php';

$pdo = new \PDO('mysql:dbname=test2;host=localhost', 'root', 'root');

\PicORM\PicORM::configure(array(
    'datasource' => $pdo
));