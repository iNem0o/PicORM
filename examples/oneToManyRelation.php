<?php
require('../PicORM/autoload.inc.php');

use PicORM\Entity;

try {
    Entity::configure(array(
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
    */

    /**
     * Class Brand
     *
     */
    class Brand extends Entity
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

    class Car extends Entity
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
            // when entity is loaded
            self::addRelationOneToOne('idBrand', 'Brand', 'idBrand', 'nameBrand');
        }
    }

// creating a brand
    $brand = new Brand();
    $brand->nameBrand = "Peugeot";
    $brand->save();

// creating some cars
// and affecting $brand to them
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

// get all cars from brand
    var_dump($brand -> getCar());

// get all cars from brand with custom parameters
// parameters are same as find() method
    $brand -> getCar($where,$order,$limitStart,$limitStop);


// tips : setter will work both way for exemple
// creating a new brand
    $brand2 = new Brand();
    $brand2 -> nameBrand = "Citroen";
    $brand2 -> save();

// this will move $car,$car2,$car3 from brand Peugeot to brand Citroen
    $brand2 -> setCar(array($car,$car2,$car3));

} catch (PicORM\Exception $e) {
    // !!!! disable showing PicORM\Exception for production because it contains SQL query !!!!
    echo '<strong>'.$e->getMessage().'</strong>';
}
catch (Exception $e) {
    echo '<strong>'.$e->getMessage().'</strong>';
}