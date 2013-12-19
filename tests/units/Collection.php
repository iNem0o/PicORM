<?php
namespace PicORM\tests\units;

use \atoum;
use PicORM\InternalQueryHelper;
use PicORM\Model;

class Collection extends atoum
{
    public function beforeTestMethod($testMethod)
    {
        \PicORM\PicORM::getDataSource()->query(file_get_contents(__DIR__ . '/../scripts/before_tests.sql'));
    }

    public function afterTestMethod($testMethod)
    {
        \PicORM\PicORM::getDataSource()->query(file_get_contents(__DIR__ . '/../scripts/after_tests.sql'));
    }


    public static function createAndSaveRawModelWithOneToManyRelation()
    {


        $testBrand            = new \Brand();
        $testBrand->nameBrand = 'AcmeMult';
        $testBrand->noteBrand = 10;
        $testBrand->save();

        $car          = new \Car();
        $car->nameCar = 'AcmeCar1';
        $car->noteCar = '10';

        $car2          = new \Car();
        $car2->nameCar = 'AcmeCar2';
        $car2->noteCar = '12';

        $car3          = new \Car();
        $car3->nameCar = 'AcmeCar3';
        $car3->noteCar = '15';

        $cars = array($car, $car2, $car3);

        $testBrand->setCar($cars);

        return array(
            array($testBrand, $cars)
        );
    }

    /**
     * @engine isolate
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testGetQueryHelper($testBrand, $cars)
    {
        $collection = \Car::find();

        $property = new \ReflectionProperty('\PicORM\Collection', '_queryHelper');
        $property->setAccessible(true);

        $this
            ->object($property->getValue($collection))
            ->isCloneOf($collection->getQueryHelper());
    }

    /**
     * @engine isolate
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testSetQueryHelper($testBrand, $cars)
    {
        $query      = new InternalQueryHelper();
        $collection = \Car::find();
        $collection->setQueryHelper($query);
        $property = new \ReflectionProperty('\PicORM\Collection', '_queryHelper');
        $property->setAccessible(true);

        $this
            ->object($property->getValue($collection))
            ->isIdenticalTo($query);
    }

    /**
     * @engine isolate
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testGet($testBrand, $cars)
    {
        $carCollection = \Car::find();
        $this->string($cars[0]->idCar)->isIdenticalTo($carCollection->get(0)->idCar);
        $this->string($cars[1]->idCar)->isIdenticalTo($carCollection->get(1)->idCar);
        $this->string($cars[2]->idCar)->isIdenticalTo($carCollection->get(2)->idCar);
    }

    /**
     * @engine isolate
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testDeleteCollection($testBrand, $cars)
    {
//        include_once __DIR__ . '/../scripts/tested_models.php';

        $this->integer(count($testBrand->getCar()))->isEqualTo(3);

        $testBrand->getCar()->delete();

        $this->integer(count($testBrand->getCar()))->isEqualTo(0);
    }

    /**
     * @engine isolate
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testUpdateCollection($testBrand, $cars)
    {
//        include_once __DIR__ . '/../scripts/tested_models.php';

        $testBrand->getCar()->update(array('nameCar' => 'test'));

        $req = Model::getDataSource()->prepare('
            SELECT count(*) as nb FROM cars WHERE nameCar = ?
        ');
        $req->execute(array('test'));
        $res = $req->fetch(\PDO::FETCH_ASSOC);

        $this->string($res['nb'])->isEqualTo('3');

        $testBrand->getCar()->update(array('nameCar' => 'test'));

        $req = Model::getDataSource()->prepare('
            SELECT count(*) as nb FROM cars WHERE nameCar = ?
        ');
        $req->execute(array('test'));
        $res = $req->fetch(\PDO::FETCH_ASSOC);

        $this->string($res['nb'])->isEqualTo('3');
    }

    /**
     * @engine isolate
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testCountModelsWithoutLimit($testBrand, $cars)
    {
        $collection = \Car::find();
        $collection->activePagination(10);
        $collection->paginate(1);
        $this->integer($collection->countModelsWithoutLimit())->isEqualTo(3);

        // test with no pagination active

        $collection = \Car::find();
        $this->integer($collection->countModelsWithoutLimit())->isEqualTo(3);
    }


    /**
     * @engine isolate
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testPagination($testBrand, $cars)
    {
        self::createAndSaveRawModelWithOneToManyRelation();
        self::createAndSaveRawModelWithOneToManyRelation();
        self::createAndSaveRawModelWithOneToManyRelation();
        self::createAndSaveRawModelWithOneToManyRelation();
        self::createAndSaveRawModelWithOneToManyRelation();
//        include_once __DIR__ . '/../scripts/tested_models.php';

        $collection = \Car::find();
        $collection->activePagination(5);
        $collection->paginate(1);

        // test total page and count interface
        $this->integer($collection->getTotalPages())->isEqualTo(4);
        $this->integer(count($collection))->isEqualTo(5);

        // test ArrayAccess
        $this->boolean($collection->has(0))->isEqualTo(true);
        $this->boolean($collection->has(999999))->isEqualTo(false);
        $this->string($collection->get(0)->idCar)->isEqualTo($cars[0]->idCar);

        $collection->set(5, $cars[0]);
        $this->string($collection[5]->idCar)->isEqualTo($cars[0]->idCar);


        // tests if pagination is not active
        $collection = \Car::find();
        $this->integer($collection->getTotalPages())->isEqualTo(0);
        $this->object($collection->paginate(1))->isIdenticalTo($collection);

    }

    /**
     * @engine isolate
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testIterable($testBrand, $cars)
    {
        $collection = \Car::find();
        foreach ($collection as $k => $aCar) {
            $this->string($aCar->idCar)->isEqualTo($cars[$k]->idCar);
        }
    }

    /**
     * @engine isolate
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testOffsetExists($testBrand, $cars)
    {
        $collection = \Car::find();
        $this->boolean(isset($collection[1]))->isEqualTo(true);
    }

    /**
     * @engine isolate
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testOffsetSet($testBrand, $cars)
    {
        $collection    = \Car::find();
        $collection[1] = $cars[0];
        $this->object($collection[1])->isIdenticalTo($cars[0]);

        $collection   = \Car::find();
        $collection[] = $cars[0];
        $this->object($collection[count($collection) - 1])->isIdenticalTo($cars[0]);
    }

    /**
     * @engine isolate
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testOffsetUnset($testBrand, $cars)
    {
        $collection = \Car::find();
        unset($collection[1]);
        $this->boolean(isset($collection[1]))->isEqualTo(false);
    }

    /**
     * @engine isolate
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testOffsetGet($testBrand, $cars)
    {
        $collection = \Car::find();
        $this->string($collection[1]->idCar)->isIdenticalTo($cars[1]->idCar);
    }

    /**
     * @engine isolate
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testHas($testBrand, $cars)
    {
        $collection = \Car::find();
        $this->boolean($collection->has(1))->isEqualTo(true);
        $this->boolean($collection->has(10))->isEqualTo(false);
    }

}