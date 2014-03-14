<?php
    $atoumPath = __DIR__.'/mageekguy.atoum.phar';
    $bootstrapPath = __DIR__.'/scripts/bootstrap.php';
    $unitsFolderPath = __DIR__.'/units';

    if(!file_exists($atoumPath)) {
        chdir(__DIR__);
        print `curl -s https://raw.github.com/atoum/atoum-installer/master/installer | php -- --phar`;
    }

    print `php $atoumPath -bf $bootstrapPath -d $unitsFolderPath`;