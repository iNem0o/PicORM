<?php
require('../src/autoload.inc.php');

use PicORM\Model;

try {
    PicORM::configure(array(
        'datasource' => new PDO('mysql:dbname=DBNAME;host=HOST', 'DBLOGIN', 'DBPASSWD')
    ));

    /*
        CREATE TABLE IF NOT EXISTS `brands` (
          `idBrand` int(11) NOT NULL AUTO_INCREMENT,
          `nameBrand` varchar(100) NOT NULL,
          `noteBrand` float DEFAULT 0,
          PRIMARY KEY (`idBrand`)
        ) ENGINE=MyISAM;

        CREATE TABLE  `cars` (
        `idCar` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `idBrand` INT NOT NULL ,
        `nameCar` VARCHAR( 100 ) NOT NULL
        ) ENGINE = MYISAM ;

        CREATE TABLE IF NOT EXISTS `car_have_tag` (
        `idCar` INT NOT NULL ,
        `idTag` INT NOT NULL ,
        PRIMARY KEY (  `idCar` ,  `idTag` )
        ) ENGINE = MYISAM ;

        CREATE TABLE IF NOT EXISTS `tags` (
        `idTag` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `libTag` VARCHAR( 255 ) NOT NULL
        ) ENGINE = MYISAM ;
    */

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


// creating a brand
    $brand = new Brand();
    $brand->nameBrand = "Peugeot";
    $brand->save();

// creating a car
// and affecting $brand to them
    $car = new Car();
    $car->nameCar = "205 GTi";
    $car->setBrand($brand);
    $car->save();

// setting car's brand
    $car -> setBrand($brand);

// other way to setting car's brand
    $car -> idBrand = $brand -> idBrand;
    $car -> save();

// we look for our car
    $car = Car :: findOne(array('nameCar' => '205 GTi'));
// get brand of the car
    $car -> getBrand();

// you can access brand name directy because it has been added to relation autoget fields
    $car -> nameBrand;



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

// getting car's tags with custom parameters
// parameters are same as find() method
    $car -> getTag($where,$order,$limitStart,$limitStop);

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


} catch (PicORM\Exception $e) {
    // !!!! disable showing PicORM\Exception for production because it contains SQL query !!!!
    echo '<strong>'.$e->getMessage().'</strong>';
}
catch (Exception $e) {
    echo '<strong>'.$e->getMessage().'</strong>';
}