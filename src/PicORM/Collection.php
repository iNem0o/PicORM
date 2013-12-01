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
     * Active or not pagination
     * @var bool
     */
    protected $_usePagination = false;

    /**
     * Number of model by page
     * @var int
     */
    protected $_paginationNbModelByPage = 0;

    /**
     * Total models rows founded during pagination
     * @var int
     */
    protected $_paginationFoundModels = 0;

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
     * @param \PDO                $dataSource
     * @param InternalQueryHelper $queryHelper
     * @param                     $className
     */
    public function __construct(\PDO $dataSource, InternalQueryHelper $queryHelper, $className)
    {
        $this->_dataSource  = $dataSource;
        $this->_className   = $className;
        $this->_queryHelper = $queryHelper;
    }

    /**
     * Return collection query helper
     * @return InternalQueryHelper
     */
    public function getQueryHelper()
    {
        return clone($this->_queryHelper);
    }

    /**
     * Define collection query helper
     * @return InternalQueryHelper
     */
    public function setQueryHelper(QueryBuilder $queryHelper)
    {
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

        // if pagination is used, adding found rows mysql hint
        if ($this->_usePagination) {
            $this->_queryHelper->queryHint("SQL_CALC_FOUND_ROWS");
        }

        // execute fetch query
        $query = $this->_dataSource->prepare($this->_queryHelper->buildQuery());
        $query->execute($this->_queryHelper->getWhereParamsValues());

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") {
            throw new Exception($errorcode[2]);
        }

        // fetch query and hydrate models
        $fetch = $query->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($fetch as &$unRes) {
            $object = new $modelName();
            $object->hydrate($unRes, false);
            $unRes = $object;
        }

        // configure collection after fetch
        $this->isFetched = true;
        $this->models    = $fetch;

        // if pagination used grab the total found model
        if ($this->_usePagination) {
            $this->_paginationFoundModels = $this->foundModels();
        }

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

        // transform query to delete
        $deleteQuery->cleanQueryBeforeSwitching()
                    ->delete($modelClass::formatTableNameMySQL());

        // execute query
        $query = $this->_dataSource->prepare($deleteQuery->buildQuery());
        $query->execute($deleteQuery->getWhereParamsValues());

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") {
            throw new Exception($errorcode[2]);
        }
    }

    /**
     * Update models in collection with specified values
     *
     * @param array $setValues
     *
     * @throws Exception
     */
    public function update(array $setValues)
    {
        $modelClass = $this->_className;

        // cloning fetch query to get where,order by and limit values
        $updateQuery = clone($this->_queryHelper);

        // transform query to update
        $updateQuery->cleanQueryBeforeSwitching()
                    ->update($modelClass::formatTableNameMySQL());

        // build set values
        $params = array();
        foreach ($setValues as $fieldName => $value) {
            $updateQuery->set($fieldName, '?');
            $params[] = $value;
        }

        // merge set values with where values
        $params = array_merge($params, $updateQuery->getWhereParamsValues());

        // execute query
        $query = $this->_dataSource->prepare($updateQuery->buildQuery());
        $query->execute($params);

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") {
            throw new Exception($errorcode[2]);
        }
    }

    /**
     * Return an element from collection by index
     *
     * @param $index
     *
     * @return Model
     */
    public function get($index)
    {
        if (!$this->isFetched) {
            $this->fetchCollection();
        }

        return $this->models[$index];

    }

    /**
     * Return total page available
     * @return int
     */
    public function getTotalPages()
    {
        if ($this->_usePagination === false) {
            return 0;
        }
        if (!$this->isFetched) {
            $this->fetchCollection();
        }

        return (int)ceil($this->_paginationFoundModels / $this->_paginationNbModelByPage);
    }

    /**
     * Paginate collection to match a num page
     *
     * @param $neededNumPage
     */
    public function paginate($neededNumPage)
    {
        if ($this->_usePagination === false) {
            return;
        }

        // build the limit start
        $limitStart = max(0, $neededNumPage - 1) * $this->_paginationNbModelByPage;

        // limit fetch query for page $neededNumPage
        $this->_queryHelper->limit($limitStart, $this->_paginationNbModelByPage);

    }


    /**
     * Enable pagination in collection
     *
     * @param $nbModelByPage - Number of model by page
     */
    public function activePagination($nbModelByPage)
    {
        $this->_usePagination           = true;
        $this->_paginationNbModelByPage = $nbModelByPage;
    }

    /**
     * Fetch the mysql found_rows from last select query
     * @return mixed
     */
    public function foundModels()
    {
        if (!$this->isFetched) {
            $this->fetchCollection();
        }

        return (int)$this->_dataSource->query('SELECT FOUND_ROWS();')->fetch(\PDO::FETCH_COLUMN);
    }

    /**
     * Test if collection has element at this $index
     *
     * @param $index
     *
     * @return bool
     */
    public function has($index)
    {
        if (!$this->isFetched) {
            $this->fetchCollection();
        }

        return isset($this->models[$index]);
    }

    /**
     * Set collection element with $model at $index
     *
     * @param $index
     * @param $model
     */
    public function set($index, $model)
    {
        $this->models[$index] = $model;
    }

    /**
     * Rewind the Iterator to the first element
     * Rewind method allow to lazy fetch collection
     * when iteration begins
     */
    public function rewind()
    {
        if (!$this->isFetched) {
            $this->fetchCollection();
        }
        $this->position = 0;
    }

    /**
     * Return the current model
     * @return Model
     */
    public function current()
    {
        return $this->models[$this->position];
    }

    /**
     * Return the key of the current model
     * @return int|mixed
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Move forward to next model
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Checks if current position is valid
     * @return bool - Returns true on success or false on failure
     */
    public function valid()
    {
        return isset($this->models[$this->position]);
    }

    /**
     * Count elements of an object
     * @return int The custom count as an integer.
     */
    public function count()
    {
        if (!$this->isFetched) {
            $this->fetchCollection();
        }

        return count($this->models);
    }

    /**
     * Whether a offset exists
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        if (!$this->isFetched) {
            $this->fetchCollection();
        }

        return isset($this->models[$offset]);
    }

    /**
     * Offset to set
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (!$this->isFetched) {
            $this->fetchCollection();
        }

        if (is_null($offset)) {
            $this->models[] = $value;
        } else {
            $this->models[$offset] = $value;
        }
    }

    /**
     * Offset to unset
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        if (!$this->isFetched) {
            $this->fetchCollection();
        }

        unset($this->models[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @param mixed $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        if (!$this->isFetched) {
            $this->fetchCollection();
        }

        return isset($this->models[$offset]) ? $this->models[$offset] : null;
    }
}

?>