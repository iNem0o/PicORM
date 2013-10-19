<?php
namespace PicORM\tests\units;
use \atoum;

class EntityCollection extends atoum {

    public static function cleanTables() {
        \PicORM\Entity::getDataSource()->query('TRUNCATE brands');
        \PicORM\Entity::getDataSource()->query('TRUNCATE cars');
        \PicORM\Entity::getDataSource()->query('TRUNCATE car_have_tag');
        \PicORM\Entity::getDataSource()->query('TRUNCATE tags');
    }

    public static function createAndSaveRawEntityWithOneToManyRelation() {
        self::cleanTables();
        include_once __DIR__ . '/../scripts/raw_entity.php';

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
     * @dataProvider createAndSaveRawEntityWithOneToManyRelation
     */
    public function testDeleteCollection($testBrand,$cars) {
        include_once __DIR__ . '/../scripts/raw_entity.php';

        $this -> variable(count($testBrand->getCar()))->isEqualTo('3');

        $testBrand->getCar()->delete();

        $this -> variable(count($testBrand->getCar()))->isEqualTo('0');
    }

    /**
     * @dataProvider createAndSaveRawEntityWithOneToManyRelation
     */
    public function testUpdateCollection($testBrand,$cars) {
        include_once __DIR__ . '/../scripts/raw_entity.php';

        $testBrand->getCar()->update(array('nameCar' => 'test'));

        $req = \PicORM\Entity::getDataSource()->prepare('
            SELECT count(*) as nb FROM cars WHERE nameCar = ?
        ');
        $req -> execute(array('test'));
        $res = $req->fetch(\PDO::FETCH_ASSOC);

        $this -> variable($res['nb'])->isEqualTo('3');

        $testBrand->getCar()->update(array('nameCar' => 'test'));

        $req = \PicORM\Entity::getDataSource()->prepare('
            SELECT count(*) as nb FROM cars WHERE nameCar = ?
        ');
        $req -> execute(array('test'));
        $res = $req->fetch(\PDO::FETCH_ASSOC);

        $this -> variable($res['nb'])->isEqualTo('3');
    }


}