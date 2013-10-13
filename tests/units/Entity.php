<?php
namespace PicORM\tests\units;
use \atoum;

class Entity extends atoum {

    public function createAndSaveRawEntity() {
        self::cleanTables();
        include_once __DIR__ . '/../scripts/raw_entity.php';
        $testBrand = new \Brand();
        $testBrand -> nameBrand = 'Acme';
        $testBrand -> noteBrand = 10;
        $testBrand -> save();
        return array($testBrand);
    }
    public static function cleanTables() {
        return;
        \PicORM\Entity::getDataSource()->query('TRUNCATE brands');
        \PicORM\Entity::getDataSource()->query('TRUNCATE cars');
        \PicORM\Entity::getDataSource()->query('TRUNCATE car_have_tag');
        \PicORM\Entity::getDataSource()->query('TRUNCATE tags');
    }
    public static function createAndSaveRawEntityWithOneToOneRelation() {
        self::cleanTables();
        include_once __DIR__ . '/../scripts/raw_entity.php';

        $testBrand = new \Brand();
        $testBrand -> nameBrand = 'Acme';
        $testBrand -> noteBrand = 10;
        $testBrand -> save();

        $car = new \Car();
        $car -> nameCar = 'AcmeCarcreateAndSaveRawEntityWithOneToOneRelation';
        $car -> noteCar = '10';
        $car -> setBrand($testBrand);
        $car -> save();

        return array(
            array($testBrand,$car)
        );
    }
    public static function createAndSaveRawEntityWithManyToManyRelation() {
        self::cleanTables();
        include_once __DIR__ . '/../scripts/raw_entity.php';

        $car = new \Car();
        $car -> nameCar = 'AcmeCar';
        $car -> noteCar = '10';
        $car -> idBrand = 1;
        $car -> save();

        $tags = array();

        $tag1 = new \Tag();
        $tag1 -> libTag = 'Sport';
        $tag1 -> save();
        $tag2 = new \Tag();
        $tag2 -> libTag = 'Family';
        $tag2 -> save();
        $tag3 = new \Tag();
        $tag3 -> libTag = 'Crossover';
        $tag3 -> save();
        $tags[] = $tag1;
        $tags[] = $tag2;
        $tags[] = $tag3;

        $car -> setTag($tags);
        $car -> save();
        return array(
            array($car,array($tags))
        );
    }

    /**
     * @dataProvider createAndSaveRawEntityWithManyToManyRelation
     */
    public function testManyToManyRelationCreation($car,$tags) {
        $req = \PicORM\Entity::getDataSource()->prepare('SELECT count(*) as nb FROM car_have_tag WHERE idCar = ?');
        $req -> execute(array($car -> idCar));
        $res = $req->fetch(\PDO::FETCH_ASSOC);
        $this -> variable($res['nb'])->isEqualTo("3");
    }

    public function testOneToManyRelationCreation() {
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

        $testBrand -> setCar(array($car,$car2,$car3));

        $req = \PicORM\Entity::getDataSource()->prepare('SELECT count(*) as nb FROM cars WHERE idBrand = ?');
        $req -> execute(array($testBrand -> idBrand));
        $res = $req->fetch(\PDO::FETCH_ASSOC);
        $this -> variable($res['nb'])->isEqualTo('3');
    }

    /**
     * @dataProvider createAndSaveRawEntityWithOneToOneRelation
     */
    public function testOneToOneRelationCreation($testBrand,$car) {
        $req = \PicORM\Entity::getDataSource()->prepare('SELECT count(*) as nb FROM cars WHERE idBrand = ? AND idCar = ?');
        $req -> execute(array($testBrand -> idBrand, $car -> idCar));
        $res = $req->fetch(\PDO::FETCH_ASSOC);
        $this -> variable($res['nb'])->isEqualTo('1');
    }

    /**
     * @dataProvider createAndSaveRawEntity
     */
    public function testDeleteEntity($testBrand) {
        $idBrand = $testBrand -> idBrand;
        $testBrand -> delete();

        $req = \PicORM\Entity::getDataSource()->prepare('SELECT count(*) as nb FROM brands WHERE idBrand = ?');
        $req -> execute(array($idBrand));
        $res = $req->fetch(\PDO::FETCH_ASSOC);

        $this -> variable($res['nb'])->isEqualTo("0");
    }

    /**
     * @dataProvider createAndSaveRawEntity
     */
    public function testUpdateEntity($testBrand) {

        $idBrand = $testBrand -> idBrand;
        $testBrand -> nameBrand = 'NEWNAME!';
        $testBrand -> noteBrand = '5';
        $testBrand -> save();

        $req = \PicORM\Entity::getDataSource()->prepare('SELECT * FROM brands WHERE idBrand = ?');
        $req -> execute(array($idBrand));
        $res = $req->fetch(\PDO::FETCH_ASSOC);

        $this -> variable($res['nameBrand'])->isEqualTo('NEWNAME!');
        $this -> variable($res['noteBrand'])->isEqualTo('5');
    }

    /**
     * @dataProvider createAndSaveRawEntity
     */
    public function testCreateEntity($testBrand) {

        $req = \PicORM\Entity::getDataSource()->prepare('SELECT * FROM brands WHERE idBrand = ?');
        $req -> execute(array($testBrand -> idBrand));
        $res = $req->fetch(\PDO::FETCH_ASSOC);

        $this -> variable($res['nameBrand'])->isEqualTo('Acme');
        $this -> variable($res['noteBrand'])->isEqualTo(10);
    }

}