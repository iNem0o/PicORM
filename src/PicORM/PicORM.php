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
        'cache' => false, // !!TODO!!
        'datasource' => null
    );

    /**
     * Set PicORM global configuration
     * @param array $configuration
     */
    final public static function configure(array $configuration)
    {
        // override with default configuration if not present
        $configuration += static::$_defaultConfiguration;

        if (isset($configuration['datasource']) && $configuration['datasource'] instanceof \PDO) {
            static::$_dataSource = $configuration['datasource'];
            Entity::setDataSource(static::$_dataSource);
        }

        static::$_configuration = $configuration;
    }
}