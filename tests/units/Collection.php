<?php
namespace PicORM\tests\units;
use \atoum;
use PicORM\Model;

class Collection extends atoum {

    public function afterTestMethod($testMethod) {
        Model::getDataSource()->query('TRUNCATE brands');
        Model::getDataSource()->query('TRUNCATE cars');
        Model::getDataSource()->query('TRUNCATE car_have_tag');
        Model::getDataSource()->query('TRUNCATE tags');
    }

    public static function createAndSaveRawModelWithOneToManyRelation() {
        include_once __DIR__ . '/../scripts/tested_models.php';

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
        include_once __DIR__ . '/../scripts/tested_models.php';

        $this -> integer(count($testBrand->getCar()))->isEqualTo(3);

        $testBrand->getCar()->delete();

        $this -> integer(count($testBrand->getCar()))->isEqualTo(0);
    }

    /**
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testUpdateCollection($testBrand,$cars) {
        include_once __DIR__ . '/../scripts/tested_models.php';

        $testBrand->getCar()->update(array('nameCar' => 'test'));

        $req = Model::getDataSource()->prepare('
            SELECT count(*) as nb FROM cars WHERE nameCar = ?
        ');
        $req -> execute(array('test'));
        $res = $req->fetch(\PDO::FETCH_ASSOC);

        $this -> string($res['nb'])->isEqualTo('3');

        $testBrand->getCar()->update(array('nameCar' => 'test'));

        $req = Model::getDataSource()->prepare('
            SELECT count(*) as nb FROM cars WHERE nameCar = ?
        ');
        $req -> execute(array('test'));
        $res = $req->fetch(\PDO::FETCH_ASSOC);

        $this -> string($res['nb'])->isEqualTo('3');
    }

    /**
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testPagination($testBrand,$cars) {
        self::createAndSaveRawModelWithOneToManyRelation();
        self::createAndSaveRawModelWithOneToManyRelation();
        self::createAndSaveRawModelWithOneToManyRelation();
        self::createAndSaveRawModelWithOneToManyRelation();
        self::createAndSaveRawModelWithOneToManyRelation();
        include_once __DIR__ . '/../scripts/tested_models.php';

        $collection = \Car::find();
        $collection->activePagination(5);
        $collection->paginate(1);

        $this -> integer($collection->getTotalPages())->isEqualTo(4);
        $this -> integer(count($collection))->isEqualTo(5);
    }


}