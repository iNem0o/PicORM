<?php
namespace PicORM;

/**
 * Store an entity list defined by a InternalQueryHelper
 * and allow to fetch / modify / delete them
 * @package PicORM
 */
class EntityCollection implements \Iterator
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
     * Entity class name
     * @var
     */
    private $className;

    /**
     * @param \PDO $dataSource
     * @param InternalQueryHelper $queryHelper
     * @param $className
     */
    public function __construct(\PDO $dataSource, InternalQueryHelper $queryHelper, $className)
    {
        $this->_dataSource = $dataSource;
        $this->className = $className;
        $this->_queryHelper = $queryHelper;
    }

    /**
     * Execute query and fetch entities from database
     * @return $this
     * @throws Exception
     */
    public function fetchCollection()
    {
        $entityName = $this->className;
        $query = $this->_dataSource->prepare($this->_queryHelper->buildQuery());
        $query->execute($this->_queryHelper->getParams());

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") throw new Exception($errorcode[2]);

        $fetch = $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($fetch as &$unRes) {
            $object = new $entityName();
            $object->hydrate($unRes);
            $unRes = $object;
        }
        $this->entities = $fetch;

        $this->isFetched = true;
        return $this;
    }

    /**
     * Delete entity in collection
     * @throws Exception
     */
    public function delete()
    {
        $entityClass = $this->className;

        // cloning fetch query to get where,order by and limit values
        $deleteQuery = clone($this->_queryHelper);
        $deleteQuery->cleanQueryBeforeSwitching()
            ->delete($entityClass::formatTableNameMySQL());

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
        $entityClass = $this->className;

        // cloning fetch query to get where,order by and limit values
        $updateQuery = clone($this->_queryHelper);
        $updateQuery->cleanQueryBeforeSwitching()
            ->update($entityClass::formatTableNameMySQL());

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
     * Set collection element with $entity at $index
     * @param $id
     * @param $entity
     */
    public function set($index, $entity)
    {
        $this->entities[$index] = $entity;
    }

// iterator methods

    /**
     * Rewind method allow to lazy fetch collection only
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
}

?>