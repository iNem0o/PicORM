<?php
/**
 * This file is part of PicORM.
 *
 * PicORM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PicORM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with PicORM.  If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5.4
 *
 * @category Core
 * @package  PicORM
 * @author   iNem0o <contact@inem0o.fr>
 * @license  LGPL http://opensource.org/licenses/lgpl-license.php
 * @link     https://github.com/iNem0o/PicORM
 */
namespace PicORM;

/**
 * Class PicORM
 * Static class to configure libraries from user option
 *
 * @category Core
 * @package  PicORM
 * @author   iNem0o <contact@inem0o.fr>
 * @license  LGPL http://opensource.org/licenses/lgpl-license.php
 * @link     https://github.com/iNem0o/PicORM
 */
class PicORM
{
    /**
     * Datasource instance
     *
     * @var \PDO
     */
    protected static $_dataSource;

    /**
     * Configuration array
     *
     * @var array
     */
    protected static $_configuration;

    /**
     * Default PicORM configuration
     *
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

    /**
     * Return main datasource extracted from configuration
     * @return \PDO
     */
    public static function getDataSource()
    {
        return static::$_dataSource;
    }
}