<?php
namespace PicORM;

/**
 * Store a model list defined by a InternalQueryHelper
 * and allow to fetch / modify / delete them
 * @package PicORM
 */
class Collection implements \Iterator, \Countable, \ArrayAccess
{
    /**
     * Iterator pointer position
     * @var int
     */
    protected $position = 0;

    /**
     * Models
     * @var array
     */
    protected $models = array();

    /**
     * Boolean to test if collection have been already fetched
     * @var bool
     */
    protected $isFetched = false;

    /**
     * Datasource to execute query
     * @var \PDO
     */
    protected $_dataSource;

    /**
     * Model class name
     * @var
     */
    private $_className;

    /**
     * @param \PDO $dataSource
     * @param InternalQueryHelper $queryHelper
     * @param $className
     */
    public function __construct(\PDO $dataSource, InternalQueryHelper $queryHelper, $className)
    {
        $this->_dataSource = $dataSource;
        $this->_className = $className;
        $this->_queryHelper = $queryHelper;
    }

    /**
     * Execute query and fetch models from database
     * @return $this
     * @throws Exception
     */
    public function fetchCollection()
    {
        $modelName = $this->_className;
        $query = $this->_dataSource->prepare($this->_queryHelper->buildQuery());
        $query->execute($this->_queryHelper->getWhereParamsValues());

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") throw new Exception($errorcode[2]);

        $fetch = $query->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($fetch as &$unRes) {
            $object = new $modelName();
            $object->hydrate($unRes);
            $unRes = $object;
        }
        $this->models = $fetch;

        $this->isFetched = true;
        return $this;
    }

    /**
     * Delete model in collection
     * @throws Exception
     */
    public function delete()
    {
        $modelClass = $this->_className;

        // cloning fetch query to get where,order by and limit values
        $deleteQuery = clone($this->_queryHelper);
        $deleteQuery->cleanQueryBeforeSwitching()
            ->delete($modelClass::formatTableNameMySQL());

        $query = $this->_dataSource->prepare($deleteQuery->buildQuery());
        $query->execute($deleteQuery->getWhereParamsValues());
        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") throw new Exception($errorcode[2]);
    }

    /**
     * Update models in collection with specified values
     * @param array $setValues
     * @throws Exception
     */
    public function update(array $setValues)
    {
        $modelClass = $this->_className;

        // cloning fetch query to get where,order by and limit values
        $updateQuery = clone($this->_queryHelper);
        $updateQuery->cleanQueryBeforeSwitching()
            ->update($modelClass::formatTableNameMySQL());

        $params = array();
        foreach ($setValues as $fieldName => $value) {
            $updateQuery->set($fieldName, '?');
            $params[] = $value;
        }
        // merge set values with where values
        $params = array_merge($params, $updateQuery->getWhereParamsValues());

        $query = $this->_dataSource->prepare($updateQuery->buildQuery());
        $query->execute($params);

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") throw new Exception($errorcode[2]);
    }

    /**
     * Return an element from collection by index
     * @param $index
     * @return Model
     */
    public function get($index)
    {
        if (!$this->isFetched) $this->fetchCollection();
        return $this->models[$index];
    }

    /**
     * Test if collection has element at this $index
     * @param $index
     * @return bool
     */
    public function has($index)
    {
        if (!$this->isFetched) $this->fetchCollection();
        return isset($this->models[$index]);
    }

    /**
     * Set collection element with $model at $index
     * @param $index
     * @param $model
     */
    public function set($index, $model)
    {
        $this->models[$index] = $model;
    }

// iterator and array interfaces methods

    /**
     * Rewind method allow to lazy fetch collection
     * when iteration begins
     */
    public function rewind()
    {
        if (!$this->isFetched) $this->fetchCollection();
        $this->position = 0;
    }

    public function current()
    {
        return $this->models[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->models[$this->position]);
    }

    public function count() {
        if (!$this->isFetched) $this->fetchCollection();

        return count($this->models);
    }

    public function offsetSet($offset, $value) {
        if (!$this->isFetched) $this->fetchCollection();

        if (is_null($offset))
            $this->models[] = $value;
        else
            $this->models[$offset] = $value;
    }
    public function offsetExists($offset) {
        if (!$this->isFetched) $this->fetchCollection();

        return isset($this->models[$offset]);
    }
    public function offsetUnset($offset) {
        if (!$this->isFetched) $this->fetchCollection();

        unset($this->models[$offset]);
    }
    public function offsetGet($offset) {
        if (!$this->isFetched) $this->fetchCollection();

        return isset($this->models[$offset]) ? $this->models[$offset] : null;
    }
}

?>