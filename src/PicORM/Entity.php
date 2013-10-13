<?php
namespace PicORM;
/**
 * PicORM is a simple and light PHP Object-Relational Mapping
 */
abstract class Entity
{
    /**
     * Table primary key
     * @var null
     */
    protected static $_primaryKey = null;

    /**
     * Entity Database name
     * @var string
     */
    protected static $_databaseName = null;

    /**
     * Entity table name
     * @var string
     */
    protected static $_tableName = null;

    /**
     * Entity relations
     * @var array
     */
    protected static $_relations = null;

    /**
     * SQL fields from table without primary key
     * @var array
     */
    protected static $_tableFields = null;

    /**
     * Array to store OOP declaration status of each Entity subclass
     * @var array
     */
    private static $validationStatus = array();

    /**
     * Datasource instance
     * @var \PDO
     */
    protected static $_dataSource;

    /**
     * Define if entity have been saved
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
     * Can declare entity relations with calling
     * ::addRelationOneToOne()
     * ::addRelationOneToMany()
     * @return mixed
     */
    protected static function defineRelations()
    {
    }

    /**
     * Validate if this entity is correctly implemented
     * @throws Exception
     */
    protected static function _validateEntity()
    {
        // assure that check is only did once
        if (!isset(self::$validationStatus[static::$_databaseName . static::$_tableName])) {

            $subClassName = get_class(new static());
            if (static::$_tableName === null) throw new Exception($subClassName . '::$_tableName must be implemented');
            if (static::$_primaryKey === null) throw new Exception($subClassName . '::$_primaryKey must be implemented');
            if (static::$_relations === null) throw new Exception($subClassName . '::$_relations must be implemented');
            if (static::$_tableFields === null) throw new Exception($subClassName . '::$_tableFields must be implemented');

            // entity OOP structure is OK to declare relationship
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
     * Save entity in database
     */
    public function save()
    {
        if ($this->_isNew)
            $this->insert();
        else
            $this->update();
    }

    /**
     * Return entity data in JSON
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
        static::_validateEntity();

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
     * @return bool
     */
    private function _unsetRelation(array $configRelation, $callArgs)
    {
        $isDeleted = false;
        switch ($configRelation['typeRelation']) {
            case self::MANY_TO_MANY:
                if (!is_array($callArgs[0])) $callArgs[0] = array($callArgs[0]);
                foreach ($callArgs[0] as $oneRelationEntity) {
                    $query = new InternalQueryHelper();
                    $query->delete($configRelation['relationTable'])
                        ->where($configRelation['sourceField'], '=', '?')
                        ->where($configRelation['targetField'], '=', '?');

                    $query = static::$_dataSource->prepare($query->buildQuery());

                    $query->execute(array($this->{$configRelation['sourceField']}, $oneRelationEntity->{$configRelation['targetField']}));
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
                    foreach ($callArgs[0] as $oneRelationEntity) {
                        $oneRelationEntity->{$configRelation['targetField']} = $this->{$configRelation['sourceField']};
                        $oneRelationEntity->save();
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

                foreach ($callArgs[0] as $oneRelationEntity) {
                    if ($oneRelationEntity->isNew()) $oneRelationEntity->save();

                    // test if relation already exists
                    $testQuery->execute(array($this->{$configRelation['sourceField']}, $oneRelationEntity->{$configRelation['targetField']}));
                    $errorcode = $testQuery->errorInfo();
                    if ($errorcode[0] != "00000") throw new Exception($errorcode[2]);
                    $testResult = $testQuery->fetch(\PDO::FETCH_ASSOC);
                    if ($testResult['nb'] == 0) {
                        // create link in relation table
                        $insertQuery->execute(array($this->{$configRelation['sourceField']}, $oneRelationEntity->{$configRelation['targetField']}));
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

        // extract criteria from args
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
                // and append necessary fields to select
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
                $selectRelations->where("`" . $configRelation['relationTable'] . "`." . $configRelation['sourceField'], '=', '?');

                $relationValue = $classRelation::findFromQuery($selectRelations->buildQuery(), array($this->{$configRelation['sourceField']}));
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
     * @param $sourceField - entity source field
     * @param $classRelation - relation entity name
     * @param $targetField - related entity target field
     * @param array $autoGetFields - field to auto get from relation when loading entity
     * @param string $aliasRelation - override relation auto naming with className with an alias
     *                                    (ex : for reflexive relation)
     * @throws Exception
     */
    protected static function addRelationOneToOne($sourceField, $classRelation, $targetField, $autoGetFields = array(), $aliasRelation = '')
    {
        if (!class_exists($classRelation) || !new $classRelation() instanceof Entity)
            throw new Exception("Class " . $classRelation . " doesn't exists or is not subclass of \PicORM\Entity");

        if (!is_array($autoGetFields) && is_string($autoGetFields)) $autoGetFields = array($autoGetFields);

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
     * @param $sourceField
     * @param $classRelation
     * @param $targetField
     * @param string $aliasRelation
     * @throws Exception
     */
    protected static function addRelationOneToMany($sourceField, $classRelation, $targetField, $aliasRelation = '')
    {
        if (!class_exists($classRelation) || !new $classRelation() instanceof Entity)
            throw new Exception("Class " . $classRelation . " doesn't exists or is not subclass of PicORM\Entity");

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
     * @param $sourceField
     * @param $classRelation
     * @param $targetField
     * @param $relationTable
     * @param string $aliasRelation
     * @throws Exception
     */
    protected static function addRelationManyToMany($sourceField, $classRelation, $targetField, $relationTable, $aliasRelation = '')
    {
        if (!class_exists($classRelation) || !new $classRelation() instanceof Entity)
            throw new Exception("Class " . $classRelation . " doesn't exists or is not subclass of PicORM\Entity");

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
     * Return entity collection fetched from database with custom mysql query
     * @param $req
     * @param $params
     * @return static[]
     */
    public static function findFromQuery($req, $params)
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
     * Return entity collection fetched from database with criteria
     * @param array $where - associative array ex:
     *            simple criteria        array('idMarque' => 1)
     *            custom operator        array('idMarque' => array('operator' => '<=','value' => ''))
     *            raw SQL                array('idMarque' => array('IN (5,6,4)'))
     * @param array $order - associative array ex:array('libMarque'=>'ASC')
     * @param int $limitStart - int
     * @param int $limitEnd - int
     * @return static[]
     */
    public static function find($where = array(), $order = array(), $limitStart = null, $limitEnd = null)
    {
        self :: _validateEntity();

        $queryHelper = static::buildSelectQuery(array("*"), $where, $order, $limitStart, $limitEnd);

        return new EntityCollection(static::$_dataSource, $queryHelper, get_called_class());
    }

    /**
     * Find one entity from criteria
     * @param array $where - associative array ex:
     *            simple criteria        array('idMarque' => 1)
     *            custom operator        array('idMarque' => array('operator' => '<=','value' => ''))
     *            raw SQL                array('idMarque' => array('IN (5,6,4)'))
     * @param array $order - associative array ex:array('libMarque'=>'ASC')
     * @return static
     */
    public static function findOne($where = array(), $order = array())
    {
        if ($dataEntity = self::select(array('*'), $where, $order, 1)) {
            $entity = new static();
            $entity->hydrate($dataEntity);
            return $entity;
        } else
            return null;
    }

    /**
     * Test if entity is already save in database
     * @return bool
     */
    public function isNew()
    {
        return $this->_isNew;
    }

    /**
     * Count number of entity in database from criteria
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
     * Build an InternalQueryHelper to select entities
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
    public static function buildSelectQuery($fields = array('*'), $where = array(), $order = array(), $limitStart = null, $limitEnd = null)
    {
        $entityTableName = static::formatTableNameMySQL();

        // be sure that "*" is prefixed with entity table name
        foreach ($fields as &$oneField) {
            if ($oneField == "*") {
                $oneField = $entityTableName . ".*";
                break;
            }
        }


        $helper = new InternalQueryHelper();

        $where = $helper->prefixWhereWithTable($where, $entityTableName);
        $orders = $helper->prefixOrderWithTable($order, $entityTableName);

        $helper->select($fields)
            ->from($entityTableName);

        // check one to one relation with auto get fields
        // and append necessary fields to select
        $nbRelation = 0;
        foreach (static::$_relations as $uneRelation) {
            if ($uneRelation['typeRelation'] == self::ONE_TO_ONE && count($uneRelation['autoGetFields']) > 0) {
                // add auto get fields to select
                foreach ($uneRelation['autoGetFields'] as &$oneField) $oneField = 'rel' . $nbRelation . "." . $oneField;
                $helper->select($uneRelation['autoGetFields']);

                $helper->leftJoin($uneRelation['classRelation']::formatTableNameMySQL() . ' rel' . $nbRelation,
                    'rel' . $nbRelation . '.`' . $uneRelation['targetField'] . '` = ' . $entityTableName . '.`' . $uneRelation['sourceField'] . '`');
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
     * Build a select mysql query for this entity from criteria in parameters
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
        // validate entity PHP structure if necessary before using it
        static::_validateEntity();

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
     * Get array with SQL subquery to fetch OneToOne relation auto get fields
     * @param string $tableAlias
     * @return array
     */
    protected static function getOneToOneRelationMysqlJoinData($tableAlias = '')
    {

        // validate entity PHP structure if necessary before using it
        static::_validateEntity();

        $joinData = array(
            'joinStr' => '',
            'joinFields' => array()
        );
        $nbRelation = 0;
        $tableName = !empty($tableAlias) ? $tableAlias : self::formatTableNameMySQL();
        foreach (static::$_relations as $uneRelation) {
            if ($uneRelation['typeRelation'] == self::ONE_TO_ONE && count($uneRelation['autoGetFields']) > 0) {
                // add auto get field to select
                foreach ($uneRelation['autoGetFields'] as &$oneField) $oneField = 'rel' . $nbRelation . "." . $oneField;
                $joinData['joinFields'] = array_merge($joinData['joinFields'], $uneRelation['autoGetFields']);

                // create join for relation
                $joinData['joinStr'] .= '
                LEFT JOIN ' . $uneRelation['classRelation']::formatTableNameMySQL() . ' rel' . $nbRelation . '
                ON rel' . $nbRelation . '.`' . $uneRelation['targetField'] . '` = ' . $tableName . '.' . $uneRelation['sourceField'] . ' ';

                $nbRelation++;
            }
        }
        return $joinData;
    }

    /**
     * Hydrate entity from a fetch assoc
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
     * Delete this entity from database
     * @return array
     * @throws Exception
     */
    public function delete()
    {
        // validate entity PHP structure if necessary before using it
        static::_validateEntity();

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
     * Update entity field in database
     * @return bool
     * @throws Exception
     */
    private function update()
    {
        // validate entity PHP structure if necessary before using it
        static::_validateEntity();

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
     * Insert entity in database
     */
    private function insert()
    {
        // validate entity PHP structure if necessary before using it
        static::_validateEntity();

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