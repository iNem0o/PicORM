<?php
    $atoumPath = __DIR__.'/mageekguy.atoum.phar';
    $bootstrapPath = __DIR__.'/scripts/bootstrap.php';
    $unitsFolderPath = __DIR__.'/units';

    if(!file_exists($atoumPath))
        file_put_contents($atoumPath,
            file_get_contents("http://downloads.atoum.org/nightly/mageekguy.atoum.phar")
        );


    print `php $atoumPath -bf $bootstrapPath -d $unitsFolderPath`;
