PicORM
======
PicORM is a lightweight PHP ORM.

--------
PicORM is currently in Alpha stage, that's means that PicORM is in active development and should NOT be considered stable.
--------

Features
--------
* Create Read Update Delete
* Relation between entities
* Database abstraction layer
* Hydrate collection from raw sql query
* Use PDO and prepared statements

Roadmap 0.0.3
--------
* MySQL refactoring
* QueryBuilder
* EntityCollection with lazyloading and update / delete method


Install from composer
-------------------
```json
{
    "require": {
        "picorm/picorm": "0.0.2"
    }
}
```

Code sample
-------------------
### Load and configure PicORM
```php
require('src/autoload.inc.php');

use PicORM\Entity;
Entity::configure(array(
	'datasource' => new PDO('mysql:dbname=DBNAME;host=HOST', 'DBLOGIN', 'DBPASSWD')
));
```

### Declare entity
```php
    class Brand extends \PicORM\Entity
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

    }
```

### Manipulate entity
```php
	// creating new entity
	$brand = new Brand();

	// setting field
	$brand -> nameBrand = 'Peugeot';

	// save entity
	$brand -> save();

	// delete entity
	$brand -> delete();
```
### Selecting data
```php
// Criteria with exact value (noteBrand=10)
    $result = Brand::find(array('noteBrand' => 10));

// Criteria with custom operator (noteBrand >= 10)
    $result = Brand::find(array('noteBrand' => array('operator' => '>=','value' => 10)));

// Criteria with Raw SQL
    $result = Brand::find(array('noteBrand' => array('IN(9,10,11)')));

// raw mysql query
// return an entity collection
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