![PicORM](/examples/picorm.png?raw=true "PicORM")

# a lightweight PHP ORM.
PicORM will help you to map your MySQL database rows into PHP object and create relations between them.<br>
PicORM is an Active Record pattern implementation easy to install and use.

[![Build Status](http://travis-ci.org/iNem0o/PicORM.png)](http://travis-ci.org/iNem0o/PicORM)
[![Latest Stable Version](https://poser.pugx.org/picorm/picorm/version.png)](https://packagist.org/packages/picorm/picorm)
[![Total Downloads](https://poser.pugx.org/picorm/picorm/downloads.png)](https://packagist.org/packages/picorm/picorm)

### Some stuff you need to know
- You can't have multiple primary key yet
- You can't use Many To Many relations with data in relation table yet
- Fork and pull request are encouraged!

### Install
**From composer**<br>

Install composer in your www folder with ``curl -sS https://getcomposer.org/installer | php``

Create a ``composer.json`` file with

```json
{
    "require": {
        "picorm/picorm": "0.0.6"
    }
}
```
Install PicORM with ``php composer.phar install``


**From source**<br>
Clone ``https://github.com/iNem0o/PicORM`` repository and include ``PicORM`` autoload with

```php
    require('path/to/PicORM/src/autoload.inc.php');
```

**Load and configure PicORM**<br>
Before using ``PicORM`` you have to configure it.
``datasource`` is the only required parameter and have to be a PDO instance

```php
\PicORM\PicORM::configure(array(
	'datasource' => new PDO('mysql:dbname=DBNAME;host=HOST', 'DBLOGIN', 'DBPASSWD')
));
```


## Model
**Implements a Model**<br>
First you have to create a table, which your model will be mapped to


```sql
    CREATE TABLE `brands` (
      `idBrand` int(11) NOT NULL AUTO_INCREMENT,
      `nameBrand` varchar(100) NOT NULL,
      `noteBrand` float DEFAULT 0,
      PRIMARY KEY (`idBrand`)
    ) ENGINE=MyISAM;
```

Next create a class which extends ``\PicORM\Model``
You have to implements some static parameters to describe your MySQL table schema, if you forgot one of them, a ``\PicORM\Exception`` will remind you

*Required*<br>
``protected static $_tableName`` MySQL table name<br>
``protected static $_primaryKey`` table primary key field name<br>
``protected static $_tableFields`` array with all mysql table fields name without primary key

*Optional*<br>
``protected static $_databaseName`` name of the database if different from datasource main DB

and then, add one public property by table field with ``public $fieldName``

**Brand model declaration**

```php
    class Brand extends \PicORM\Model
    {
        protected static $_tableName = 'brands';
        protected static $_primaryKey = 'idBrand';

        protected static $_tableFields = array(
            'nameBrand',
            'noteBrand'
        );

        public $idBrand;
        public $nameBrand;
        public $noteBrand;

    }
```

**Create and save**

```php
// creating new model
	$brand = new Brand();

// setting field
	$brand -> nameBrand = 'Peugeot';

// save model
	$brand -> save();
```

**Update and delete**

```php
// Criteria with exact value (idBrand=10)
    $brand = Brand :: findOne(array('idBrand' => 10));

// setting model property
	$brand -> nameBrand = 'Audi';

// save model
	$brand -> save();
	
// save model
	$brand -> delete();
```

## find() and findOne()
Every subclass of ``Model`` inherit of static methods ``find()`` and ``findOne()``.<br>
Theses methods are used to hydrate models from database rows.

```php
/**
 * Find one model from criteria, allowing to order
 * @param array $where - associative
 * @param array $order - associative array
 */
public static function findOne($where = array(), $order = array())

/**
 * Return Collection instance from criteria, allowing to order and limit result
 * @param array $where - associative array
 * @param array $order - associative array
 * @param int $limitStart - int
 * @param int $limitEnd - int
 * @return Collection
 */
public static function find($where = array(),$order = array(), $limitStart = null, $limitEnd = null)
```


**How to use $where parameter** <br>
this parameter is data for building a WHERE mysql clause

```php
// simple criteria
	$where = array('field' => 1);

// custom operator
	$where = array('field' => array('operator' => '<=',
									'value'    => '20')
					);

// raw sql value (NOT prepared, beware of SQL injection)
	$where = array(
					'field'    => array('IN (5,6,4)'),
					'dateTime' => array('NOW()')
					);
```

**How to use $order parameter**<br>
This parameter is data for building an ORDER mysql clause

```php
     $order = array(
		'field'=>'ASC',
		'field2' => DESC,
		'RAND() => ''
	 )
```

## Collections
Collections in PicORM are created by ``::find()`` method, accessible statically on each ``\PicORM\Model`` subclass.<br>
Once you have a fresh \PicORM\Collection instance, data is not fetched yet. Fetching is done only when you try to access data using one of these ways

### Collections usage
```php
// php array access
	$firstResult = $collection[0];
	
// counting collection
	$nbResults = count($collection);
	
// using getter
	$firstResult = $collection->get(0);
	
// iterate over the collection
	foreach($collection as $model)
	
// or manual fetching / re-fetching
	$collection->fetch();
```

An \PicORM\Collection instance can execute UPDATE and DELETE queries on the collection members before fetching data,
this way using ``update()`` or ``delete()`` method produce only one MySQL query based on ``find()`` restriction parameters.

```php
// Delete all models in collection
    $collection = Brand::find(array('noteBrand' => 10))
                         -> delete();

// Update and set noteBrand = 5 to collection
    $collection = Brand::find(array('noteBrand' => array('IN(9,10,11)')))
                         -> update(array('noteBrand' => 5));
						 
```
### Collections pagination
Pagination in collection is based on MySQL FOUND_ROWS().
Remembering that collection is not fetched until you use it, when you have a Collection instance, its easy to activate pagination with

```php
$carCollection = Car::find();           // grab all car from database
$carCollection->activePagination(50);   // asking for 50 models by page
$carCollection->paginate(1);            // asking for first page
```

You have now access to 2 more methods.

```php
$nbTotalPages = $carCollection -> getTotalPages();
$nbTotalModels = $carCollection -> foundModels();
```

### Collection advanced query
You are able to alter the fetch query before it execution using the ```getQueryHelper()``` method on the collection.
You will get an instance of ```InternalQueryHelper``` which you can manipulate. Once you are done, just set
the collection query helper with ```setQueryHelper($helper)```

That way you can load custom data inside a model instance.

```php
$cars  = Car::find();
$queryBuilder = $cars->getQueryHelper();
$queryBuilder->select("COUNT(idTag) as nbTags");
$queryBuilder->leftJoin('car_has_tag cht', 'cht.idCar = cars.idCar');
$queryBuilder->groupBy("cars.idCar");
$cars->setQueryHelper($queryBuilder);
```

## Relations between models
Using relations will need you to add a property and a method to your model subclass.<br>
``protected static $_relations = array();``  needed to be implemented to store model relations
``protected static function defineRelations() { }`` method to declare your relation

overriding ``defineRelations()`` in your subclass allow you to declare your model specific relations
using one of the 3 next methods.

```php
/**
 * Add a OneToOne relation
 * @param $sourceField          - model source field
 * @param $classRelation        - relation model classname
 * @param $targetField          - related model target field
 * @param array $autoGetFields  - field to auto get from relation when loading model
 * @param string $aliasRelation - override relation auto naming className with an alias
 *                                    (ex : for reflexive relation)
 */
protected static function addRelationOneToOne($sourceField, $classRelation, $targetField, $autoGetFields = array(), $aliasRelation = '')

/**
 * Add a OneToMany relation
 * @param $sourceField          - model source field
 * @param $classRelation        - relation model classname
 * @param $targetField          - related model target field
 * @param string $aliasRelation - override relation auto naming className with an alias
 */
protected static function addRelationOneToMany($sourceField, $classRelation, $targetField, $aliasRelation = '')

/**
 * Add a ManyToMany relation
 * @param $sourceField           - model source field
 * @param $classRelation         - relation model name
 * @param $targetField           - related model field
 * @param $relationTable         - mysql table containing the two models ID
 * @param string $aliasRelation  - override relation auto naming className
 */
protected static function addRelationManyToMany($sourceField, $classRelation, $targetField, $relationTable, $aliasRelation = '')
```

**Using relation**

This example will use the following MySQL schema

```sql
CREATE TABLE `brands` (
	`idBrand` int(11) NOT NULL AUTO_INCREMENT,
	`nameBrand` varchar(100) NOT NULL,
	`noteBrand` float DEFAULT 0,
PRIMARY KEY (`idBrand`)
) ENGINE=MyISAM;

CREATE TABLE  `cars` (
	`idCar` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`idBrand` INT NOT NULL,
	`nameCar` VARCHAR(100) NOT NULL
) ENGINE = MYISAM ;

CREATE TABLE `car_have_tag` (
	`idCar` INT NOT NULL,
	`idTag` INT NOT NULL,
PRIMARY KEY (`idCar`,`idTag`)
) ENGINE = MYISAM ;

CREATE TABLE `tags` (
	`idTag` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`libTag` VARCHAR(255) NOT NULL
) ENGINE = MYISAM ;
```

First you have to declare your 3 models and their relations

```php
class Brand extends Model
{
	protected static $_tableName = 'brands';
	protected static $_primaryKey = "idBrand";
	protected static $_relations = array();

	protected static $_tableFields = array(
		'nameBrand',
		'noteBrand'
	);

	public $idBrand;
	public $nameBrand;
	public $noteBrand;

	protected static function defineRelations()
	{
		// create a relation between Brand and Car
		// based on this.idBrand = Car.idBrand
		self::addRelationOneToMany('idBrand', 'Car', 'idBrand');
	}
}

class Car extends Model
{
	protected static $_tableName = 'cars';
	protected static $_primaryKey = "idCar";
	protected static $_relations = array();

	protected static $_tableFields = array(
		'idBrand',
		'nameCar'
	);

	public $idCar;
	public $idBrand;
	public $nameCar = '';

	protected static function defineRelations()
	{
		// create a relation between Car and Brand
		// based on this.idBrand = Brand.idBrand
		// nameBrand is added to autoget fields which is automatically fetched
		// when model is loaded
		self::addRelationOneToOne('idBrand', 'Brand', 'idBrand', 'nameBrand');

		// create a relation between Car and Tag using a relation table car_have_tag
		self::addRelationManyToMany("idCar","Tag","idTag","car_have_tag");
	}
}

class Tag extends Model
{
	protected static $_tableName = 'tags';
	protected static $_primaryKey = "idTag";
	protected static $_relations = array();

	protected static $_tableFields = array(
		'libTag',
	);

	public $idTag;
	public $libTag = '';

	protected static function defineRelations()
	{
		// create a relation between Tag and Car using a relation table car_have_tag
		self::addRelationManyToMany('idTag','Car','idCar','car_have_tag');
	}
}
```

Now you can start to create and manipulates related models

```php

// creating a brand
    $brand = new Brand();
    $brand -> nameBrand = "Peugeot";
    $brand -> save();

// creating a car
    $car = new Car();
    $car -> nameCar = "205 GTi";

// setting car's brand
    $car -> setBrand($brand);

// other way to setting car's brand
    $car -> idBrand = $brand -> idBrand;
    $car -> save();

// if we look for our car
    $car = Car :: findOne(array('nameCar' => '205 GTi'));
	
// we can get brand of the car
    $car -> getBrand();

// or we can access brand name directly because it has been added to relation auto get fields
    $car -> nameBrand;
		
```

As you declare a one to many relation from Brand to Car you can also using setter and getter on the other side

```php	
// get all cars from brand
// method return instance of \PicORM\Collection
    foreach($brand -> getCar() as $cars)

// get all cars from brand with custom criteria
// parameters are same as find() method
// method return instance of \PicORM\Collection
    $brand -> getCar($where,$order,$limitStart,$limitStop);
```

Many to many relations are easy to use too

```php	
// creating some tags
    $tag = new Tag();
    $tag -> libTag = 'tag 1';
    $tag -> save();

    $tag2 = new Tag();
    $tag2 -> libTag = 'tag 2';
    $tag2 -> save();

    $tag3 = new Tag();
    $tag3 -> libTag = 'tag 3';
    $tag3 -> save();

// setting car's tags
    $car -> setTag(array($tag,$tag2,$tag3));

// getting car's tags (return instance of \PicORM\Collection)
    $car -> getTag();

// getting car's tags with custom criteria
// parameters are same as find() method
// method return instance of \PicORM\Collection
    $car -> getTag($where,$order,$limitStart,$limitStop);

// unset relation between $car and $tag2
    $car -> unsetTag($tag2);
```


Changelog
---------
#### UNSTABLE 0.0.1
- Initial release

#### UNSTABLE 0.0.2
- Bug fixes
- Add namespace support and pdo fetch mode selection
- Add transaction support

#### BETA 0.0.3
- Bug fixes
- Refactoring MySQL
- Collections
- Tests

#### BETA 0.0.4
- Refactoring Entity to Model
- QueryBuilder : SQL hint and FoundRows
- Collection pagination
- Tests

#### BETA 0.0.5
- Customize collection query builder
- Moving from SQL_CALC_FOUND_ROWS to count(*) 
- Fix bug with relation alias name and caps
- Fix bug in relation getter with LIMIT clause
- Fix bug with no where / order / limit in many to many getter
- Refactoring Code
- Refactoring PHPDoc
- Refactoring Tests
- Adding TravisCI 

#### BETA 0.0.6
- Adding setter for database and table
- Bugfix:update query doesnt use tablename getter
- Bugfix : where value was not prefixed with table name in many to many getter
- Update readme

