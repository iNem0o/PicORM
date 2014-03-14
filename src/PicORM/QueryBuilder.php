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
 * Class QueryBuilder
 * Allow you to easily build MySQL query using PHP Object
 *
 * @category Query
 * @package  PicORM
 * @author   iNem0o <contact@inem0o.fr>
 * @license  LGPL http://opensource.org/licenses/lgpl-license.php
 * @link     https://github.com/iNem0o/PicORM
 */
class QueryBuilder
{
    /**
     * Fields names (UPDATE)
     *
     * @var array
     */
    protected $_setName = array();

    /**
     * Fields values (UPDATE)
     *
     * @var array
     */
    protected $_setVal = array();

    /**
     * Insert VALUES (UPDATE or INSERT)
     *
     * @var array
     */
    protected $_insertValues = array();

    /**
     * Temp insert values
     *
     * @var array
     */
    protected $_lastInsertValues = array();


    /**
     * Marker for SELECT query type
     */
    const SELECT = 1;

    /**
     * Marker for DELETE query type
     */
    const DELETE = 2;

    /**
     * Marker for UPDATE query type
     */
    const UPDATE = 3;

    /**
     * Marker for INSERT query type
     */
    const INSERT = 4;


    /**
     * Query TYPE (UPDATE|INSERT|DELETE|SELECT)
     *
     * @var
     */
    protected $_queryType;

    /**
     * Query hint (ex : SQL_NO_CACHE)
     * @var array
     */
    protected $_queryHint = array();

    /**
     * Fields to select
     * @var array
     */
    protected $_select = array();

    /**
     * Table name to insert
     * @var string
     */
    protected $_insert = '';

    /**
     * Table name to update
     * @var string
     */
    protected $_update = '';

    /**
     * Table name to delete from
     * @var string
     */
    protected $_delete = '';

    /**
     * Tables to select from
     * @var string
     */
    protected $_from = '';

    /**
     * Join definition
     * @var array
     */
    protected $_join = array();

    /**
     * Where criteria
     * @var array
     */
    protected $_where = array();

    /**
     * Order by criteria
     * @var array
     */
    protected $_orderBy = array();

    /**
     * Group by criteria
     * @var array
     */
    protected $_groupBy = array();

    /**
     * Having criteria
     * @var string
     */
    protected $_having = '';

    /**
     * Limit criteria
     * @var string
     */
    protected $_limit = '';


    /**
     * Build query string from setted data
     *
     * @return string
     * @throws Exception
     */
    public function buildQuery()
    {
        $where   = implode(' ', $this->_where);
        $join    = implode(' ', $this->_join);
        $orderBy = count($this->_orderBy) > 0 ? sprintf('ORDER BY %s', implode(',', $this->_orderBy)) : '';
        $groupBy = count($this->_groupBy) > 0 ? sprintf('GROUP BY %s', implode(',', $this->_groupBy)) : '';
        $hint    = count($this->_queryHint) > 0 ? sprintf(' %s ', implode(',', $this->_queryHint)) : '';
        $limit   = $this->_limit;
        $query   = '';

        switch ($this->_queryType) {
            case self::SELECT:
                $select = implode(',', $this->_select);
                $from   = !empty($this->_from) ? sprintf('FROM %s', $this->_from) : '';
                $query  = sprintf(
                    "SELECT %s %s %s %s %s %s %s %s %s",
                    $hint,
                    $select,
                    $from,
                    $join,
                    $where,
                    $groupBy,
                    $this->_having,
                    $orderBy,
                    $limit
                );
                break;
            case self::INSERT:
                $values = '';

                // save lastInsertValues
                if (!empty($this->_lastInsertValues)) {
                    $this->_insertValues[]   = $this->_lastInsertValues;
                    $this->_lastInsertValues = array();
                }

                // grab field name from first line
                $keys   = array_keys($this->_insertValues[0]);
                $nbKeys = count($keys) - 1;

                // reorder values and build string
                foreach ($this->_insertValues as $k => $oneVal) {
                    if ($k > 0) {
                        $values .= ',';
                    }
                    $values .= '(';
                    for ($i = 0; $i <= $nbKeys; $i++) {
                        $values .= $oneVal[$keys[$i]];
                        if ($i != $nbKeys) {
                            $values .= ',';
                        }
                    }
                    $values .= ')';
                }

                // build insert query string
                $query = sprintf(
                    "INSERT INTO %s (%s) VALUES %s",
                    $this->_insert,
                    implode(',', $keys),
                    $values
                );
                break;
            case self::UPDATE:
                $strSet = '';

                // test if params number match the values
                if (count($this->_setName) != count($this->_setVal)) {
                    throw new Exception("number of params / values is not correct");
                }

                // build SET values
                $maxParams = count($this->_setName) - 1;
                for ($i = 0; $i <= $maxParams; $i++) {
                    $strSet .= sprintf('%s = %s', $this->_setName[$i], $this->_setVal[$i]);
                    if ($i !== $maxParams) {
                        $strSet .= ',';
                    }
                }

                // build update query string
                $query = sprintf(
                    "UPDATE %s %s SET %s %s %s %s",
                    $this->_update,
                    $join,
                    $strSet,
                    $where,
                    $orderBy,
                    $limit
                );
                break;
            case self::DELETE:

                // build delete query string
                $query = sprintf(
                    "DELETE %s FROM %s %s %s %s %s",
                    $this->_delete,
                    $this->_delete,
                    $join,
                    $where,
                    $orderBy,
                    $limit
                );
                break;
        }

        return trim($query);
    }


    /**
     * Add a query hint modifier (ex SQL_NO_CACHE)
     *
     * @param $queryHint
     */
    public function queryHint($queryHint)
    {
        $this->_queryHint[] = $queryHint;

        return $this;
    }


    /**
     * Add values (INSERT|UPDATE)
     *
     * @param string $nameParams - field name
     * @param string $val        - field value
     *
     * @return $this
     */
    public function values($nameParams, $val)
    {
        $this->_lastInsertValues[$nameParams] = $val;

        return $this;
    }


    /**
     * Create new values set for multiple insert query
     *
     * @param $nameParams - field name
     * @param $val        - field value
     *
     * @return $this
     */
    public function newValues($nameParams, $val)
    {
        // store value buffer data
        if (count($this->_lastInsertValues) > 0) {
            $this->_insertValues[] = $this->_lastInsertValues;
        }

        // set new value in insert buffer
        $this->_lastInsertValues = array($nameParams => $val);

        return $this;
    }


    /**
     * Set values for update query
     *
     * @alias values
     *
     * @param $nameParams
     * @param $val
     *
     * @return $this
     */
    public function set($nameParams, $val)
    {
        $this->_setName[] = $nameParams;
        $this->_setVal[]  = $val;

        return $this;
    }


    /**
     * Add field to select
     *
     * @param $field
     *
     * @return $this
     */
    public function select($field)
    {
        $this->_queryType = self :: SELECT;
        if (!is_array($field)) {
            $field = array($field);
        }
        $this->_select = array_merge($this->_select, $field);

        return $this;
    }

    /**
     * Reset select and add a new field
     *
     * @param $field
     *
     * @return $this
     */
    public function resetSelect($field = null)
    {
        $this->_select = array();
        if ($field !== null) {
            $this->select($field);
        }

        return $this;
    }


    /**
     * Configure object for UPDATE query
     * and set the main table name
     *
     * @param $tableName
     *
     * @return $this
     */
    public function update($tableName)
    {
        $this->_queryType = self :: UPDATE;
        $this->_update    = $tableName;

        return $this;
    }


    /**
     * Configure object for DELETE query
     * and set the main table name
     *
     * @param $tableName
     *
     * @return $this
     */
    public function delete($tableName)
    {
        $this->_queryType = self :: DELETE;
        $this->_delete    = $tableName;

        return $this;
    }


    /**
     * Configure object for INSERT query
     * and set the main table name
     *
     * @param $tableName
     *
     * @return $this
     */
    public function insertInto($tableName)
    {
        $this->_queryType = self :: INSERT;
        $this->_insert    = $tableName;

        return $this;
    }


    /**
     * Add FROM clause with table name and optional alias
     *
     * @param      $tableName
     * @param null $aliasTable
     *
     * @return $this
     */
    public function from($tableName, $aliasTable = null)
    {
        $this->_from = sprintf('%s %s', $tableName, $aliasTable);

        return $this;
    }


    /**
     * Add JOIN clause
     *
     * @param $mode     string    INNER|LEFT|RIGHT or other mysql join possibilities
     * @param $table    string    table name
     * @param $on       string    join condition
     *
     * @return $this
     */
    public function join($mode, $table, $on)
    {
        $this->_join[] = sprintf('%s JOIN %s ON %s', $mode, $table, $on);

        return $this;
    }


    /**
     * Add INNER JOIN clause
     *
     * @param $table    string    table name
     * @param $on       string    join condition
     *
     * @return $this
     */
    public function innerJoin($table, $on)
    {
        $this->join('INNER', $table, $on);

        return $this;
    }


    /**
     * Add LEFT JOIN clause
     *
     * @param $table    string    table name
     * @param $on       string    join condition
     *
     * @return $this
     */
    public function leftJoin($table, $on)
    {
        $this->join('LEFT', $table, $on);

        return $this;
    }


    /**
     * Adding WHERE clause (default with AND)
     *
     * @param        $field
     * @param        $comparisonOperator
     * @param        $value
     * @param string $booleanOperator (AND|OR)
     *
     * @return $this
     */
    public function where($field, $comparisonOperator, $value, $booleanOperator = 'AND')
    {
        // first where value, need to override operator
        if (count($this->_where) == 0) {
            $booleanOperator = "WHERE";
        }

        // create and store where
        $this->_where[] = sprintf("%s %s %s %s", $booleanOperator, $field, $comparisonOperator, $value);

        return $this;
    }


    /**
     * Alias to add WHERE clause with AND operator
     *
     * @param $field
     * @param $comparisonOperator
     * @param $value
     *
     * @return $this
     */
    public function andWhere($field, $comparisonOperator, $value)
    {
        $this->where($field, $comparisonOperator, $value, 'AND');

        return $this;
    }


    /**
     * Alias to add WHERE clause with OR
     *
     * @param $field
     * @param $comparisonOperator
     * @param $value
     *
     * @return $this
     */
    public function orWhere($field, $comparisonOperator, $value)
    {
        $this->where($field, $comparisonOperator, $value, 'OR');

        return $this;
    }


    /**
     * Add data to ORDER BY clause
     *
     * @param        $orderName
     * @param string $orderVal
     *
     * @return $this
     */
    public function orderBy($orderName, $orderVal = '')
    {
        $this->_orderBy[] = sprintf("%s %s", $orderName, $orderVal);

        return $this;
    }

    /**
     * Reset the order clause from query
     *
     * @param null   $orderName
     * @param string $orderVal
     *
     * @return $this
     */
    public function resetOrderBy($orderName = null, $orderVal = '')
    {
        $this->_orderBy = array();
        if ($orderName !== null) {
            $this->orderBy($orderName, $orderVal);
        }

        return $this;
    }


    /**
     * Add a GROUP BY clause to the query
     *
     * @param $groupByField
     *
     * @return $this
     */
    public function groupBy($groupByField)
    {
        $this->_groupBy[] = sprintf("%s", $groupByField);

        return $this;
    }


    /**
     * Add a HAVING clause to the query
     *
     * @param $having
     *
     * @return $this
     */
    public function having($having)
    {
        $this->_having = sprintf('HAVING %s', $having);

        return $this;
    }


    /**
     * Set LIMIT clause
     *
     * @param      $limitStart
     * @param null $limitEnd
     *
     * @return $this
     */
    public function limit($limitStart = null, $limitEnd = null)
    {
        if ($limitStart === null) {
            return $this;
        }

        if ($limitEnd === null) {
            $this->_limit = sprintf('LIMIT %d', $limitStart);
        } else {
            $this->_limit = sprintf('LIMIT %d, %d', $limitStart, $limitEnd);
        }

        return $this;
    }

    /**
     * Delete the limit clause from query
     *
     * @param null $limitStart
     * @param null $limitEnd
     *
     * @return $this
     */
    public function resetLimit($limitStart = null, $limitEnd = null)
    {
        $this->_limit = '';
        if ($limitStart !== null) {
            $this->limit($limitStart, $limitEnd);
        }

        return $this;
    }


    /**
     * Return all fields values set during building an UPDATE query
     *
     * @return array
     */
    public function getUpdateValues()
    {
        return $this->_setVal;
    }
}