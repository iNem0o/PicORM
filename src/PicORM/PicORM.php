<?php
namespace PicORM;

class Exception extends \Exception
{
}

/**
 * Class PicORM
 * @package PicORM
 */
class PicORM
{
    /**
     * Datasource instance
     * @var \PDO
     */
    protected static $_dataSource;
    /**
     * Configuration array
     * @var \PDO
     */
    protected static $_configuration;

    /**
     * Default PicORM configuration
     * @var array
     */
    protected static $_defaultConfiguration = array(
        'cache'      => false, // !!TODO!!
        'datasource' => null
    );

    /**
     * Set PicORM global configuration
     *
     * @param array $configuration
     *
     * @throws Exception
     */
    final public static function configure(array $configuration)
    {
        // override with default configuration if not present
        $configuration += static::$_defaultConfiguration;

        // test if datasource is a PDO instance
        if ($configuration['datasource'] === null || !$configuration['datasource'] instanceof \PDO) {
            throw new Exception("PDO Datasource is required!");
        }

        // set global datasource for all model
        static::$_dataSource = $configuration['datasource'];
        Model::setDataSource(static::$_dataSource);

        // store PicORM configuration
        static::$_configuration = $configuration;
    }
}