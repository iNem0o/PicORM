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
 * @category Model
 * @package  PicORM
 * @author   iNem0o <contact@inem0o.fr>
 * @license  LGPL http://opensource.org/licenses/lgpl-license.php
 * @link     https://github.com/iNem0o/PicORM
 */

namespace PicORM;

/**
 * Class Model
 *
 * @category Model
 * @package  PicORM
 * @author   iNem0o <contact@inem0o.fr>
 * @license  LGPL http://opensource.org/licenses/lgpl-license.php
 * @link     https://github.com/iNem0o/PicORM
 */
abstract class Model
{
    /**
     * Table primary key
     *
     * @var null
     */
    protected static $_primaryKey = null;

    /**
     * Model Database name
     *
     * @var string
     */
    protected static $_databaseName = null;

    /**
     * Model table name
     *
     * @var string
     */
    protected static $_tableName = null;

    /**
     * Model relations
     *
     * @var array
     */
    protected static $_relations = null;

    /**
     * SQL fields from table without primary key
     *
     * @var array
     */
    protected static $_tableFields = null;

    /**
     * Array to store OOP declaration status of each Model subclass
     *
     * @var array
     */
    private static $_validationStatus = array();

    /**
     * Datasource instance
     *
     * @var \PDO
     */
    protected static $_dataSource;

    /**
     * Define if model have been saved
     *
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
     * Define relations
     * ::addRelationOneToOne()
     * ::addRelationOneToMany()
     *
     * @throws Exception
     *
     * @return void
     */
    protected static function defineRelations()
    {

    }


    /**
     * Validate if this model is correctly implemented
     *
     * @return void
     * @throws Exception
     */
    protected static function _validateModel()
    {
        // check if model is already validate
        if (!isset(self::$_validationStatus[static::$_databaseName . static::$_tableName])) {

            // grab actual class name from late state binding
            $subClassName = get_class(new static());

            // check model OOP static structure is OK
            if (static::$_tableName === null) {
                throw new Exception($subClassName . '::$_tableName must be implemented');
            }
            if (static::$_primaryKey === null) {
                throw new Exception($subClassName . '::$_primaryKey must be implemented');
            }
            if (static::$_tableFields === null) {
                throw new Exception($subClassName . '::$_tableFields must be implemented');
            }

            // if user has implemented $_relations for this model, call the defineRelations() method
            if (static::$_relations !== null) {
                static::defineRelations();
            }

            // store model validation status
            self::$_validationStatus[static::$_databaseName . static::$_tableName] = true;
        }
    }


    /**
     * Format database name to using it in SQL query
     *
     * @return string
     */
    public static function formatDatabaseNameMySQL()
    {
        return !empty(static::$_databaseName) ? "`" . static::$_databaseName . "`." : '';
    }

    /**
     * Modify the database name on the fly
     * @param $databaseName
     */
    public static function setDatabaseName($databaseName) {
        static::$_databaseName = $databaseName;
    }

    /**
     * Format table name to using it in SQL query
     *
     * @return string
     */
    public static function formatTableNameMySQL()
    {
        return self::formatDatabaseNameMySQL() . "`" . static::$_tableName . "`";
    }

    /**
     * Modify the table name on the fly
     * @param $tableName
     */
    public static function setTableName($tableName) {
        static::$_tableName = $tableName;
    }


    /**
     * Return primary key field name
     *
     * @return string
     */
    public static function getPrimaryKeyFieldName()
    {
        return static::$_primaryKey;
    }


    /**
     * Save model in database
     *
     * @return bool
     */
    public function save()
    {
        if ($this->_isNew) {
            return $this->insert();
        } else {
            return $this->update();
        }
    }


    /**
     * Return model data in JSON
     *
     * @return string - json representation of the model
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }


    /**
     * Return model data in an array
     *
     * @param bool $includePrimary - Include or not primary key in returned array
     *
     * @return array
     */
    public function toArray($includePrimary = true)
    {
        if ($includePrimary) {
            $array = array(static::$_primaryKey => $this->{static::$_primaryKey});
        } else {
            $array = array();
        }

        foreach (static::$_tableFields as $unChamp) {
            $array[$unChamp] = $this->{$unChamp};
        }

        return $array;
    }


    /**
     * Return table field from Model
     *
     * @param bool $includePrimary - Include or not primary key in returned array
     *
     * @return array
     */
    public static function getModelFields($includePrimary = true)
    {
        $fields = array();

        if ($includePrimary) {
            $fields[] = static::$_primaryKey;
        }
        foreach (static::$_tableFields as $unChamp) {
            $fields[] = $unChamp;
        }

        return $fields;
    }


    /**
     * Magic call which create accessors for relation
     *
     * @param string $method - method name
     * @param array  $args   - method arguments
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        // validate model PHP structure if necessary
        static::_validateModel();

        // if method name begin with 'get' or 'set' or 'unset'
        // and finished with a knew relation name
        if (preg_match('/^(get|set|unset)(.+)/', $method, $matches)
            && array_key_exists(
                strtolower($matches[2]),
                static::$_relations
            )
        ) {

            // build relation accessor method name
            $toCall = '_' . $matches[1] . 'Relation';

            // calling getRelation() or setRelation() or unsetRelation() with relation data
            return $this->$toCall(static::$_relations[strtolower($matches[2])], $args);
        } else {
            // not a valid function
            throw new Exception("unknown method {$method}");
        }
    }


    /**
     * Unset a relation value from magic setter
     *
     * @param array $configRelation - Relation configuration
     * @param array $callArgs       - Models instance
     *
     * @todo unset other relations type
     * @return bool
     */
    private function _unsetRelation(array $configRelation, $callArgs)
    {
        $isDeleted = false;
        switch ($configRelation['typeRelation']) {
            case self::MANY_TO_MANY:
                // assure that we provide an array of relation to unset
                if (!is_array($callArgs[0])) {
                    $callArgs[0] = array($callArgs[0]);
                }
                foreach ($callArgs[0] as $oneRelationModel) {
                    // build delete query for this relationship
                    $query = new InternalQueryHelper();
                    $query->delete($configRelation['relationTable'])
                          ->where($configRelation['sourceField'], '=', '?')
                          ->where($configRelation['targetField'], '=', '?');

                    $query = static::$_dataSource->prepare($query->buildQuery());

                    $query->execute(
                          array(
                              $this->{$configRelation['sourceField']},
                              $oneRelationModel->{$configRelation['targetField']}
                          )
                    );
                }
                $isDeleted = true;
                break;
        }

        return $isDeleted;
    }


    /**
     * Set a relation value from magic setter
     *
     * @param array $configRelation - Relation configuration
     * @param array $callArgs       - Models instance
     *
     * @throws Exception
     *
     * @return bool
     */
    private function _setRelation(array $configRelation, $callArgs)
    {
        $isSaved = false;
        switch ($configRelation['typeRelation']) {
            case self::ONE_TO_ONE:
                if ($callArgs[0] instanceof $configRelation['classRelation']) {
                    // if relation model is new, we need to store it before
                    if ($callArgs[0]->isNew()) {
                        $callArgs[0]->save();
                    }

                    // set model field corresponding to the relation
                    $this->{$configRelation['sourceField']} = $callArgs[0]->{$configRelation['targetField']};

                    // if we have autoget field, grab and store them inside model
                    foreach ($configRelation['autoGetFields'] as $oneField) {
                        $this->{$oneField} = $callArgs[0]->{$oneField};
                    }
                    $isSaved = true;
                }
                break;
            case self::ONE_TO_MANY:
                if (is_array($callArgs[0])) {
                    // foreach models in relation
                    foreach ($callArgs[0] as $oneRelationModel) {
                        // set model field corresponding to the relation
                        $oneRelationModel->{$configRelation['targetField']} = $this->{$configRelation['sourceField']};
                        $oneRelationModel->save();
                    }
                    $isSaved = true;
                }
                break;
            case self::MANY_TO_MANY:
                // assure that we have an array of model
                if (!is_array($callArgs[0])) {
                    $callArgs[0] = array($callArgs[0]);
                }

                // prepare a query for testing if relation already exists
                $testQueryHelper = new InternalQueryHelper();
                $testQueryHelper->select('count(*) as nb')->from("`" . $configRelation['relationTable'] . "`")
                                ->where("`" . $configRelation['sourceField'] . "`", '=', '?')
                                ->where("`" . $configRelation['targetField'] . "`", '=', '?');
                $testQuery = static::$_dataSource->prepare($testQueryHelper->buildQuery());

                // prepare a query to insert relation between models
                $insertQuery = new InternalQueryHelper();
                $insertQuery->insertInto("`" . $configRelation['relationTable'] . "`")
                            ->values($configRelation['sourceField'], "?")
                            ->values($configRelation['targetField'], "?");
                $insertQuery = static::$_dataSource->prepare($insertQuery->buildQuery());

                // for each model in relation
                foreach ($callArgs[0] as $oneRelationModel) {
                    // if model is new, we need to store it before setting relation
                    if ($oneRelationModel->isNew()) {
                        $oneRelationModel->save();
                    } else {
                        // test if relation already exists between the two models
                        $testQuery->execute(
                                  array(
                                      $this->{$configRelation['sourceField']},
                                      $oneRelationModel->{$configRelation['targetField']}
                                  )
                        );
                        $errorcode = $testQuery->errorInfo();
                        if ($errorcode[0] != "00000") {
                            throw new Exception($errorcode[2]);
                        }
                        $testResult = $testQuery->fetch(\PDO::FETCH_ASSOC);

                        // relation already set
                        if ($testResult['nb'] > 0) {
                            continue;
                        }
                    }

                    // create link in relation table
                    $insertQuery->execute(
                                array(
                                    $this->{$configRelation['sourceField']},
                                    $oneRelationModel->{$configRelation['targetField']}
                                )
                    );
                    $errorcode = $insertQuery->errorInfo();

                    // check if data is stored with no error
                    if ($errorcode[0] != "00000") {
                        throw new Exception($errorcode[2]);
                    }

                    $isSaved = true;
                }
                break;
        }

        return $isSaved;
    }


    /**
     * Get a relation value using magic getter
     *
     * @param array $configRelation - Relation configuration
     * @param array $callArgs       - Models instance
     *
     * @throw Exception
     *
     * @return null
     */
    private function _getRelation(array $configRelation, $callArgs)
    {
        $where      = $order = array();
        $limitStart = $limitEnd = null;

        // extract find criteria from args
        if (isset($callArgs[0]) && is_array($callArgs[0])) {
            $where = $callArgs[0];
        }

        // extract order from args
        if (isset($callArgs[1]) && is_array($callArgs[1])) {
            $order = $callArgs[1];
        }

        // extract limit from args
        if (isset($callArgs[2]) && is_numeric($callArgs[2])) {
            $limitStart = $callArgs[2];
        }
        if (isset($callArgs[3]) && is_numeric($callArgs[3])) {
            $limitEnd = $callArgs[3];
        }

        $relationValue = null;
        switch ($configRelation['typeRelation']) {
            case self::ONE_TO_ONE:
                $classRelation = $configRelation['classRelation'];
                if (is_subclass_of($classRelation, 'PicORM\Model')) {
                    // add model relation relation field to where
                    $where = array_merge(
                        $where,
                        array($configRelation['targetField'] => $this->{$configRelation['sourceField']})
                    );

                    // grab the related model
                    $relationValue = $classRelation::findOne($where, $order);
                } else {
                    throw new Exception(sprintf("%s must be a subclass of PicORM\Model", $classRelation));
                }
                break;
            case self::ONE_TO_MANY:
                $classRelation = $configRelation['classRelation'];
                if (is_subclass_of($classRelation, 'PicORM\Model')) {
                    // add model relation relation field to where
                    $where = array_merge(
                        $where,
                        array($configRelation['targetField'] => $this->{$configRelation['sourceField']})
                    );

                    // grab the related models
                    $relationValue = $classRelation::find($where, $order, $limitStart, $limitEnd);
                } else {
                    throw new Exception(sprintf("%s must be a subclass of PicORM\Model", $classRelation));
                }
                break;
            case self::MANY_TO_MANY:
                $classRelation = $configRelation['classRelation'];
                if (is_subclass_of($classRelation, 'PicORM\Model')) {
                    // create the select query for related models using relation table
                    $selectRelations = new InternalQueryHelper();
                    $selectRelations
                        ->select("t.*")
                        ->from($classRelation::formatTableNameMySQL(), 't')
                        ->innerJoin(
                        $configRelation['relationTable'],
                        $configRelation['relationTable'] . "." . $configRelation['targetField'] . " = t." . $configRelation['targetField']
                        );
                    if($order !== null) {
                        foreach ($order as $orderField => $orderVal) {
                            $selectRelations->orderBy($orderField, $orderVal);
                        }
                    }
                    $selectRelations -> limit($limitStart, $limitEnd);

                    $where = $selectRelations->prefixWhereWithTable($where,'t');
                    $selectRelations->buildWhereFromArray($where);
                    // check one to one relation with auto get fields
                    // and append needed fields to select
                    $nbRelation = 0;
                    foreach ($classRelation::$_relations as $uneRelation) {
                        if ($uneRelation['typeRelation'] == self::ONE_TO_ONE
                            && count($uneRelation['autoGetFields']) > 0
                        ) {
                            // add auto get fields to select
                            foreach ($uneRelation['autoGetFields'] as &$oneField) {
                                $oneField = 'rel' . $nbRelation . "." . $oneField;
                            }

                            // add fields to select
                            $selectRelations->select($uneRelation['autoGetFields']);

                            // add query join corresponding to the relation
                            $relationFieldName = 'rel' . $nbRelation . '.`' . $uneRelation['targetField'];
                            $relationField     = 't' . '.`' . $uneRelation['sourceField'] . '`';
                            $selectRelations
                                ->leftJoin(
                                $uneRelation['classRelation']::formatTableNameMySQL() . ' rel' . $nbRelation,
                                $relationFieldName . '` = ' . $relationField
                                );

                            // increment relation count used in prefix
                            $nbRelation++;
                        }
                    }

                    $relatedFieldName = "`" . $configRelation['relationTable'] . "`." . $configRelation['sourceField'];
                    $conditionValue   = $this->{$configRelation['sourceField']};
                    $selectRelations->buildWhereFromArray(array($relatedFieldName => $conditionValue));

                    // create collection with this model datasource instance
                    // hydrating $classRelation model and using $selectRelation query helper
                    $relationValue = new Collection(static::getDataSource(), $selectRelations, $classRelation);
                } else {
                    throw new Exception(sprintf("%s must be a subclass of PicORM\Model", $classRelation));
                }
                break;
        }

        return $relationValue;
    }


    /**
     * Format class name without namespace to store a relation name
     *
     * @param string $fullClassName - Class name to normalize (with namespace)
     *
     * @return string
     */
    protected static function formatClassnameToRelationName($fullClassName)
    {
        // test if namespace present and remove them
        if (strpos($fullClassName, '\\') !== false) {
            $fullClassName = explode('\\', $fullClassName);
            $fullClassName = array_pop($fullClassName);
        }

        return strtolower($fullClassName);
    }


    /**
     * Add a OneToOne relation
     *
     * @param string $sourceField   - model source field
     * @param string $classRelation - relation model classname
     * @param string $targetField   - related model target field
     * @param array  $autoGetFields - field to auto get from relation when loading model
     * @param string $aliasRelation - override relation auto naming with an alias (ex : for reflexive relation)
     *
     * @throws Exception
     *
     * @return void
     */
    protected static function addRelationOneToOne(
        $sourceField,
        $classRelation,
        $targetField,
        $autoGetFields = array(),
        $aliasRelation = ''
    )
    {
        if (!is_string($sourceField)) {
            throw new Exception('$sourceField have to be a string');
        }
        if (!is_string($classRelation)) {
            throw new Exception('$classRelation have to be a string');
        }
        if (!is_string($targetField)) {
            throw new Exception('$targetField have to be a string');
        }

        // test is related class is a PicORM model
        if (!class_exists($classRelation) || !new $classRelation() instanceof Model) {
            throw new Exception("Class " . $classRelation . " doesn't exists or is not subclass of \PicORM\Model");
        }

        // test if its needed to autoget all fields
        if ($autoGetFields === true) {
            $autoGetFields = $classRelation::getModelFields();
        }

        // store autogetfields as an array
        if (!is_array($autoGetFields)) {
            $autoGetFields = array($autoGetFields);
        }

        // create relation id with normalized classRelation name
        $idRelation = self :: formatClassnameToRelationName($classRelation);

        // override the relation's id if an alias is present
        if (!empty($aliasRelation)) {
            $idRelation = strtolower($aliasRelation);
        }

        // store new relation in model
        static::$_relations[$idRelation] = array(
            'typeRelation'  => self::ONE_TO_ONE,
            'classRelation' => $classRelation,
            'sourceField'   => $sourceField,
            'targetField'   => $targetField,
            'autoGetFields' => $autoGetFields
        );
    }


    /**
     * Add a OneToMany relation
     *
     * @param        $sourceField   - model source field
     * @param        $classRelation - relation model classname
     * @param        $targetField   - related model target field
     * @param string $aliasRelation - override relation auto naming className with an alias
     *
     * @throws Exception
     */
    protected static function addRelationOneToMany($sourceField, $classRelation, $targetField, $aliasRelation = '')
    {
        // test is related class is a PicORM model
        if (!class_exists($classRelation) || !new $classRelation() instanceof Model) {
            throw new Exception("Class " . $classRelation . " doesn't exists or is not subclass of PicORM\Model");
        }

        // create relation id with normalized classRelation name
        $idRelation = self :: formatClassnameToRelationName($classRelation);

        // override the relation's id if an alias is present
        if (!empty($aliasRelation)) {
            $idRelation = strtolower($aliasRelation);
        }

        // store new relation in model
        static::$_relations[$idRelation] = array(
            'typeRelation'  => self::ONE_TO_MANY,
            'classRelation' => $classRelation,
            'sourceField'   => $sourceField,
            'targetField'   => $targetField,
        );
    }


    /**
     * Add a ManyToMany relation
     *
     * @param        $sourceField   - model source field
     * @param        $classRelation - relation model name
     * @param        $targetField   - related model field
     * @param        $relationTable - mysql table containing the two models ID
     * @param string $aliasRelation - override relation auto naming className
     *
     * @throws Exception
     */
    protected static function addRelationManyToMany(
        $sourceField,
        $classRelation,
        $targetField,
        $relationTable,
        $aliasRelation = ''
    )
    {
        // test is related class is a PicORM model
        if (!class_exists($classRelation) || !new $classRelation() instanceof Model) {
            throw new Exception("Class " . $classRelation . " doesn't exists or is not subclass of PicORM\Model");
        }

        // create relation id with normalized classRelation name
        $idRelation = self :: formatClassnameToRelationName($classRelation);

        // override the relation's id if an alias is present
        if (!empty($aliasRelation)) {
            $idRelation = strtolower($aliasRelation);
        }

        // store new relation in model
        static::$_relations[$idRelation] = array(
            'typeRelation'  => self::MANY_TO_MANY,
            'classRelation' => $classRelation,
            'sourceField'   => $sourceField,
            'targetField'   => $targetField,
            'relationTable' => $relationTable,
        );
    }


    /**
     * Return model array fetched from database with custom mysql query
     *
     * @param $query
     * @param $params
     *
     * @return static[]
     * @todo must return Collection
     */
    public static function findQuery($query, $params)
    {
        $query = static::$_dataSource->prepare($query);
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
     *
     * @param array $where      - associative array ex:
     *                          simple criteria        array('idMarque' => 1)
     *                          custom operator        array('idMarque' => array('operator' => '<=','value' => ''))
     *                          raw SQL                array('idMarque' => array('IN (5,6,4)'))
     * @param array $order      - associative array ex:array('libMarque'=>'ASC')
     * @param int   $limitStart - int
     * @param int   $limitEnd   - int
     *
     * @return Collection
     */
    public static function find($where = array(), $order = array(), $limitStart = null, $limitEnd = null)
    {
        // validate model PHP structure if necessary
        self :: _validateModel();

        // build a query helper with parameters
        $queryHelper = static::buildSelectQuery(array("*"), $where, $order, $limitStart, $limitEnd);

        // create a collection instance for called model with model datasource and custom created queryhelper
        return new Collection(static::$_dataSource, $queryHelper, get_called_class());
    }


    /**
     * Find one model from criteria
     *
     * @param array $where - associative array ex:
     *                     simple criteria        array('idMarque' => 1)
     *                     custom operator        array('idMarque' => array('operator' => '<=','value' => ''))
     *                     raw SQL                array('idMarque' => array('IN (5,6,4)'))
     * @param array $order - associative array ex:array('libMarque'=>'ASC')
     *
     * @return static
     */
    public static function findOne($where = array(), $order = array())
    {
        // try to fetch a model from database
        if ($dataModel = self::select(array('*'), $where, $order, 1)) {

            // hydrate new model instance with fetched data
            $model = new static();
            $model->hydrate($dataModel);

            return $model;
        } else {
            return null;
        }
    }


    /**
     * Test if model is already save in database
     *
     * @return bool
     */
    public function isNew()
    {
        return $this->_isNew;
    }


    /**
     * Count number of model in database from criteria
     *
     * @param array $where - associative array ex:
     *                     simple criteria        array('idMarque' => 1)
     *                     custom operator        array('idMarque' => array('operator' => '<=','value' => ''))
     *                     raw SQL                array('idMarque' => array('IN (5,6,4)'))
     *
     * @return int | null
     */
    public static function count($where = array())
    {
        // fetch the count with $where parameters
        $rawSqlFetch = self::select(array("count(*) as nb"), $where);

        return isset($rawSqlFetch[0]) && isset($rawSqlFetch[0]['nb']) ? (int)$rawSqlFetch[0]['nb'] : null;
    }


    /**
     * Build an InternalQueryHelper to select models
     *
     * @param array $fields     - selected fields
     * @param array $where      - associative array ex:
     *                          simple criteria       array('idMarque' => 1)
     *                          custom operator       array('idMarque' => array('operator' => '<=','value' => '5'))
     *                          raw SQL without operator       array('idMarque' => array('IN (5,6,4)')
     * @param array $order      - associative array ex:array('libMarque'=>'ASC')
     * @param int   $limitStart - int
     * @param int   $limitEnd   - int
     *
     * @return InternalQueryHelper
     */
    protected static function buildSelectQuery(
        $fields = array('*'),
        $where = array(),
        $order = array(),
        $limitStart = null,
        $limitEnd = null
    )
    {
        // get the formatted model mysql table name with database name
        $modelTableName = static::formatTableNameMySQL();

        // be sure that "*" is prefixed with model table name
        foreach ($fields as &$oneField) {
            if ($oneField == "*") {
                $oneField = $modelTableName . ".*";
                break;
            }
        }

        // create and helper to build a sql query
        $helper = new InternalQueryHelper();

        // prefix columns with model table name
        $where  = $helper->prefixWhereWithTable($where, $modelTableName);
        $orders = $helper->prefixOrderWithTable($order, $modelTableName);

        // starting build select
        $helper->select($fields)
               ->from($modelTableName);

        // check one to one relation with auto get fields
        // and append necessary fields to select
        $nbRelation = 0;
        foreach (static::$_relations as $uneRelation) {
            if ($uneRelation['typeRelation'] == self::ONE_TO_ONE && count($uneRelation['autoGetFields']) > 0) {
                // prefix fields
                foreach ($uneRelation['autoGetFields'] as &$oneField) {
                    $oneField = 'rel' . $nbRelation . "." . $oneField;
                }
                // add fields to select
                $helper->select($uneRelation['autoGetFields']);

                // add query join corresponding to the relation
                $helper->leftJoin(
                       $uneRelation['classRelation']::formatTableNameMySQL() . ' rel' . $nbRelation,
                       'rel' . $nbRelation . '.`' . $uneRelation['targetField'] . '` = ' . $modelTableName . '.`' . $uneRelation['sourceField'] . '`'
                );

                // increment relation count used in prefix
                $nbRelation++;
            }
        }

        // build where clause
        $helper->buildWhereFromArray($where);

        // build order clause
        foreach ($orders as $orderField => $orderVal) {
            $helper->orderBy($orderField, $orderVal);
        }

        // build limit clause
        $helper->limit($limitStart, $limitEnd);

        return $helper;
    }


    /**
     * Build a select mysql query for this model from criteria in parameters
     * return a raw mysql fetch assoc
     * Using Raw SQL _setVal, assume that you properly filter user input
     *
     * @param array $fields       - selected fields
     * @param array $where        - associative array ex:
     *                            simple criteria       array('idMarque' => 1)
     *                            custom operator       array('idMarque' => array('operator' => '<=','value' => '5'))
     *                            raw SQL without operator       array('idMarque' => array('IN (5,6,4)')
     * @param array $order        - associative array ex:array('libMarque'=>'ASC')
     * @param int   $limitStart   - int
     * @param int   $limitEnd     - int
     * @param int   $pdoFetchMode - PDO Fetch Mode (default : \PDO::FETCH_ASSOC)
     *
     * @return array
     * @throws Exception
     */
    public static function select(
        $fields = array('*'),
        $where = array(),
        $order = array(),
        $limitStart = null,
        $limitEnd = null,
        $pdoFetchMode = null
    )
    {
        // validate model PHP structure if necessary
        static::_validateModel();

        // build and execute query
        $mysqlQuery = static::buildSelectQuery($fields, $where, $order, $limitStart, $limitEnd);
        $query      = static::$_dataSource->prepare($mysqlQuery->buildQuery());
        $query->execute($mysqlQuery->getWhereParamsValues());

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") {
            throw new Exception($errorcode[2]);
        }

        // if no fetch mode specified fallback to FETCH_ASSOC
        if ($pdoFetchMode === null) {
            $pdoFetchMode = \PDO::FETCH_ASSOC;
        }

        // if limit say its a findOne only fetch
        if ($limitStart == 1 && ($limitEnd === null || $limitEnd === 1)) {
            return $query->fetch($pdoFetchMode);
        }

        // fetch and return data from database
        return $query->fetchAll($pdoFetchMode);
    }


    /**
     * Hydrate model from a fetch assoc
     * including OneToOne relation auto get field
     *
     * @param $data
     * @param $strictLoad - if true, all fields from $data will be loaded
     */
    public function hydrate($data, $strictLoad = true)
    {
        // using reflection to check if property exist
        $reflection = new \ReflectionObject($this);
        foreach ($data as $k => $v) {
            // if strictLoad is disabled, all properties are allowed to be hydrated
            if (!$strictLoad) {
                $this->{$k} = $v;
                continue;
            }
            // check if property really exists in class
            if ($reflection->hasProperty($k)) {
                $this->{$k} = $v;
                continue;
            }
            // test relation auto get fields
            foreach (static::$_relations as $uneRelation) {
                // check if this field is in auto get from relation
                if ($uneRelation['typeRelation'] == self::ONE_TO_ONE && in_array($k, $uneRelation['autoGetFields'])) {
                    $this->{$k} = $v;
                    break;
                }
            }
        }

        // model is not new anymore
        $this->_isNew = false;
    }


    /**
     * Delete this model from database
     *
     * @return array
     * @throws Exception
     */
    public function delete()
    {
        // validate model PHP structure if necessary
        static::_validateModel();

        // build delete query helper for this model
        $query = new InternalQueryHelper();
        $query->delete(self::formatTableNameMySQL())
              ->where(static::$_primaryKey, "=", "?");

        // delete model from database
        $query = static::$_dataSource->prepare($query->buildQuery());
        $query->execute(array($this->{static::$_primaryKey}));

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") {
            throw new Exception($errorcode[2]);
        }

        // model is not stored anymore in database
        $this->_isNew = true;

        return true;
    }


    /**
     * Update model field in database
     *
     * @return bool
     * @throws Exception
     */
    private function update()
    {
        // validate model PHP structure if necessary
        static::_validateModel();

        // build update query on model table
        $helper = new InternalQueryHelper();
        $helper->update(self::formatTableNameMySQL());

        // setting model fields value
        $params = array();
        foreach (static::$_tableFields as $unChamp) {
            // array is for raw SQL value
            if (is_array($this->$unChamp) && isset($this->{$unChamp}[0])) {
                $helper->set($unChamp, $this->{$unChamp}[0]);
            } else {
                // Mysql prepared value
                $helper->set($unChamp, '?');
                $params[] = $this->{$unChamp};
            }
        }

        // restrict with model primary key
        $helper->where(self::formatTableNameMySQL() . ".`" . static::$_primaryKey . "`", "=", "?");
        $params[] = $this->{static::$_primaryKey};

        // update model in database
        $query = static::$_dataSource->prepare($helper->buildQuery());
        $query->execute($params);

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") {
            throw new Exception($errorcode[2]);
        }

        return true;
    }


    /**
     * Insert model in database
     */
    private function insert()
    {
        // validate model PHP structure if necessary
        static::_validateModel();

        // create insert query for this model
        $queryHelp = new InternalQueryHelper();
        $queryHelp->insertInto(self::formatTableNameMySQL());

        // if primary key has forced value and is not present in tableField array
        if (!empty($this->{static::$_primaryKey}) && !in_array(static::$_primaryKey, static::$_tableFields)) {
            array_unshift(static::$_tableFields, static::$_primaryKey);
        } else {
            // use autoincrement for primary key
            $queryHelp->values(static::$_primaryKey, 'NULL');
        }

        // save model fields
        $params = array();
        foreach (static::$_tableFields as $unChamp) {
            // array is for raw SQL value
            if (is_array($this->$unChamp) && isset($this->{$unChamp}[0])) {
                $val = $this->{$unChamp}[0];
            } else {
                // mysql prepared value
                $val      = '?';
                $params[] = $this->$unChamp;
            }

            $queryHelp->values($unChamp, $val);
        }

        // execute insert query
        $query = static::$_dataSource->prepare($queryHelp->buildQuery());
        $query->execute($params);

        // check for mysql error
        $errorcode = $query->errorInfo();
        if ($errorcode[0] != "00000") {
            throw new Exception($errorcode[2]);
        }

        // model is saved, not new anymore
        $this->_isNew = false;

        // if empty PK grab the last insert ID for auto_increment fields
        if (empty($this->{static::$_primaryKey})) {
            $this->{static::$_primaryKey} = static::$_dataSource->lastInsertId();
        }

        return true;
    }


    /**
     * Initiates a transaction
     *
     * @return bool
     * @throws Exception
     */
    public static function begin()
    {
        // trying to start a mysql transation
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
     *
     * @return \PDO
     */
    public static function getDataSource()
    {
        return static::$_dataSource;
    }


    /**
     * Set PDO instance
     *
     * @param $_dataSource
     */
    public static function setDataSource($_dataSource)
    {
        static::$_dataSource = $_dataSource;
    }
}
