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
 * @category Collection
 * @package  PicORM
 * @author   iNem0o <contact@inem0o.fr>
 * @license  LGPL http://opensource.org/licenses/lgpl-license.php
 * @link     https://github.com/iNem0o/PicORM
 */

namespace PicORM;

/**
 * Class Collection
 *
 * Store a model array fetched from an InternalQueryHelper
 * and allow to fetch / modify / delete them
 *
 * @category Collection
 * @package  PicORM
 * @author   iNem0o <contact@inem0o.fr>
 * @license  LGPL http://opensource.org/licenses/lgpl-license.php
 * @link     https://github.com/iNem0o/PicORM
 */
class Collection implements \Iterator, \Countable, \ArrayAccess
{
    /**
     * Active or not pagination
     *
     * @var bool
     */
    protected $_usePagination = false;

    /**
     * Number of model by page
     *
     * @var int
     */
    protected $_paginationNbModelByPage = 0;

    /**
     * Total models rows founded during pagination
     *
     * @var int
     */
    protected $_paginationFoundModels = 0;

    /**
     * Iterator pointer position
     *
     * @var int
     */
    protected $position = 0;

    /**
     * Models
     *
     * @var array
     */
    protected $models = array();

    /**
     * Boolean to test if collection have been already fetched
     *
     * @var bool
     */
    protected $isFetched = false;

    /**
     * Datasource to execute query
     *
     * @var \PDO
     */
    protected $_dataSource;

    /**
     * Model class name
     *
     * @var
     */
    private $_className;

    /**
     * QueryBuilder to fetch collection
     *
     * @var InternalQueryHelper
     */
    protected $_queryHelper;


    /**
     * Constructor
     *
     * @param \PDO                $dataSource  - Pdo instance
     * @param InternalQueryHelper $queryHelper - QueryBuilder to fetch collection
     * @param string              $className   - class name of the model
     */
    public function __construct(\PDO $dataSource, InternalQueryHelper $queryHelper, $className)
    {
        $this->_dataSource  = $dataSource;
        $this->_className   = $className;
        $this->_queryHelper = $queryHelper;
    }


    /**
     * Return collection query helper
     *
     * @return InternalQueryHelper
     */
    public function getQueryHelper()
    {
        return clone($this->_queryHelper);
    }


    /**
     * Define collection query helper
     *
     * @param QueryBuilder $queryHelper - Query builder to set inside collection
     *
     * @return void
     */
    public function setQueryHelper(QueryBuilder $queryHelper)
    {
        $this->_queryHelper = $queryHelper;
    }


    /**
     * Execute query and fetch models from database
     *
     * @return $this
     * @throws Exception
     */
    public function fetch()
    {
        $modelName = $this->_className;

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
            /** @var $object \PicORM\Model */
            $object = new $modelName();
            $object->hydrate($unRes, false);
            $unRes = $object;
        }

        // configure collection after fetch
        $this->isFetched = true;
        $this->models    = $fetch;

        // if pagination used grab the total found model
        if ($this->_usePagination) {
            $this->_paginationFoundModels = $this->queryFoundModels();
        }

        return $this;
    }


    /**
     * Delete model in collection
     *
     * @throws Exception
     * @return bool - true if deleted
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

        return true;
    }


    /**
     * Update models in collection with specified values
     *
     * @param array $setValues - Associative array with model field name as key, and model field value as value
     *
     * @throws Exception
     *
     * @return void
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
     * @param int $index - needed array index
     *
     * @return Model
     */
    public function get($index)
    {
        if (!$this->isFetched) {
            $this->fetch();
        }

        return $this->models[$index];

    }
    
    /**
    * Return all models inside the collection
    *
    * @return array
    */
    public function getModels()
    {
        return $this->models;
    }


    /**
     * Return total page available
     *
     * @return int
     */
    public function getTotalPages()
    {
        if ($this->_usePagination === false) {
            return 0;
        }
        if (!$this->isFetched) {
            $this->fetch();
        }

        return (int)ceil($this->_paginationFoundModels / $this->_paginationNbModelByPage);
    }


    /**
     * Paginate collection to match a num page
     *
     * @param int $neededNumPage - Needed page number
     *
     * @return Collection
     */
    public function paginate($neededNumPage)
    {
        if ($this->_usePagination === false) {
            return $this;
        }

        // build the limit start
        $limitStart = max(0, $neededNumPage - 1) * $this->_paginationNbModelByPage;

        // limit fetch query for page $neededNumPage
        $this->_queryHelper->limit($limitStart, $this->_paginationNbModelByPage);

        return $this;
    }


    /**
     * Enable pagination in collection
     *
     * @param int $nbModelByPage - Number of model by page
     *
     * @return Collection
     */
    public function activePagination($nbModelByPage)
    {
        $this->_usePagination           = true;
        $this->_paginationNbModelByPage = $nbModelByPage;

        return $this;
    }

    /**
     * Execute query to count number of model in database without limit
     *
     * @return mixed
     */
    protected function queryFoundModels()
    {
        $countQueryHelper = clone($this->_queryHelper);
        $countQueryHelper->resetSelect("count(*)");
        $countQueryHelper->resetOrderBy();
        $countQueryHelper->resetLimit();
        $query = $this->_dataSource->prepare($countQueryHelper->buildQuery());
        $query->execute($countQueryHelper->getWhereParamsValues());

        return (int)$query->fetch(\PDO::FETCH_COLUMN);
    }


    /**
     * Remove limit from fetch query and count the number
     * of models in database
     *
     * @return mixed
     */
    public function countModelsWithoutLimit()
    {
        if (!$this->isFetched) {
            $this->fetch();
        }

        // no pagination, we have to manually count number of model
        if (!$this->_usePagination) {
            $this->_paginationFoundModels = $this->queryFoundModels();
        }

        return $this->_paginationFoundModels;
    }


    /**
     * Test if collection has element at this $index
     *
     * @param int $index - needed index
     *
     * @return bool
     */
    public function has($index)
    {
        if (!$this->isFetched) {
            $this->fetch();
        }

        return isset($this->models[$index]);
    }


    /**
     * Set collection element with $model at $index
     *
     * @param int   $index - needed index
     * @param mixed $model - model to store
     *
     * @return void
     */
    public function set($index, $model)
    {
        $this->models[$index] = $model;
    }


    /**
     * Rewind the Iterator to the first element
     * Rewind method allow to lazy fetch collection
     * when iteration begins
     *
     * @return void
     */
    public function rewind()
    {
        if (!$this->isFetched) {
            $this->fetch();
        }
        $this->position = 0;
    }


    /**
     * Return the current model
     *
     * @return Model
     */
    public function current()
    {
        return $this->models[$this->position];
    }


    /**
     * Return the key of the current model
     *
     * @return int|mixed
     */
    public function key()
    {
        return $this->position;
    }


    /**
     * Move forward to next model
     *
     * @return void
     */
    public function next()
    {
        ++$this->position;
    }


    /**
     * Checks if current position is valid
     *
     * @return bool - Returns true on success or false on failure
     */
    public function valid()
    {
        return isset($this->models[$this->position]);
    }


    /**
     * Count elements of an object
     *
     * @return int The custom count as an integer.
     */
    public function count()
    {
        if (!$this->isFetched) {
            $this->fetch();
        }

        return count($this->models);
    }


    /**
     * Whether a offset exists
     *
     * @param mixed $offset - Offset name to test
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        if (!$this->isFetched) {
            $this->fetch();
        }

        return isset($this->models[$offset]);
    }


    /**
     * Offset to set
     *
     * @param mixed $offset - Offset name to set
     * @param mixed $value  - Value to set
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (!$this->isFetched) {
            $this->fetch();
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
     * @param mixed $offset - Offset name to unset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        if (!$this->isFetched) {
            $this->fetch();
        }

        unset($this->models[$offset]);
    }


    /**
     * Offset to retrieve
     *
     * @param mixed $offset - Offset name to get
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        if (!$this->isFetched) {
            $this->fetch();
        }

        return isset($this->models[$offset]) ? $this->models[$offset] : null;
    }
}

?>
