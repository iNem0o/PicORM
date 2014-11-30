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
 * @category Query
 * @package  PicORM
 * @author   iNem0o <contact@inem0o.fr>
 * @license  LGPL http://opensource.org/licenses/lgpl-license.php
 * @link     https://github.com/iNem0o/PicORM
 */
namespace PicORM;

/**
 * Class InternalQueryHelper
 * Extends raw QueryBuilder class to add some PicORM logic
 *
 * @category Query
 * @package  PicORM
 * @author   iNem0o <contact@inem0o.fr>
 * @license  LGPL http://opensource.org/licenses/lgpl-license.php
 * @link     https://github.com/iNem0o/PicORM
 */
class InternalQueryHelper extends QueryBuilder
{
    /**
     * Values of where before sending to querybuilder
     *
     * @var array
     */
    protected $_whereValues;


    /**
     * Prefix fields from where associated array
     * with table name
     *
     * @param array  $data      - associative array with where data
     * @param string $tableName - table name to use as prefix
     *
     * @return mixed
     */
    public function prefixWhereWithTable($data, $tableName)
    {
        if (count($data) == 0) {
            return $data;
        }

        foreach ($data as $key => $v) {
            $newKey        = $tableName . '.' . trim($key, '`');
            $data[$newKey] = $v;
            unset($data[$key]);
        }

        return $data;
    }


    /**
     * Prefix fields from orderBy associated array
     * with table name
     *
     * @param array  $data      - associative array with order data
     * @param string $tableName - table name to use as prefix
     *
     * @return mixed
     */
    public function prefixOrderWithTable($data, $tableName)
    {
        if (count($data) == 0) {
            return $data;
        }

        $tableName = trim($tableName, '`');
        foreach ($data as $key => $v) {
            // if $v is empty, we have a custom order like RAND() and do not have to prefix
            if (!empty($v)) {
                $newKey        = $tableName . '.' . trim($key, '`');
                $data[$newKey] = $v;
                unset($data[$key]);
            }
        }

        return $data;
    }


    /**
     * Build where condition from find() $where params
     *
     * @param array $where - associative array with where needed values
     *
     * @return $this
     */
    public function buildWhereFromArray(array $where)
    {
        if (count($where) == 0) {
            return $this->_whereValues;
        }

        foreach ($where as $fieldName => $oneCritera) {
            $val      = '?';
            $operator = "=";
            if (is_array($oneCritera)) {
                // using raw mysql
                if (count($oneCritera) == 1 && isset($oneCritera[0])) {
                    $val = $oneCritera[0];
                } else {
                    // custom operator
                    if (isset($oneCritera['operator'])) {
                        $operator = $oneCritera['operator'];
                    }
                    // custom value with prepared data or raw mysql if value is in an array
                    if (isset($oneCritera['value'])) {
                        if (is_array($oneCritera['value']) && isset($oneCritera['value'][0])) {
                            $val = $oneCritera['value'][0];
                        } else {
                            $this->_whereValues[] = $oneCritera['value'];
                        }
                    }
                }
            } else {
                $this->_whereValues[] = $oneCritera;
            }
            $this->where($fieldName, $operator, $val);
        }

        return $this->_whereValues;
    }


    /**
     * Return where values to prepare query
     *
     * @return mixed
     */
    public function getWhereParamsValues()
    {
        return $this->_whereValues;
    }


    /**
     * Clean query before switching to another type in collections
     *
     * @return $this
     */
    public function cleanQueryBeforeSwitching()
    {
        // clean join created by collection for fetching auto get field
        $this->_join = array();

        return $this;
    }
}