<?php
    if(!file_exists("mageekguy.atoum.phar"))
        file_put_contents('mageekguy.atoum.phar',
                          file_get_contents("http://downloads.atoum.org/nightly/mageekguy.atoum.phar")
        );

    include('scripts/bootstrap.php');

    \PicORM\Entity::getDataSource()->query(file_get_contents('scripts/testschema.sql'));

    \PicORM\Entity::getDataSource()->query('TRUNCATE brands');
    \PicORM\Entity::getDataSource()->query('TRUNCATE cars');
    \PicORM\Entity::getDataSource()->query('TRUNCATE car_have_tag');
    \PicORM\Entity::getDataSource()->query('TRUNCATE tags');

    print `php mageekguy.atoum.phar -bf scripts/bootstrap.php -d units`;
