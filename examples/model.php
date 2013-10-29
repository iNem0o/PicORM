<?php
require('../src/autoload.inc.php');

try {
    \PicORM\PicORM::configure(array(
        'datasource' => new PDO('mysql:dbname=DBNAME;host=HOST', 'DBLOGIN', 'DBPASSWD')
    ));

/*

    CREATE TABLE IF NOT EXISTS `brands` (
      `idBrand` int(11) NOT NULL AUTO_INCREMENT,
      `nameBrand` varchar(100) NOT NULL,
      `noteBrand` float DEFAULT 0,
      PRIMARY KEY (`idBrand`)
    ) ENGINE=MyISAM ;

*/

    class Brand extends \PicORM\Model
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


    // create new brand
    $brand = new Brand();
    $brand -> nameBrand = "Citroen";
    $brand -> noteBrand = 10;
    $brand -> save();

    $brand2 = new Brand();
    $brand2 -> nameBrand = "Renault";
    $brand2 -> noteBrand = 10;
    $brand2 -> save();

    $brand3 = new Brand();
    $brand3 -> nameBrand = "Peugeot";
    $brand3 -> noteBrand = 15;
    $brand3 -> save();

    // update existing brand
    $brand -> nameBrand = "Audi";
    $brand -> save();

    // delete brand
    $brand -> delete();

    // select all brand
    $result = Brand::find();

    // select one brand
    $result = Brand::findOne(array('idBrand' => 1));

    // select one brand with order by
    $result = Brand::findOne(array('noteBrand' => 10),array('nameBrand' => 'ASC'));


    // Criteria with exact value (noteBrand=10)
    $collection = Brand::find(array('noteBrand' => 10));

    // Criteria with custom operator (noteBrand >= 10)
    $collection = Brand::find(array('noteBrand' => array('operator' => '>=','value' => 10)));

    // Criteria with Raw SQL
    $collection = Brand::find(array('noteBrand' => array('IN(9,10,11)')));

    // Criteria with custom operator (noteBrand >= 10)
    // order by field
    $collection = Brand::find(array('noteBrand' => array('operator' => '>=','value' => 10)),array('noteBrand' => 'ASC'));

    // Criteria with custom operator (noteBrand >= 10)
    // random order
    $collection = Brand::find(array('noteBrand' => array('operator' => '>=','value' => 10)),array('RAND()' => null));

    // Criteria with custom operator (noteBrand >= 10)
    // order by field (noteBrand ASC)
    // return subset of first 10 elements (LIMIT 10)
    $collection = Brand::find(array('noteBrand' => array('operator' => '>=','value' => 10)),array('noteBrand' => 'ASC'),10);

    // Criteria with custom operator (noteBrand >= 10)
    // order by field (noteBrand ASC)
    // return subset of 10 elements starting from 20th position (LIMIT 10,20)
    $collection = Brand::find(array('noteBrand' => array('operator' => '>=','value' => 10)),array('noteBrand' => 'ASC'),10,20);

    // raw mysql query
    // return a model array
    $result = Brand::findQuery("SELECT * FROM brands WHERE noteBrand = ?",array(10));

} catch (PicORM\Exception $e) {
    // !!!! disable showing PicORM\Exception for production because it contains SQL query !!!!
    echo '<strong>'.$e->getMessage().'</strong>';
}
catch (Exception $e) {
    echo '<strong>'.$e->getMessage().'</strong>';
}