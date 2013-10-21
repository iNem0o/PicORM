# PicORM a lightweight ORM.
---
PicORM will help you to map your database rows into PHP object and create relations between them. 


## Install
---
### From composer

Open terminal, and change directory to your project root folder
Run this command to get the latest Composer version

``curl -sS https://getcomposer.org/installer | php``

Create a composer.json file with

```json
{
    "require": {
        "picorm/picorm": "0.0.3"
    }
}
```
Install PicORM with

``php composer.phar install``


### From source
Clone ``https://github.com/iNem0o/PicORM`` repository and include ``PicORM`` autoload with
```php require('path/to/PicORM/src/autoload.inc.php'); ```

## Load and configure PicORM
Before using ``PicORM`` you have to configure it. 
``datasource`` is the only required parameter and it have to be a PDO instance

```php
\PicORM\PicORM::configure(array(
	'datasource' => new PDO('mysql:dbname=DBNAME;host=HOST', 'DBLOGIN', 'DBPASSWD')
));
```


## Entity
---
### Implements an Entity

First you have to create a table, which your entity will be mapped to

```sql
    CREATE TABLE `brands` (
      `idBrand` int(11) NOT NULL AUTO_INCREMENT,
      `nameBrand` varchar(100) NOT NULL,
      `noteBrand` float DEFAULT 0,
      PRIMARY KEY (`idBrand`)
    ) ENGINE=MyISAM;
```

Next create a class which extends ``\PicORM\Entity``
You have to implements some static parameters to describe your MySQL table schema, if you forgot one of them, a ``\PicORM\Exception`` will remind you

  ``protected static $_tableName`` MySQL table name
  ``protected static $_primaryKey`` table primary key field name
  ``protected static $_tableFields`` array with all mysql table fields name without primary key

and then, add one public property by table field with ``public $fieldName``
  
  **Complete Brand entity code**
  
```php
    class Brand extends \PicORM\Entity
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

### Create and save an Entity

```php
// creating new entity
	$brand = new Brand();

// setting field
	$brand -> nameBrand = 'Peugeot';

// save entity
	$brand -> save();
```

### Update and delete an Entity

```php
// Criteria with exact value (idBrand=10)
    $brand = Brand :: findOne(array('idBrand' => 10));

// setting entity property
	$brand -> nameBrand = 'Audi';

// save entity
	$brand -> save();
	
// save entity
	$brand -> delete();
```

## Using find() or findOne() $where and $order parameters
---
**$where** parameter is data for building a WHERE mysql clause

```php
// simple criteria
	$where = array('field' => 1);
	
// custom operator
	$where = array('field' => array('operator' => '<=',
									'value'    => '20')
					);

// raw sql value (NOT prepared, beware of SQL injection)
	$where = array('field' => array(
									'field'    => array('IN (5,6,4)'),
									'dateTime' => array('NOW()')
									)
					);
```
	 
**$order** parameter is data for building an ORDER mysql clause

```php
     $order = array(
		'field'=>'ASC',
		'field2' => DESC,
		'RAND() => ''
	 )
```

## Collections
Collections in PicORM are created by ``find()`` method.
When you have a fresh EntityCollection instance, data is not fetched yet. Fetching is done only when you try to access data with one of these methods

```php
// php array access
	$firstEntity = $collection[0];
	
// counting collection
	$nbEntities = count($collection);
	
// using getter
	$firstEntity = $collection->get(0);
	
// iterate over the collection
	foreach($collection as $entity)
	
// or manual fetching / re-fetching
	$collection->fetchCollection();
```

An EntityCollection instance can interact with it entities and group SELECT and DELETE queries

```php
// Delete (execute only one mysql query)
    $collection = Brand::find(array('noteBrand' => 10))
                        ->delete();

// Update  (execute only one mysql query)
    $collection = Brand::find(array('noteBrand' => array('IN(9,10,11)')))
                         ->update(array('noteBrand' => 5));
						 
```








































Code sample
-------------------


### Select multiple entity
```php
// Criteria with exact value (noteBrand=10)
    $collection = Brand::find(array('noteBrand' => 10));

// Criteria with custom operator (noteBrand >= 10)
    $collection = Brand::find(array('noteBrand' => array('operator' => '>=','value' => 10)));

// Criteria with Raw SQL
    $collection = Brand::find(array('noteBrand' => array('IN(9,10,11)')));



// raw mysql query finding
    $result = Brand::findFromQuery("SELECT * FROM brands WHERE noteBrand = ?",array(10));
```

## Relations (php in 'examples/' for entity declaration)

### 1-1 relation
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

// getting car's brand
    $car -> getBrand($brand);

    $car -> save();
```

### 1-N relation
```php
// get all cars from brand
    $brand -> getCar()
```

### N-N relation
```php
// creating a car
// and affecting $brand to them
    $car = new Car();
    $car->nameCar = "205 GTi";
    $car->setBrand($brand);
    $car->save();

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

// getting car's tags
	$car -> getTag();

// unset relation between $car and $tag2
    $car -> unsetTag($tag2);

// creating some cars
	$car = new Car();
	$car->nameCar = "205 GTi";
	$car->setBrand($brand);
	$car->save();

	$car2 = new Car();
	$car2->nameCar = "206";
	$car2->setBrand($brand);
	$car2->save();

	$car3 = new Car();
	$car3->nameCar = "207";
	$car3->setBrand($brand);
	$car3->save();


// affecting new cars to $tag2
	$tag2 -> setCar(array($car,$car2,$car3));

```

Changelog
---------
#### 0.0.1
- initial release

#### 0.0.2
- bugfixes
- add namespace support and pdo fetch mode selection
- add transaction support