<?php
namespace PicORM;

/**
 * Class QueryBuilder
 * Allow you to easily build MySQL query using PHP Object
 * @package PicORM
 */
class QueryBuilder
{
    /**
     * Fields names (UPDATE)
     * @var array
     */
    protected $_setName = array();

    /**
     * Fields values (UPDATE)
     * @var array
     */
    protected $_setVal = array();

    /**
     * Insert VALUES (UPDATE or INSERT)
     * @var array
     */
    protected $_insertValues = array();

    /**
     * Temp insert values
     * @var array
     */
    protected $_lastInsertValues = array();


    /**
     * Marker for query type
     */
    const SELECT = 1;
    const DELETE = 2;
    const UPDATE = 3;
    const INSERT = 4;


    /**
     * Define query TYPE (UPDATE|INSERT|DELETE|SELECT)
     * @var
     */
    protected $_queryType;


    protected $_select = array();
    protected $_insert = '';
    protected $_update = '';
    protected $_delete = '';
    protected $_from = '';
    protected $_join = array();
    protected $_where = array();
    protected $_orderBy = array();
    protected $_groupBy = array();
    protected $_having = '';
    protected $_limit = '';

    /**
     * Construct query string from data
     * @return string
     * @throws Exception
     */
    public function buildQuery()
    {
        $where = implode(' ', $this->_where);
        $join = implode(' ', $this->_join);
        $orderBy = count($this->_orderBy) > 0 ? sprintf('ORDER BY %s', implode(',', $this->_orderBy)) : '';
        $groupBy = count($this->_groupBy) > 0 ? sprintf('GROUP BY %s', implode(',', $this->_groupBy)) : '';
        $limit = $this->_limit;
        $query = '';
        switch ($this->_queryType) {
            case self::SELECT:
                $select = implode(',', $this->_select);
                $from = !empty($this->_from) ? sprintf('FROM %s', $this->_from) : '';
                $query = sprintf("SELECT %s %s %s %s %s %s %s %s",
                    $select, $from, $join, $where, $groupBy, $this->_having, $orderBy, $limit);
                break;
            case self::INSERT:
                $values = '';

                // save lastInsertValues
                if(!empty($this -> _lastInsertValues)) {
                    $this -> _insertValues[] = $this -> _lastInsertValues;
                    $this -> _lastInsertValues = array();
                }

                // grab field name from first line
                $keys = array_keys($this -> _insertValues[0]);
                $nbKeys = count($keys)-1;

                // reorder values and build string
                foreach($this -> _insertValues as $k => $oneVal) {
                    if($k > 0) $values .= ',';
                    $values .= '(';
                    for($i = 0;$i <= $nbKeys;$i++) {
                        $values .= $oneVal[$keys[$i]];
                        if($i != $nbKeys) $values .= ',';
                    }
                    $values .= ')';
                }

                return sprintf(
                    "INSERT INTO %s (%s) VALUES %s",
                    $this->_insert,
                    implode(',', $keys),
                    $values
                );
                break;
            case self::UPDATE:
                $strSet = '';
                if (count($this->_setName) != count($this->_setVal))
                    throw new Exception("number of params / values is not correct");

                $maxParams = count($this->_setName) - 1;
                for ($i = 0; $i <= $maxParams; $i++) {
                    $strSet .= sprintf('%s = %s', $this->_setName[$i], $this->_setVal[$i]);
                    if ($i !== $maxParams) $strSet .= ',';
                }
                $query = sprintf(
                    "UPDATE %s %s SET %s %s %s %s",
                    $this->_update, $join, $strSet, $where, $orderBy, $limit
                );
                break;
            case self::DELETE:
                $query = sprintf(
                    "DELETE %s FROM %s %s %s %s %s",
                    $this->_delete, $this->_delete, $join, $where, $orderBy, $limit
                );
                break;
        }
        return trim($query);
    }

    /**
     * Add values (INSERT|UPDATE)
     * @param $nameParams
     * @param $val
     * @return $this
     */
    public function values($nameParams, $val)
    {
        $this -> _lastInsertValues[$nameParams] = $val;
        return $this;
    }

    /**
     * Create new values for insert multiple
     * @param $nameParams
     * @param $val
     */
    public function newValues($nameParams, $val) {
        if(count($this -> _lastInsertValues) > 0) $this -> _insertValues[] = $this -> _lastInsertValues;
        $this -> _lastInsertValues = array($nameParams=>$val);
        return $this;
    }

    /**
     * Set values for update query
     * @alias values
     * @param $nameParams
     * @param $val
     * @return $this
     */
    public function set($nameParams, $val)
    {
        $this -> _setName[] = $nameParams;
        $this -> _setVal[] = $val;
        return $this;
    }

    /**
     * Add field to select
     * @param $field
     * @return $this
     */
    public function select($field)
    {
        $this->_queryType = self :: SELECT;
        if (!is_array($field)) $field = array($field);
        $this->_select = array_merge($this->_select, $field);
        return $this;
    }

    /**
     * Configure object for UPDATE query
     * and set the main table name
     * @param $tableName
     * @return $this
     */
    public function update($tableName)
    {
        $this->_queryType = self :: UPDATE;
        $this->_update = $tableName;
        return $this;
    }

    /**
     * Configure object for DELETE query
     * and set the main table name
     * @param $tableName
     * @return $this
     */
    public function delete($tableName)
    {
        $this->_queryType = self :: DELETE;
        $this->_delete = $tableName;
        return $this;
    }

    /**
     * Configure object for INSERT query
     * and set the main table name
     * @param $tableName
     * @return $this
     */
    public function insertInto($tableName)
    {
        $this->_queryType = self :: INSERT;
        $this->_insert = $tableName;
        return $this;
    }

    /**
     * Add FROM clause with table name and optional alias
     * @param $tableName
     * @param null $aliasTable
     * @return $this
     */
    public function from($tableName, $aliasTable = null)
    {
        $this->_from = sprintf('%s %s', $tableName, $aliasTable);
        return $this;
    }

    /**
     * Add JOIN clause
     * @param $mode     string    INNER|LEFT|RIGHT or other mysql join possibilities
     * @param $table    string    table name
     * @param $on       string    join condition
     * @return $this
     */
    public function join($mode, $table, $on)
    {
        $this->_join[] = sprintf('%s JOIN %s ON %s', $mode, $table, $on);
        return $this;
    }

    /**
     * Add INNER JOIN clause
     * @param $table    string    table name
     * @param $on       string    join condition
     * @return $this
     */
    public function innerJoin($table, $on)
    {
        $this->join('INNER', $table, $on);
        return $this;
    }

    /**
     * Add LEFT JOIN clause
     * @param $table    string    table name
     * @param $on       string    join condition
     * @return $this
     */
    public function leftJoin($table, $on)
    {
        $this->join('LEFT', $table, $on);
        return $this;
    }

    /**
     * Adding WHERE clause (default with AND)
     * @param $field
     * @param $comparisonOperator
     * @param $value
     * @param string $booleanOperator (AND|OR)
     * @return $this
     */
    public function where($field, $comparisonOperator, $value, $booleanOperator = 'AND')
    {
        if (count($this->_where) == 0) $booleanOperator = "WHERE";
        $this->_where[] = sprintf("%s %s %s %s", $booleanOperator, $field, $comparisonOperator, $value);
        return $this;
    }

    /**
     * Alias to add WHERE clause with AND
     * @param $field
     * @param $comparisonOperator
     * @param $value
     * @return $this
     */
    public function andWhere($field, $comparisonOperator, $value)
    {
        $this->where($field, $comparisonOperator, $value, 'AND');
        return $this;
    }

    /**
     * Alias to add WHERE clause with OR
     * @param $field
     * @param $comparisonOperator
     * @param $value
     * @return $this
     */
    public function orWhere($field, $comparisonOperator, $value)
    {
        $this->where($field, $comparisonOperator, $value, 'OR');
        return $this;
    }

    /**
     * Add data to ORDER BY clause
     * @param $orderName
     * @param string $orderVal
     * @return $this
     */
    public function orderBy($orderName, $orderVal = '')
    {
        $this->_orderBy[] = sprintf("%s %s", $orderName, $orderVal);
        return $this;
    }

    /**
     * Add data to GROUP BY clause
     * @param $groupByField
     * @return $this
     */
    public function groupBy($groupByField)
    {
        $this->_groupBy[] = sprintf("%s", $groupByField);
        return $this;
    }

    /**
     * Add HAVING clause
     * @param $having
     * @return $this
     */
    public function having($having)
    {
        $this->_having = sprintf('HAVING %s', $having);
        return $this;
    }

    /**
     * Set LIMIT clause
     * @param $limitStart
     * @param null $limitEnd
     * @return $this
     */
    public function limit($limitStart, $limitEnd = null)
    {
        if (empty($limitStart)) return $this;

        if ($limitEnd === null)
            $this->_limit = sprintf('LIMIT %d', $limitStart);
        else
            $this->_limit = sprintf('LIMIT %d, %d', $limitStart, $limitEnd);

        return $this;
    }

    /**
     * Return all fields values set during building
     * @return array
     */
    public function getParams()
    {
        return $this->_setVal;
    }
}