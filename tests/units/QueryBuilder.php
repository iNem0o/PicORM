<?php
namespace PicORM\tests\units;
use \atoum;

class QueryBuilder extends atoum {
    public function testInsertQuery() {
        $insert = new \PicORM\QueryBuilder();
        $insert -> insertInto("tableName") -> values("tableField","1");
        $resultQuery = "INSERT INTO tableName (tableField) VALUES (1)";
        $this -> variable($insert -> buildQuery()) -> isEqualTo($resultQuery);
        $insert -> values("tableField2","'foobar'");
        $resultQuery = "INSERT INTO tableName (tableField,tableField2) VALUES (1,'foobar')";
        $this -> variable($insert -> buildQuery()) -> isEqualTo($resultQuery);
    }

    public function testUpdateQuery() {
        $update = new \PicORM\QueryBuilder();
        $update -> update('tableName t') -> set('tableField','3') -> set('tableField2','5');
        $setParams = "SET tableField = 3,tableField2 = 5";
        $resultQuery = 'UPDATE tableName t  ';
        $this -> variable($update -> buildQuery()) -> isEqualTo($resultQuery.$setParams);

        $update -> innerJoin('tableName2','tableName.field = tableName2.field');
        $resultQuery = 'UPDATE tableName t INNER JOIN tableName2 ON tableName.field = tableName2.field';
        $this -> variable($update -> buildQuery()) -> isEqualTo($resultQuery.' '.$setParams);

        $update -> leftJoin('tableName3','tableName2.field = tableName3.field');
        $resultQuery .= ' LEFT JOIN tableName3 ON tableName2.field = tableName3.field';
        $this -> variable($update -> buildQuery()) -> isEqualTo($resultQuery.' '.$setParams);

        $resultQuery .= ' '.$setParams;
        $update -> where('tableName.field','=','2');
        $resultQuery .= ' WHERE tableName.field = 2';
        $this -> variable($update -> buildQuery()) -> isEqualTo($resultQuery);

        $update -> andWhere('tableName2.field','!=','3');
        $resultQuery .= ' AND tableName2.field != 3';
        $this -> variable($update -> buildQuery()) -> isEqualTo($resultQuery);

        $update -> orWhere('tableName3.field','<','2');
        $resultQuery .= ' OR tableName3.field < 2';
        $this -> variable($update -> buildQuery()) -> isEqualTo($resultQuery);

        $update -> orderBy('tableName.field','ASC');
        $resultQuery .= ' ORDER BY tableName.field ASC';
        $this -> variable($update -> buildQuery()) -> isEqualTo($resultQuery);

        $update -> orderBy('tableName3.field','DESC');
        $resultQuery .= ',tableName3.field DESC';
        $this -> variable($update -> buildQuery()) -> isEqualTo($resultQuery);

        $update -> limit(10);
        $this -> variable($update -> buildQuery()) -> isEqualTo($resultQuery.' LIMIT 10');

        $update -> limit(10,5);
        $resultQuery .= ' LIMIT 10, 5';
        $this -> variable($update -> buildQuery()) -> isEqualTo($resultQuery);
    }

    public function testDeleteQuery() {
        $delete = new \PicORM\QueryBuilder();
        $delete -> delete('tableName');
        $resultQuery = 'DELETE tableName FROM tableName';
        $this -> variable($delete -> buildQuery()) -> isEqualTo($resultQuery);

        $delete -> innerJoin('tableName2','tableName.field = tableName2.field');
        $resultQuery .= ' INNER JOIN tableName2 ON tableName.field = tableName2.field';
        $this -> variable($delete -> buildQuery()) -> isEqualTo($resultQuery);

        $delete -> innerJoin('tableName2','tableName.field = tableName2.field');
        $resultQuery .= ' INNER JOIN tableName2 ON tableName.field = tableName2.field';
        $this -> variable($delete -> buildQuery()) -> isEqualTo($resultQuery);

        $delete -> leftJoin('tableName3','tableName2.field = tableName3.field');
        $resultQuery .= ' LEFT JOIN tableName3 ON tableName2.field = tableName3.field';
        $this -> variable($delete -> buildQuery()) -> isEqualTo($resultQuery);

        $delete -> where('tableName.field','=','2');
        $resultQuery .= ' WHERE tableName.field = 2';
        $this -> variable($delete -> buildQuery()) -> isEqualTo($resultQuery);

        $delete -> andWhere('tableName2.field','!=','3');
        $resultQuery .= ' AND tableName2.field != 3';
        $this -> variable($delete -> buildQuery()) -> isEqualTo($resultQuery);

        $delete -> orWhere('tableName3.field','<','2');
        $resultQuery .= ' OR tableName3.field < 2';
        $this -> variable($delete -> buildQuery()) -> isEqualTo($resultQuery);

        $delete -> orderBy('tableName.field','ASC');
        $resultQuery .= ' ORDER BY tableName.field ASC';
        $this -> variable($delete -> buildQuery()) -> isEqualTo($resultQuery);

        $delete -> orderBy('tableName3.field','DESC');
        $resultQuery .= ',tableName3.field DESC';
        $this -> variable($delete -> buildQuery()) -> isEqualTo($resultQuery);

        $delete -> limit(10);
        $this -> variable($delete -> buildQuery()) -> isEqualTo($resultQuery.' LIMIT 10');

        $delete -> limit(10,5);
        $resultQuery .= ' LIMIT 10, 5';
        $this -> variable($delete -> buildQuery()) -> isEqualTo($resultQuery);
    }

    public function testSelectQuery()
    {
        $select = new \PicORM\QueryBuilder();

        $select -> select('*') -> from('tableName');
        $resultQuery = 'SELECT * FROM tableName';
        $this -> variable($select -> buildQuery()) -> isEqualTo($resultQuery);

        $select -> innerJoin('tableName2','tableName.field = tableName2.field');
        $resultQuery .= '  INNER JOIN tableName2 ON tableName.field = tableName2.field';
        $this -> variable($select -> buildQuery()) -> isEqualTo($resultQuery);

        $select -> leftJoin('tableName3','tableName2.field = tableName3.field');
        $resultQuery .= ' LEFT JOIN tableName3 ON tableName2.field = tableName3.field';
        $this -> variable($select -> buildQuery()) -> isEqualTo($resultQuery);

        $select -> where('tableName.field','=','2');
        $resultQuery .= ' WHERE tableName.field = 2';
        $this -> variable($select -> buildQuery()) -> isEqualTo($resultQuery);

        $select -> andWhere('tableName2.field','!=','3');
        $resultQuery .= ' AND tableName2.field != 3';
        $this -> variable($select -> buildQuery()) -> isEqualTo($resultQuery);

        $select -> orWhere('tableName3.field','<','2');
        $resultQuery .= ' OR tableName3.field < 2';
        $this -> variable($select -> buildQuery()) -> isEqualTo($resultQuery);

        $select -> groupBy('tableName3.field');
        $resultQuery .= ' GROUP BY tableName3.field';
        $this -> variable($select -> buildQuery()) -> isEqualTo($resultQuery);

        $select -> groupBy('tableName2.field');
        $resultQuery .= ',tableName2.field';
        $this -> variable($select -> buildQuery()) -> isEqualTo($resultQuery);

        $select -> having('tableName2.field > 2');
        $resultQuery .= ' HAVING tableName2.field > 2';
        $this -> variable($select -> buildQuery()) -> isEqualTo($resultQuery);

        $select -> orderBy('tableName.field','ASC');
        $resultQuery .= ' ORDER BY tableName.field ASC';
        $this -> variable($select -> buildQuery()) -> isEqualTo($resultQuery);

        $select -> orderBy('tableName3.field','DESC');
        $resultQuery .= ',tableName3.field DESC';
        $this -> variable($select -> buildQuery()) -> isEqualTo($resultQuery);

        $select -> limit(10);
        $this -> variable($select -> buildQuery()) -> isEqualTo($resultQuery.' LIMIT 10');

        $select -> limit(10,5);
        $resultQuery .= ' LIMIT 10, 5';
        $this -> variable($select -> buildQuery()) -> isEqualTo($resultQuery);
    }
}