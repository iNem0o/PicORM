<?php
require('../src/autoload.inc.php');

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

// we look for our car
    $car = Car :: findOne(array('nameCar' => '205 GTi'));
    // get brand of the car
    $car -> getBrand();

    // you can access brand name directy because it has been added to relation autoget fields
    $car -> nameBrand;


} catch (PicORM\Exception $e) {
    // !!!! disable showing PicORM\Exception for production because it contains SQL query !!!!
    echo '<strong>'.$e->getMessage().'</strong>';
}
catch (Exception $e) {
    echo '<strong>'.$e->getMessage().'</strong>';
}