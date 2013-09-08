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
     * PicORM Global or Entity specific Configuration
     * @var array
     */
    protected static $_configuration = array();

    /**
     * Default PicORM configuration
     * @var array
     */
    protected static $_defaultConfiguration = array(
        'cache' => false, // !!TODO!!
        'datasource' => null
    );

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
     * Validate if this entity is correcty implemented
     * @throws Exception
     */
    protected static function _validateEntity()
    {
        // assure that check is only did once
        if (!isset(self::$validationStatus[static::$_databaseName.static::$_tableName])) {

            $subClassName = get_class(new static());
            if (static::$_tableName === null) throw new Exception($subClassName.'::$_tableName must be implemented');
            if (static::$_primaryKey === null) throw new Exception($subClassName.'::$_primaryKey must be implemented');
            if (static::$_relations === null) throw new Exception($subClassName.'::$_relations must be implemented');
            if (static::$_tableFields === null) throw new Exception($subClassName.'::$_tableFields must be implemented');

            // entity OOP structure is OK to declare relationship
            static::defineRelations();

            self::$validationStatus[static::$_databaseName.static::$_tableName] = true;
        }
    }

    /**
     * Set PicORM or Entity configuration
     * @param array $configuration
     */
    final public static function configure(array $configuration)
    {
        // overide with default configuration if not present
        $configuration += static::$_defaultConfiguration;

        if (isset($configuration['datasource']) && $configuration['datasource'] instanceof \PDO)
            static::$_dataSource = $configuration['datasource'];

        static::$_configuration = $configuration;
    }

    /**
     * Format database name to using it in SQL query
     * @return string
     */
    public static function formatDatabaseNameMySQL()
    {
        return !empty(static::$_databaseName) ? "`".static::$_databaseName."`." : '';
    }

    /**
     * Format table name to using it in SQL query
     * @return string
     */
    public static function formatTableNameMySQL()
    {
        return self::formatDatabaseNameMySQL()."`".static::$_tableName."`";
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
        $retour = array(static::$_primaryKey => $this->{static::$_primaryKey});
        foreach (static::$_tableFields as $unChamp) {
            $retour[$unChamp] = $this->{$unChamp};
        }
        return json_encode($retour);
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
            $toCall = '_'.$matches[1].'Relation';
            // calling getRelation() or setRelation() or unsetRelation()
            return $this->$toCall(static::$_relations[strtolower($matches[2])], $args);
        } else {
            throw new Exception("Fonction {$method} inconnue");
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
                    $query = static::$_dataSource->prepare("
                       DELETE
                       FROM `".$configRelation['relationTable']."`
                       WHERE `".$configRelation['sourceField']."` = ? AND `".$configRelation['targetField']."` = ?
                   ");

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
        $retour = false;
        switch ($configRelation['typeRelation']) {
            case self::ONE_TO_ONE:
                if ($callArgs[0] instanceof $configRelation['classRelation']) {
                    if ($callArgs[0]->isNew()) $callArgs[0]->save();
                    $this->{$configRelation['sourceField']} = $callArgs[0]->{$configRelation['targetField']};
                    foreach ($configRelation['autoGetFields'] as $oneField) {
                        $this->{$oneField} = $callArgs[0]->{$oneField};
                    }
                    $retour = true;
                }
                break;
            case self::ONE_TO_MANY:
                if (is_array($callArgs[0])) {
                    foreach ($callArgs[0] as $oneRelationEntity) {
                        $oneRelationEntity->{$configRelation['targetField']} = $this->{$configRelation['sourceField']};
                        $oneRelationEntity->save();
                    }
                }
                break;
            case self::MANY_TO_MANY:
                if (!is_array($callArgs[0])) $callArgs[0] = array($callArgs[0]);
                foreach ($callArgs[0] as $oneRelationEntity) {
                    if ($oneRelationEntity->isNew()) $oneRelationEntity->save();

                    // test if relation already exists
                    $query = static::$_dataSource->prepare("
                        SELECT count(*) as nb
                        FROM `".$configRelation['relationTable']."`
                        WHERE `".$configRelation['sourceField']."` = ? AND `".$configRelation['targetField']."` = ?
                    ");

                    $query->execute(array($this->{$configRelation['sourceField']}, $oneRelationEntity->{$configRelation['targetField']}));
                    $errorcode = $query->errorInfo();
                    if ($errorcode[0] != "00000") throw new Exception($errorcode[2]);

                    $res = $query->fetch(\PDO::FETCH_ASSOC);
                    if ($res['nb'] == 0) {
                        // create link in relation table
                        $query = static::$_dataSource->prepare("
                            INSERT INTO `".$configRelation['relationTable']."` (`".$configRelation['sourceField']."` ,`".$configRelation['targetField']."`)
                            VALUES (?, ?);
                        ");
                        $query->execute(array($this->{$configRelation['sourceField']}, $oneRelationEntity->{$configRelation['targetField']}));
                        $errorcode = $query->errorInfo();
                        if ($errorcode[0] != "00000") throw new Exception($errorcode[2]);
                    }
                }
                break;
        }

        return $retour;
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

                $joinStr = $fieldsStr = '';

                $joinData = $classRelation::getOneToOneRelationMysqlJoinData('t');
                if (count($joinData['joinFields']) > 0) {
                    $joinStr = $joinData['joinStr'];
                    $fieldsStr = ",".implode(',', $joinData['joinFields']);
                }

                $req = "
                    SELECT t.* $fieldsStr
                    FROM ".$classRelation::formatTableNameMySQL()." t
                    INNER JOIN ".$configRelation['relationTable']."
                    ON ".$configRelation['relationTable'].".".$configRelation['targetField']." = t.".$configRelation['targetField']."
                    $joinStr
                    WHERE `".$configRelation['relationTable']."`.".$configRelation['sourceField']." = ?
                ";

                $relationValue = $classRelation::findFromQuery($req, array($this->{$configRelation['sourceField']}));
                break;
        }

        return $relationValue;
    }

    /**
     * Add a OneToOne relation
     * @param $sourceField             - entity source field
     * @param $classRelation           - relation entity name
     * @param $targetField             - related entity target field
     * @param array $autoGetFields     - field to autoget from relation when loading entity
     * @param string $aliasRelation    - override relation autonaming with className with an alias
     *                                    (ex : for reflexive relation)
     * @throws Exception
     */
    protected static function addRelationOneToOne($sourceField, $classRelation, $targetField, $autoGetFields = array(), $aliasRelation = '')
    {
        if (!class_exists($classRelation) || !new $classRelation() instanceof Entity)
            throw new Exception("Class ".$classRelation." doesnt exists or is not subclass of PicORM");

        if (!is_array($autoGetFields) && is_string($autoGetFields)) $autoGetFields = array($autoGetFields);

        // check for idRelation override with aliasRelation
        $idRelation = !empty($aliasRelation) ? $aliasRelation : $classRelation;

        static::$_relations[strtolower($idRelation)] = array(
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
            throw new Exception("Class ".$classRelation." doesnt exists or is not subclass of PicORM");

        $idRelation = !empty($aliasRelation) ? $aliasRelation : $classRelation;
        $idRelation = strtolower($idRelation);

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
            throw new Exception("Class ".$classRelation." doesnt exists or is not subclass of PicORM");

        $idRelation = !empty($aliasRelation) ? $aliasRelation : $classRelation;
        $idRelation = strtolower($idRelation);

        static::$_relations[$idRelation] = array(
            'typeRelation' => self::MANY_TO_MANY,
            'classRelation' => $classRelation,
            'sourceField' => $sourceField,
            'targetField' => $targetField,
            'relationTable' => $relationTable,
        );
    }

    /**
     * Return entity collection fetched from database with criteria
     * @param array $where        - associative array ex:
     *            simple criteria        array('idMarque' => 1)
     *            custom operator        array('idMarque' => array('operator' => '<=','value' => ''))
     *            raw SQL                array('idMarque' => array('IN (5,6,4)'))
     * @param array $order        - associative array ex:array('libMarque'=>'ASC')
     * @param int $limitStart     - int
     * @param int $limitEnd       - int
     * @return static[]
     */
    public static function find($where = array(), $order = array(), $limitStart = null, $limitEnd = null)
    {
        $res = self::select(array("*"), $where, $order, $limitStart, $limitEnd);
        $retour = array();
        foreach ($res as $unRes) {
            $object = new static();
            $object->hydrate($unRes);
            $retour[] = $object;
        }
        return $retour;
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
     * Find one entity from criteria
     * @param array $where        - associative array ex:
     *            simple criteria        array('idMarque' => 1)
     *            custom operator        array('idMarque' => array('operator' => '<=','value' => ''))
     *            raw SQL                array('idMarque' => array('IN (5,6,4)'))
     * @param array $order        - associative array ex:array('libMarque'=>'ASC')
     * @return static
     */
    public static function findOne($where = array(), $order = array())
    {
        $collection = self::find($where, $order, 1);
        return isset($collection[0]) ? $collection[0] : null;
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
     * @param array $where        - associative array ex:
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
     * Build a select mysql query for this entity from criteria in parameters
     * return a raw mysql fetch assoc
     * Using Raw SQL, assume that you properly filter user input
     *
     * @param array $fields    - selected fields
     * @param array $where              - associative array ex:
     *             simple criteria       array('idMarque' => 1)
     *             custom operator       array('idMarque' => array('operator' => '<=','value' => '5'))
     *    raw SQL without operator       array('idMarque' => array('IN (5,6,4)')
     * @param array $order              - associative array ex:array('libMarque'=>'ASC')
     * @param int $limitStart           - int
     * @param int $limitEnd             - int
     * @param int $pdoFetchMode         - PDO Fetch Mode (default : \PDO::FETCH_ASSOC)
     * @return array
     * @throws Exception
     */
    public static function select($fields = array('*'), $where = array(), $order = array(), $limitStart = null, $limitEnd = null, $pdoFetchMode = null)
    {
        // validate entity PHP structure if necessary before using it
        static::_validateEntity();

        $limitStr = $whereStr = $orderStr = $joinStr = '';
        $sqlParams = array();

        // building where clause
        if (count($where) > 0) {
            $k = 0;
            $whereStr = "WHERE ";
            foreach ($where as $nomColonne => $oneCritera) {
                if ($k > 0) $whereStr .= ' AND ';

                $val = '?';
                $operator = "=";
                if (is_array($oneCritera)) {
                    // using raw mysql
                    if (count($oneCritera) == 1 && isset($oneCritera[0])) {
                        $operator = '';
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
                                $sqlParams[] = $oneCritera['value'];
                            }
                        }
                    }
                } else {
                    $sqlParams[] = $oneCritera;
                }

                $whereStr .= static::formatTableNameMySQL().'.`'.$nomColonne.'` '.$operator.' '.$val;
                $k++;
            }
        }

        // building order clause
        if (count($order) > 0) {
            $k = 0;
            $orderStr = "ORDER BY ";
            foreach ($order as $orderField => $sensOrder) {
                // allow only ASC or DESC or empty string in $sensOrder
                // ORDER BY RAND() possible with filling only $orderField with "RAND()"
                if ($sensOrder != "ASC" && $sensOrder != "DESC" && $sensOrder != "") continue;

                if ($k > 0) $orderStr .= ',';

                $orderStr .= $orderField.' '.$sensOrder;
                $k++;
            }
        }

        // building limit clause
        if ($limitStart !== null) {
            $limitStr = 'LIMIT '.(int)$limitStart;
            if ($limitEnd !== null) {
                $limitStr .= ','.(int)$limitEnd;
            }
        }

        // check one to one relation with autogetFields
        // and append necessary fields to select
        $joinData = static::getOneToOneRelationMysqlJoinData();
        if (count($joinData['joinFields']) > 0) {
            $joinStr = $joinData['joinStr'];
            $fields = array_merge($fields, $joinData['joinFields']);
        }

        // be sure that "*" is prefixed with entity table name
        foreach ($fields as &$oneField) {
            if ($oneField == "*") {
                $oneField = self::formatTableNameMySQL().".*";
                break;
            }
        }

        $mysqlQuery = "
   			SELECT ".implode(",", $fields)."
   			FROM ".self::formatTableNameMySQL()."
   			$joinStr
   			$whereStr
   			$orderStr
   			$limitStr";

        $query = static::$_dataSource->prepare($mysqlQuery);
        $query->execute($sqlParams);

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") throw new Exception($errorcode[2]);

        if ($pdoFetchMode === null) {
            $pdoFetchMode = \PDO::FETCH_ASSOC;
        }
        return $query->fetchAll($pdoFetchMode);
    }

    /**
     * Get array with SQL subquery to fetch OneToOne relation autoget fields
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
                // add autoget field to select
                foreach ($uneRelation['autoGetFields'] as &$oneField) $oneField = 'rel'.$nbRelation.".".$oneField;
                $joinData['joinFields'] = array_merge($joinData['joinFields'], $uneRelation['autoGetFields']);

                // create join for relation
                $joinData['joinStr'] .= '
                LEFT JOIN '.$uneRelation['classRelation']::formatTableNameMySQL().' rel'.$nbRelation.'
                ON rel'.$nbRelation.'.`'.$uneRelation['targetField'].'` = '.$tableName.'.'.$uneRelation['sourceField'].' ';

                $nbRelation++;
            }
        }
        return $joinData;
    }

    /**
     * Hydrate entity from a fetch assoc
     * including OneToOne relation autoget field
     * @param $data
     */
    private function hydrate($data)
    {
        // using reflection to check if property exist
        $reflection = new \ReflectionObject($this);
        foreach ($data as $k => $v) {
            // check if property really exists in class
            if ($reflection->hasProperty($k))
                $this->{$k} = $v;

            foreach (static::$_relations as $uneRelation) {
                // check if this is an autogetfield from relation
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

        $req = "DELETE FROM ".self::formatTableNameMySQL()." WHERE ".static::$_primaryKey." = ?";

        $query = static::$_dataSource->prepare($req);
        $query->execute(array($this->{static::$_primaryKey}));

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") throw new Exception($errorcode[2]);

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

        $fieldsStr = '';
        foreach (static::$_tableFields as $k => $unChamp) {
            if ($k > 0) {
                $fieldsStr .= ',';
            }
            if (is_array($this->$unChamp) && isset($this->{$unChamp}[0])) {
                $fieldsStr .= "`$unChamp` = ".$this->{$unChamp}[0];
            } else {
                $fieldsStr .= "`$unChamp` =  ?";
                $params[] = $this->$unChamp;
            }
        }

        $req = "UPDATE ".self::formatTableNameMySQL()."
				SET $fieldsStr
				WHERE ".self::formatTableNameMySQL().".`".static::$_primaryKey."` = ?;";

        $params[] = $this->{static::$_primaryKey};

        $query = static::$_dataSource->prepare($req);
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

        $champs = $params = '';
        $paramsValues = array();

        // if primary key has forced value and is not present in tableField array
        if (!empty($this->{static::$_primaryKey}) && !in_array(static::$_primaryKey, static::$_tableFields)) {
            array_unshift(static::$_tableFields, static::$_primaryKey);
        } else {
            // use autoincrement for primary key
            $champs .= "`".static::$_primaryKey."`,";
            $params .= 'NULL,';
        }

        foreach (static::$_tableFields as $unChamp) {
            $champs .= "`".$unChamp."`,";

            if (is_array($this->$unChamp) && isset($this->{$unChamp}[0])) {
                $params .= ','.$this->{$unChamp}[0];
            } else {
                $params .= '?,';
                $paramsValues[] = $this->$unChamp;
            }

        }
        $params = rtrim($params, ",");
        $champs = rtrim($champs, ",");

        $query = static::$_dataSource->prepare("INSERT INTO ".self::formatTableNameMySQL()." ($champs) VALUES ($params);");
        $query->execute($paramsValues);

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") throw new Exception($errorcode[2]);

        $this->_isNew = false;

        $this->{static::$_primaryKey} = static::$_dataSource->lastInsertId();

        return true;
    }
}