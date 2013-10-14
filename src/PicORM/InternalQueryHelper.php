<?php
namespace PicORM;

/**
 * Class InternalQueryHelper
 * Extends raw QueryBuilder class to add some PicORM logic
 * @package PicORM
 */
class InternalQueryHelper extends QueryBuilder
{
    protected $_whereValues;

    public function __construct()
    {

    }

    /**
     * Prefix fields from where associated array
     * with table name
     *
     * @param $data
     * @param $tableName
     * @return mixed
     */
    public function prefixWhereWithTable($data, $tableName)
    {
        if (count($data) == 0) return $data;

        $tableName = trim($tableName, '`');
        foreach ($data as $key => $v) {
            $newKey = $tableName . '.' . trim($key, '`');
            $data[$newKey] = $v;
            unset($data[$key]);
        }
        return $data;
    }

    /**
     * Prefix fields from orderBy associated array
     * with table name
     *
     * @param $data
     * @param $tableName
     * @return mixed
     */
    public function prefixOrderWithTable($data, $tableName)
    {
        if (count($data) == 0) return $data;

        $tableName = trim($tableName, '`');
        foreach ($data as $key => $v) {
            // if $v is empty, we have a custom order like RAND() and do not have to prefix
            if (!empty($v)) {
                $newKey = $tableName . '.' . trim($key, '`');
                $data[$newKey] = $v;
                unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * Build where condition from find() $where params
     * @param $where
     * @return $this
     */
    public function buildWhereFromArray($where)
    {
        if (count($where) == 0) return $this;

        foreach ($where as $fieldName => $oneCritera) {
            $val = '?';
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
     * @return mixed
     */
    public function getWhereParamsValues()
    {
        return $this->_whereValues;
    }

    /**
     * Clean query before switching to another type in collections
     * @return $this
     */
    public function cleanQueryBeforeSwitching()
    {
        // clean join created by collection for fetching auto get field
        $this->_join = array();
        return $this;
    }
}