<?php
namespace PicORM\tests\units;
use \atoum;

class Collection extends atoum {

    public static function cleanTables() {
        \PicORM\Model::getDataSource()->query('TRUNCATE brands');
        \PicORM\Model::getDataSource()->query('TRUNCATE cars');
        \PicORM\Model::getDataSource()->query('TRUNCATE car_have_tag');
        \PicORM\Model::getDataSource()->query('TRUNCATE tags');
    }

    public static function createAndSaveRawModelWithOneToManyRelation() {
        self::cleanTables();
        include_once __DIR__ . '/../scripts/raw_models.php';

        $testBrand = new \Brand();
        $testBrand -> nameBrand = 'AcmeMult';
        $testBrand -> noteBrand = 10;
        $testBrand -> save();

        $car = new \Car();
        $car -> nameCar = 'AcmeCar1';
        $car -> noteCar = '10';

        $car2 = new \Car();
        $car2 -> nameCar = 'AcmeCar2';
        $car2 -> noteCar = '12';

        $car3 = new \Car();
        $car3 -> nameCar = 'AcmeCar3';
        $car3 -> noteCar = '15';

        $cars = array($car,$car2,$car3);

        $testBrand -> setCar($cars);

        return array(
            array($testBrand,$cars)
        );
    }

    /**
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testDeleteCollection($testBrand,$cars) {
        include_once __DIR__ . '/../scripts/raw_models.php';

        $this -> integer(count($testBrand->getCar()))->isEqualTo(3);

        $testBrand->getCar()->delete();

        $this -> integer(count($testBrand->getCar()))->isEqualTo(0);
    }

    /**
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testUpdateCollection($testBrand,$cars) {
        include_once __DIR__ . '/../scripts/raw_models.php';

        $testBrand->getCar()->update(array('nameCar' => 'test'));

        $req = \PicORM\Model::getDataSource()->prepare('
            SELECT count(*) as nb FROM cars WHERE nameCar = ?
        ');
        $req -> execute(array('test'));
        $res = $req->fetch(\PDO::FETCH_ASSOC);

        $this -> string($res['nb'])->isEqualTo('3');

        $testBrand->getCar()->update(array('nameCar' => 'test'));

        $req = \PicORM\Model::getDataSource()->prepare('
            SELECT count(*) as nb FROM cars WHERE nameCar = ?
        ');
        $req -> execute(array('test'));
        $res = $req->fetch(\PDO::FETCH_ASSOC);

        $this -> string($res['nb'])->isEqualTo('3');
    }


}