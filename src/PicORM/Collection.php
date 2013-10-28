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
     * Entities array
     * @var array
     */
    protected $entities = array();

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
     * Execute query and fetch entities from database
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
        $this->entities = $fetch;

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
     * Update entities in collection with specified values
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
     * @param $id
     * @return mixed
     */
    public function get($index)
    {
        if (!$this->isFetched) $this->fetchCollection();
        return $this->entities[$index];
    }

    /**
     * Test if collection has element at this $index
     * @param $id
     * @return bool
     */
    public function has($index)
    {
        if (!$this->isFetched) $this->fetchCollection();
        return isset($this->entities[$index]);
    }

    /**
     * Set collection element with $model at $index
     * @param $id
     * @param $model
     */
    public function set($index, $model)
    {
        $this->entities[$index] = $model;
    }

// iterator methods

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
        return $this->entities[$this->position];
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
        return isset($this->entities[$this->position]);
    }

    public function count() {
        if (!$this->isFetched) $this->fetchCollection();

        return count($this->entities);
    }

    public function offsetSet($offset, $value) {
        if (!$this->isFetched) $this->fetchCollection();

        if (is_null($offset))
            $this->entities[] = $value;
        else
            $this->entities[$offset] = $value;
    }
    public function offsetExists($offset) {
        if (!$this->isFetched) $this->fetchCollection();

        return isset($this->entities[$offset]);
    }
    public function offsetUnset($offset) {
        if (!$this->isFetched) $this->fetchCollection();

        unset($this->entities[$offset]);
    }
    public function offsetGet($offset) {
        if (!$this->isFetched) $this->fetchCollection();

        return isset($this->entities[$offset]) ? $this->entities[$offset] : null;
    }
}

?>