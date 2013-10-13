<?php
require __DIR__.'/../src/autoload.inc.php';

$pdo = new \PDO('mysql:dbname=test2;host=localhost', 'root', 'root');

\PicORM\PicORM::configure(array(
    'datasource' => $pdo
));

\PicORM\Entity::getDataSource()->query('TRUNCATE brands');
\PicORM\Entity::getDataSource()->query('TRUNCATE cars');
\PicORM\Entity::getDataSource()->query('TRUNCATE car_have_tag');
\PicORM\Entity::getDataSource()->query('TRUNCATE tags');

require 'mageekguy.atoum.phar';
