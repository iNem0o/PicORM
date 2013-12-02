<?php

    if(!file_exists("mageekguy.atoum.phar"))
        file_put_contents('mageekguy.atoum.phar',
                          file_get_contents("http://downloads.atoum.org/nightly/mageekguy.atoum.phar")
        );

    print `php mageekguy.atoum.phar -bf scripts/bootstrap.php -d units`;
