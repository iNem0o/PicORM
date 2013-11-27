<?php
namespace PicORM;
/**
 * PicORM is a simple and light PHP Object-Relational Mapping
 */
abstract class Model
{
    /**
     * Table primary key
     * @var null
     */
    protected static $_primaryKey = null;

    /**
     * Model Database name
     * @var string
     */
    protected static $_databaseName = null;

    /**
     * Model table name
     * @var string
     */
    protected static $_tableName = null;

    /**
     * Model relations
     * @var array
     */
    protected static $_relations = null;

    /**
     * SQL fields from table without primary key
     * @var array
     */
    protected static $_tableFields = null;

    /**
     * Array to store OOP declaration status of each Model subclass
     * @var array
     */
    private static $validationStatus = array();

    /**
     * Datasource instance
     * @var \PDO
     */
    protected static $_dataSource;

    /**
     * Define if model have been saved
     * @var bool
     */
    protected $_isNew = true;

    /**
     * Identifier for 1-1 relation
     */
    const ONE_TO_ONE = 1;

    /**
     * Identifier for 1-N relation
     */
    const ONE_TO_MANY = 2;

    /**
     * Identifier for N-N relation
     */
    const MANY_TO_MANY = 3;

    /**
     * Can declare model relations with calling
     * ::addRelationOneToOne()
     * ::addRelationOneToMany()
     * @throws Exception
     */
    protected static function defineRelations()
    {
        if(static::$_relations === null) throw new Exception(get_class(new static()).'::$_tableName must be implemented');
    }

    /**
     * Validate if this model is correctly implemented
     * @throws Exception
     */
    protected static function _validateModel()
    {
        // assure that check is only did once
        if (!isset(self::$validationStatus[static::$_databaseName . static::$_tableName])) {

            $subClassName = get_class(new static());
            // check model OOP static structure is OK
            if (static::$_tableName === null) throw new Exception($subClassName . '::$_tableName must be implemented');
            if (static::$_primaryKey === null) throw new Exception($subClassName . '::$_primaryKey must be implemented');
            if (static::$_tableFields === null) throw new Exception($subClassName . '::$_tableFields must be implemented');

            if (static::$_relations !== null)
                static::defineRelations();

            self::$validationStatus[static::$_databaseName . static::$_tableName] = true;
        }
    }


    /**
     * Format database name to using it in SQL query
     * @return string
     */
    public static function formatDatabaseNameMySQL()
    {
        return !empty(static::$_databaseName) ? "`" . static::$_databaseName . "`." : '';
    }

    /**
     * Format table name to using it in SQL query
     * @return string
     */
    public static function formatTableNameMySQL()
    {
        return self::formatDatabaseNameMySQL() . "`" . static::$_tableName . "`";
    }

    /**
     * Return primary key field name
     * @return string
     */
    public static function getPrimaryKeyFieldName()
    {
        return static::$_primaryKey;
    }

    /**
     * Save model in database
     */
    public function save()
    {
        if ($this->_isNew)
            return $this->insert();
        else
            return $this->update();
    }

    /**
     * Return model data in JSON
     * @return string
     */
    public function __toJson()
    {
        $jsonData = array(static::$_primaryKey => $this->{static::$_primaryKey});
        foreach (static::$_tableFields as $unChamp) {
            $jsonData[$unChamp] = $this->{$unChamp};
        }
        return json_encode($jsonData);
    }

    /**
     * Magic call which create accessors for relation
     * @param $method
     * @param $args
     * @return mixed
     * @throws Exception
     */
    public function __call($method, $args)
    {
        static::_validateModel();

        if (preg_match('/^(get|set|unset)(.+)/', $method, $matches) && array_key_exists(strtolower($matches[2]), static::$_relations)) {
            $toCall = '_' . $matches[1] . 'Relation';
            // calling getRelation() or setRelation() or unsetRelation()
            return $this->$toCall(static::$_relations[strtolower($matches[2])], $args);
        } else {
            throw new Exception("unknown function {$method}");
        }
    }

    /**
     * Unset a relation value from magic setter
     * @param array $configRelation
     * @param $callArgs
     * @todo unset other relations type
     * @return bool
     */
    private function _unsetRelation(array $configRelation, $callArgs)
    {
        $isDeleted = false;
        switch ($configRelation['typeRelation']) {
            case self::MANY_TO_MANY:
                if (!is_array($callArgs[0])) $callArgs[0] = array($callArgs[0]);
                foreach ($callArgs[0] as $oneRelationModel) {
                    $query = new InternalQueryHelper();
                    $query->delete($configRelation['relationTable'])
                        ->where($configRelation['sourceField'], '=', '?')
                        ->where($configRelation['targetField'], '=', '?');

                    $query = static::$_dataSource->prepare($query->buildQuery());

                    $query->execute(array($this->{$configRelation['sourceField']}, $oneRelationModel->{$configRelation['targetField']}));
                }
                $isDeleted = true;
                break;
        }
        return $isDeleted;
    }

    /**
     * Set a relation value from magic setter
     * @param array $configRelation
     * @param $callArgs
     * @return bool
     * @throws Exception
     */
    private function _setRelation(array $configRelation, $callArgs)
    {
        $isSaved = false;
        switch ($configRelation['typeRelation']) {
            case self::ONE_TO_ONE:
                if ($callArgs[0] instanceof $configRelation['classRelation']) {
                    if ($callArgs[0]->isNew()) $callArgs[0]->save();
                    $this->{$configRelation['sourceField']} = $callArgs[0]->{$configRelation['targetField']};
                    foreach ($configRelation['autoGetFields'] as $oneField) {
                        $this->{$oneField} = $callArgs[0]->{$oneField};
                    }
                    $isSaved = true;
                }
                break;
            case self::ONE_TO_MANY:
                if (is_array($callArgs[0])) {
                    foreach ($callArgs[0] as $oneRelationModel) {
                        $oneRelationModel->{$configRelation['targetField']} = $this->{$configRelation['sourceField']};
                        $oneRelationModel->save();
                    }
                    $isSaved = true;
                }
                break;
            case self::MANY_TO_MANY:
                if (!is_array($callArgs[0])) $callArgs[0] = array($callArgs[0]);
                $testQueryHelper = new InternalQueryHelper();
                $testQueryHelper->select('count(*) as nb')->from("`" . $configRelation['relationTable'] . "`")
                    ->where("`" . $configRelation['sourceField'] . "`", '=', '?')
                    ->where("`" . $configRelation['targetField'] . "`", '=', '?');
                $testQuery = static::$_dataSource->prepare($testQueryHelper->buildQuery());

                $insertQuery = new InternalQueryHelper();
                $insertQuery->insertInto("`" . $configRelation['relationTable'] . "`")
                    ->values($configRelation['sourceField'], "?")
                    ->values($configRelation['targetField'], "?");
                $insertQuery = static::$_dataSource->prepare($insertQuery->buildQuery());

                foreach ($callArgs[0] as $oneRelationModel) {
                    if ($oneRelationModel->isNew()) $oneRelationModel->save();

                    // test if relation already exists
                    $testQuery->execute(array($this->{$configRelation['sourceField']}, $oneRelationModel->{$configRelation['targetField']}));
                    $errorcode = $testQuery->errorInfo();
                    if ($errorcode[0] != "00000") throw new Exception($errorcode[2]);
                    $testResult = $testQuery->fetch(\PDO::FETCH_ASSOC);
                    if ($testResult['nb'] == 0) {
                        // create link in relation table
                        $insertQuery->execute(array($this->{$configRelation['sourceField']}, $oneRelationModel->{$configRelation['targetField']}));
                        $errorcode = $insertQuery->errorInfo();
                        if ($errorcode[0] != "00000") throw new Exception($errorcode[2]);

                        $isSaved = true;
                    }
                }
                break;
        }

        return $isSaved;
    }

    /**
     * Get a relation value from magic getter
     * @param array $configRelation
     * @param array $callArgs
     * @return null
     */
    private function _getRelation(array $configRelation, $callArgs)
    {
        $where = $order = array();
        $limitStart = $limitEnd = null;

        // extract find criteria from args
        if (isset($callArgs[0]) && is_array($callArgs[0])) $where = $callArgs[0];
        if (isset($callArgs[1]) && is_array($callArgs[1])) $order = $callArgs[1];
        if (isset($callArgs[2]) && is_array($callArgs[2])) $limitStart = $callArgs[2];
        if (isset($callArgs[3]) && is_array($callArgs[3])) $limitEnd = $callArgs[3];

        $relationValue = null;
        switch ($configRelation['typeRelation']) {
            case self::ONE_TO_ONE:
                $classRelation = $configRelation['classRelation'];
                $where = array_merge(
                    $where,
                    array($configRelation['targetField'] => $this->{$configRelation['sourceField']})
                );
                $relationValue = $classRelation::findOne($where, $order);
                break;
            case self::ONE_TO_MANY:
                $classRelation = $configRelation['classRelation'];
                $where = array_merge(
                    $where,
                    array($configRelation['targetField'] => $this->{$configRelation['sourceField']})
                );
                $relationValue = $classRelation::find($where, $order, $limitStart, $limitEnd);
                break;
            case self::MANY_TO_MANY:
                $classRelation = $configRelation['classRelation'];

                $selectRelations = new InternalQueryHelper();
                $selectRelations
                    ->select("t.*")
                    ->from($classRelation::formatTableNameMySQL(), 't')
                    ->innerJoin($configRelation['relationTable'], $configRelation['relationTable'] . "." . $configRelation['targetField'] . " = t." . $configRelation['targetField']);

                // check one to one relation with auto get fields
                // and append needed fields to select
                $nbRelation = 0;
                foreach ($classRelation::$_relations as $uneRelation) {
                    if ($uneRelation['typeRelation'] == self::ONE_TO_ONE && count($uneRelation['autoGetFields']) > 0) {
                        // add auto get fields to select
                        foreach ($uneRelation['autoGetFields'] as &$oneField) $oneField = 'rel' . $nbRelation . "." . $oneField;
                        $selectRelations->select($uneRelation['autoGetFields']);

                        $selectRelations->leftJoin($uneRelation['classRelation']::formatTableNameMySQL() . ' rel' . $nbRelation,
                            'rel' . $nbRelation . '.`' . $uneRelation['targetField'] . '` = ' . $classRelation::formatTableNameMySQL() . '.`' . $uneRelation['sourceField'] . '`');
                        $nbRelation++;
                    }
                }
                $selectRelations->buildWhereFromArray(
                    array("`" . $configRelation['relationTable'] . "`." . $configRelation['sourceField'] => $this->{$configRelation['sourceField']})
                );

                $relationValue = new Collection(static::getDataSource(), $selectRelations, $classRelation);
                break;
        }

        return $relationValue;
    }

    /**
     * Format class name without namespace to store a relation name
     * @param $fullClassName
     * @return string
     */
    protected static function formatClassnameToRelationName($fullClassName)
    {

        if (strpos($fullClassName, '\\') !== false) {
            $fullClassName = explode('\\', $fullClassName);
            $fullClassName = array_pop($fullClassName);
        }

        return strtolower($fullClassName);
    }

    /**
     * Add a OneToOne relation
     * @param $sourceField          - model source field
     * @param $classRelation        - relation model classname
     * @param $targetField          - related model target field
     * @param array $autoGetFields  - field to auto get from relation when loading model
     * @param string $aliasRelation - override relation auto naming className with an alias
     *                                    (ex : for reflexive relation)
     * @throws Exception
     */
    protected static function addRelationOneToOne($sourceField, $classRelation, $targetField, $autoGetFields = array(), $aliasRelation = '')
    {
        if(!is_string($sourceField)) throw new Exception('$sourceField have to be a string');
        if(!is_string($classRelation)) throw new Exception('$classRelation have to be a string');
        if(!is_string($targetField)) throw new Exception('$targetField have to be a string');

        if (!class_exists($classRelation) || !new $classRelation() instanceof Model)
        throw new Exception("Class " . $classRelation . " doesn't exists or is not subclass of \PicORM\Model");

        if (!is_array($autoGetFields)) $autoGetFields = array($autoGetFields);

        $idRelation = self :: formatClassnameToRelationName($classRelation);
        if (!empty($aliasRelation)) $idRelation = $aliasRelation;

        static::$_relations[$idRelation] = array(
            'typeRelation' => self::ONE_TO_ONE,
            'classRelation' => $classRelation,
            'sourceField' => $sourceField,
            'targetField' => $targetField,
            'autoGetFields' => $autoGetFields
        );
    }

    /**
     * Add a OneToMany relation
     * @param $sourceField          - model source field
     * @param $classRelation        - relation model classname
     * @param $targetField          - related model target field
     * @param string $aliasRelation - override relation auto naming className with an alias
     * @throws Exception
     */
    protected static function addRelationOneToMany($sourceField, $classRelation, $targetField, $aliasRelation = '')
    {
        if (!class_exists($classRelation) || !new $classRelation() instanceof Model)
            throw new Exception("Class " . $classRelation . " doesn't exists or is not subclass of PicORM\Model");

        $idRelation = self :: formatClassnameToRelationName($classRelation);
        if (!empty($aliasRelation)) $idRelation = $aliasRelation;

        static::$_relations[$idRelation] = array(
            'typeRelation' => self::ONE_TO_MANY,
            'classRelation' => $classRelation,
            'sourceField' => $sourceField,
            'targetField' => $targetField,
        );
    }

    /**
     * Add a ManyToMany relation
     * @param $sourceField           - model source field
     * @param $classRelation         - relation model name
     * @param $targetField           - related model field
     * @param $relationTable         - mysql table containing the two models ID
     * @param string $aliasRelation  - override relation auto naming className
     * @throws Exception
     */
    protected static function addRelationManyToMany($sourceField, $classRelation, $targetField, $relationTable, $aliasRelation = '')
    {
        if (!class_exists($classRelation) || !new $classRelation() instanceof Model)
            throw new Exception("Class " . $classRelation . " doesn't exists or is not subclass of PicORM\Model");

        $idRelation = self :: formatClassnameToRelationName($classRelation);
        if (!empty($aliasRelation)) $idRelation = $aliasRelation;

        static::$_relations[$idRelation] = array(
            'typeRelation' => self::MANY_TO_MANY,
            'classRelation' => $classRelation,
            'sourceField' => $sourceField,
            'targetField' => $targetField,
            'relationTable' => $relationTable,
        );
    }

    /**
     * Return model array fetched from database with custom mysql query
     * @param $req
     * @param $params
     * @return static[]
     * @todo must return Collection
     */
    public static function findQuery($req, $params)
    {
        $query = static::$_dataSource->prepare($req);
        $query->execute($params);
        $fetch = $query->fetchAll(\PDO::FETCH_ASSOC);

        $collection = array();
        foreach ($fetch as $unRes) {
            $object = new static();
            $object->hydrate($unRes);
            $collection[] = $object;
        }
        return $collection;
    }

    /**
     * Return model collection fetched from database with criteria
     * @param array $where - associative array ex:
     *            simple criteria        array('idMarque' => 1)
     *            custom operator        array('idMarque' => array('operator' => '<=','value' => ''))
     *            raw SQL                array('idMarque' => array('IN (5,6,4)'))
     * @param array $order - associative array ex:array('libMarque'=>'ASC')
     * @param int $limitStart - int
     * @param int $limitEnd - int
     * @return Collection
     */
    public static function find($where = array(), $order = array(), $limitStart = null, $limitEnd = null)
    {
        self :: _validateModel();

        $queryHelper = static::buildSelectQuery(array("*"), $where, $order, $limitStart, $limitEnd);

        return new Collection(static::$_dataSource, $queryHelper, get_called_class());
    }

    /**
     * Find one model from criteria
     * @param array $where - associative array ex:
     *            simple criteria        array('idMarque' => 1)
     *            custom operator        array('idMarque' => array('operator' => '<=','value' => ''))
     *            raw SQL                array('idMarque' => array('IN (5,6,4)'))
     * @param array $order - associative array ex:array('libMarque'=>'ASC')
     * @return static
     */
    public static function findOne($where = array(), $order = array())
    {
        if ($dataModel = self::select(array('*'), $where, $order, 1)) {
            $model = new static();
            $model->hydrate($dataModel);
            return $model;
        } else
            return null;
    }

    /**
     * Test if model is already save in database
     * @return bool
     */
    public function isNew()
    {
        return $this->_isNew;
    }

    /**
     * Count number of model in database from criteria
     * @param array $where - associative array ex:
     *            simple criteria        array('idMarque' => 1)
     *            custom operator        array('idMarque' => array('operator' => '<=','value' => ''))
     *            raw SQL                array('idMarque' => array('IN (5,6,4)'))
     * @return int | null
     */
    public static function count($where = array())
    {
        $rawSqlFetch = self::select(array("count(*) as nb"), $where);
        return isset($rawSqlFetch[0]) && isset($rawSqlFetch[0]['nb']) ? $rawSqlFetch[0]['nb'] : null;
    }

    /**
     * Build an InternalQueryHelper to select models
     * @param array $fields - selected fields
     * @param array $where - associative array ex:
     *             simple criteria       array('idMarque' => 1)
     *             custom operator       array('idMarque' => array('operator' => '<=','value' => '5'))
     *    raw SQL without operator       array('idMarque' => array('IN (5,6,4)')
     * @param array $order - associative array ex:array('libMarque'=>'ASC')
     * @param int $limitStart - int
     * @param int $limitEnd - int
     * @return InternalQueryHelper
     */
    protected static function buildSelectQuery($fields = array('*'), $where = array(), $order = array(), $limitStart = null, $limitEnd = null)
    {
        $modelTableName = static::formatTableNameMySQL();

        // be sure that "*" is prefixed with model table name
        foreach ($fields as &$oneField) {
            if ($oneField == "*") {
                $oneField = $modelTableName . ".*";
                break;
            }
        }


        $helper = new InternalQueryHelper();

        $where = $helper->prefixWhereWithTable($where, $modelTableName);
        $orders = $helper->prefixOrderWithTable($order, $modelTableName);

        $helper->select($fields)
            ->from($modelTableName);

        // check one to one relation with auto get fields
        // and append necessary fields to select
        $nbRelation = 0;
        foreach (static::$_relations as $uneRelation) {
            if ($uneRelation['typeRelation'] == self::ONE_TO_ONE && count($uneRelation['autoGetFields']) > 0) {
                // add auto get fields to select
                foreach ($uneRelation['autoGetFields'] as &$oneField) $oneField = 'rel' . $nbRelation . "." . $oneField;
                $helper->select($uneRelation['autoGetFields']);

                $helper->leftJoin($uneRelation['classRelation']::formatTableNameMySQL() . ' rel' . $nbRelation,
                    'rel' . $nbRelation . '.`' . $uneRelation['targetField'] . '` = ' . $modelTableName . '.`' . $uneRelation['sourceField'] . '`');
                $nbRelation++;
            }
        }

        $helper->buildWhereFromArray($where);
        foreach ($orders as $orderField => $orderVal) {
            $helper->orderBy($orderField, $orderVal);
        }

        $helper->limit($limitStart, $limitEnd);

        return $helper;
    }

    /**
     * Build a select mysql query for this model from criteria in parameters
     * return a raw mysql fetch assoc
     * Using Raw SQL _setVal, assume that you properly filter user input
     *
     * @param array $fields - selected fields
     * @param array $where - associative array ex:
     *             simple criteria       array('idMarque' => 1)
     *             custom operator       array('idMarque' => array('operator' => '<=','value' => '5'))
     *    raw SQL without operator       array('idMarque' => array('IN (5,6,4)')
     * @param array $order - associative array ex:array('libMarque'=>'ASC')
     * @param int $limitStart - int
     * @param int $limitEnd - int
     * @param int $pdoFetchMode - PDO Fetch Mode (default : \PDO::FETCH_ASSOC)
     * @return array
     * @throws Exception
     */
    public static function select($fields = array('*'), $where = array(), $order = array(), $limitStart = null, $limitEnd = null, $pdoFetchMode = null)
    {
        // validate model PHP structure if necessary before using it
        static::_validateModel();

        $mysqlQuery = static::buildSelectQuery($fields, $where, $order, $limitStart, $limitEnd);
        $query = static::$_dataSource->prepare($mysqlQuery->buildQuery());
        $query->execute($mysqlQuery->getWhereParamsValues());

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") throw new Exception($errorcode[2]);

        if ($pdoFetchMode === null) {
            $pdoFetchMode = \PDO::FETCH_ASSOC;
        }
        if ($limitStart == 1 && ($limitEnd === null || $limitEnd === 1)) {
            return $query->fetch($pdoFetchMode);
        }
        return $query->fetchAll($pdoFetchMode);
    }

    /**
     * Hydrate model from a fetch assoc
     * including OneToOne relation auto get field
     * @param $data
     */
    public function hydrate($data)
    {
        // using reflection to check if property exist
        $reflection = new \ReflectionObject($this);
        foreach ($data as $k => $v) {
            // check if property really exists in class
            if ($reflection->hasProperty($k))
                $this->{$k} = $v;

            foreach (static::$_relations as $uneRelation) {
                // check if this is an auto get field from relation
                if ($uneRelation['typeRelation'] == self::ONE_TO_ONE && in_array($k, $uneRelation['autoGetFields'])) {
                    $this->{$k} = $v;
                    break;
                }
            }
        }
        $this->_isNew = false;
    }

    /**
     * Delete this model from database
     * @return array
     * @throws Exception
     */
    public function delete()
    {
        // validate model PHP structure if necessary before using it
        static::_validateModel();

        $query = new InternalQueryHelper();
        $query->delete(self::formatTableNameMySQL())
            ->where(static::$_primaryKey, "=", "?");

        $query = static::$_dataSource->prepare($query->buildQuery());
        $query->execute(array($this->{static::$_primaryKey}));

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") throw new Exception($errorcode[2]);

        $this->_isNew = true;

        return true;
    }

    /**
     * Update model field in database
     * @return bool
     * @throws Exception
     */
    private function update()
    {
        // validate model PHP structure if necessary before using it
        static::_validateModel();

        $helper = new InternalQueryHelper();
        $helper->update(static::$_tableName);
        $params = array();
        foreach (static::$_tableFields as $unChamp) {
            // array is for raw SQL value
            if (is_array($this->$unChamp) && isset($this->{$unChamp}[0])) {
                $helper->set($unChamp, $this->{$unChamp}[0]);
            } else {
                $helper->set($unChamp, '?');
                $params[] = $this->{$unChamp};
            }
        }
        $helper->where(self::formatTableNameMySQL() . ".`" . static::$_primaryKey . "`", "=", "?");

        $params[] = $this->{static::$_primaryKey};
        // var_dump($helper -> buildQuery());

        $query = static::$_dataSource->prepare($helper->buildQuery());
        $query->execute($params);

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") throw new Exception($errorcode[2]);

        return true;
    }

    /**
     * Insert model in database
     */
    private function insert()
    {
        // validate model PHP structure if necessary before using it
        static::_validateModel();

        $params = array();
        $queryHelp = new InternalQueryHelper();
        $queryHelp->insertInto(self::formatTableNameMySQL());

        // if primary key has forced value and is not present in tableField array
        if (!empty($this->{static::$_primaryKey}) && !in_array(static::$_primaryKey, static::$_tableFields)) {
            array_unshift(static::$_tableFields, static::$_primaryKey);
        } else {
            // use autoincrement for primary key
            $queryHelp->values(static::$_primaryKey, 'NULL');
        }

        foreach (static::$_tableFields as $unChamp) {
            // array is for raw SQL value
            if (is_array($this->$unChamp) && isset($this->{$unChamp}[0]))
                $val = $this->{$unChamp}[0];
            else {
                $val = '?';
                $params[] = $this->$unChamp;
            }

            $queryHelp->values($unChamp, $val);
        }

        $query = static::$_dataSource->prepare($queryHelp->buildQuery());
        $query->execute($params);

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") throw new Exception($errorcode[2]);

        $this->_isNew = false;

        // grab the last insert ID if empty PK (auto_increment)
        if (empty($this->{static::$_primaryKey}))
            $this->{static::$_primaryKey} = static::$_dataSource->lastInsertId();

        return true;
    }

    /**
     * Initiates a transaction
     * @return bool
     * @throws Exception
     */
    public static function begin()
    {
        if (!$result = static::$_dataSource->beginTransaction()) {
            throw new Exception("Transaction could not begin!");
        }
        return $result;
    }

    /**
     * Rolls back a transaction
     *
     * @return boolean
     */
    public static function rollback()
    {
        return static::$_dataSource->rollBack();
    }

    /**
     * Commits a transaction
     *
     * @return boolean
     */
    public static function commit()
    {
        return static::$_dataSource->commit();
    }

    /**
     * Return PDO instance
     * @return \PDO
     */
    public static function getDataSource()
    {
        return static::$_dataSource;
    }

    /**
     * Set PDO instance
     */
    public static function setDataSource($_dataSource)
    {
        static::$_dataSource = $_dataSource;
    }
}