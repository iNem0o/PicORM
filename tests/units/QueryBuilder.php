<?php
namespace PicORM\tests\units;

use \atoum;

class QueryBuilder extends atoum
{
    /**
     * @dataProvider createQueryBuilder
     */
    public function testInsertQueryWithOneField(\PicORM\QueryBuilder $insertQueryBuilder)
    {
        $insertQueryBuilder
            ->insertInto("tableName")
            ->values("tableField", "1");
        $resultQuery = "INSERT INTO tableName (tableField) VALUES (1)";
        $this->string($insertQueryBuilder->buildQuery())->isEqualTo($resultQuery);
    }

    /**
     * @dataProvider createQueryBuilder
     */
    public function testInsertQueryWithTwoFields(\PicORM\QueryBuilder $insertQueryBuilder)
    {
        $insertQueryBuilder
            ->insertInto("tableName")
            ->values("tableField", "1")
            ->values("tableField2", "'foobar'");
        $resultQuery = "INSERT INTO tableName (tableField,tableField2) VALUES (1,'foobar')";
        $this->string($insertQueryBuilder->buildQuery())->isEqualTo($resultQuery);
    }

    /**
     * @dataProvider createQueryBuilder
     */
    public function testInsertQueryMultiplesvalues(\PicORM\QueryBuilder $insertQueryBuilder)
    {
        $insertQueryBuilder
            ->insertInto("tableName")
            ->values("tableField", "1")
            ->values("tableField2", "'foobar'")
            ->newValues("tableField2", "'aze'")
            ->values("tableField", "1");
        $resultQuery = "INSERT INTO tableName (tableField,tableField2) VALUES (1,'foobar'),(1,'aze')";
        $this->string($insertQueryBuilder->buildQuery())->isEqualTo($resultQuery);
    }

    /**
     * @dataProvider createQueryBuilder
     */
    public function testBuildUpdateQuery(\PicORM\QueryBuilder $updateQueryBuilder)
    {
        $updateQueryBuilder
            ->update('tableName t')
            ->set('tableField', '3')
            ->set('tableField2', '5');
        $setParams   = "SET tableField = 3,tableField2 = 5";
        $resultQuery = 'UPDATE tableName t  ';
        $this->string($updateQueryBuilder->buildQuery())->isEqualTo($resultQuery . $setParams);

        $updateQueryBuilder->innerJoin('tableName2', 'tableName.field = tableName2.field');
        $resultQuery = 'UPDATE tableName t INNER JOIN tableName2 ON tableName.field = tableName2.field';
        $this->string($updateQueryBuilder->buildQuery())->isEqualTo($resultQuery . ' ' . $setParams);

        $updateQueryBuilder->leftJoin('tableName3', 'tableName2.field = tableName3.field');
        $resultQuery .= ' LEFT JOIN tableName3 ON tableName2.field = tableName3.field';
        $this->string($updateQueryBuilder->buildQuery())->isEqualTo($resultQuery . ' ' . $setParams);

        $resultQuery .= ' ' . $setParams;
        $updateQueryBuilder->where('tableName.field', '=', '2');
        $resultQuery .= ' WHERE tableName.field = 2';
        $this->string($updateQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $updateQueryBuilder->andWhere('tableName2.field', '!=', '3');
        $resultQuery .= ' AND tableName2.field != 3';
        $this->string($updateQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $updateQueryBuilder->orWhere('tableName3.field', '<', '2');
        $resultQuery .= ' OR tableName3.field < 2';
        $this->string($updateQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $updateQueryBuilder->orderBy('tableName.field', 'ASC');
        $resultQuery .= ' ORDER BY tableName.field ASC';
        $this->string($updateQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $updateQueryBuilder->orderBy('tableName3.field', 'DESC');
        $resultQuery .= ',tableName3.field DESC';
        $this->string($updateQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $updateQueryBuilder->limit(10);
        $this->string($updateQueryBuilder->buildQuery())->isEqualTo($resultQuery . ' LIMIT 10');

        $updateQueryBuilder->limit(10, 5);
        $resultQuery .= ' LIMIT 10, 5';
        $this->string($updateQueryBuilder->buildQuery())->isEqualTo($resultQuery);
    }

    /**
     * @dataProvider createQueryBuilder
     */
    public function testBuildDeleteQuery(\PicORM\QueryBuilder $deleteQueryBuilder)
    {
        $deleteQueryBuilder->delete('tableName');
        $resultQuery = 'DELETE tableName FROM tableName';
        $this->string($deleteQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $deleteQueryBuilder->innerJoin('tableName2', 'tableName.field = tableName2.field');
        $resultQuery .= ' INNER JOIN tableName2 ON tableName.field = tableName2.field';
        $this->string($deleteQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $deleteQueryBuilder->innerJoin('tableName2', 'tableName.field = tableName2.field');
        $resultQuery .= ' INNER JOIN tableName2 ON tableName.field = tableName2.field';
        $this->string($deleteQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $deleteQueryBuilder->leftJoin('tableName3', 'tableName2.field = tableName3.field');
        $resultQuery .= ' LEFT JOIN tableName3 ON tableName2.field = tableName3.field';
        $this->string($deleteQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $deleteQueryBuilder->where('tableName.field', '=', '2');
        $resultQuery .= ' WHERE tableName.field = 2';
        $this->string($deleteQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $deleteQueryBuilder->andWhere('tableName2.field', '!=', '3');
        $resultQuery .= ' AND tableName2.field != 3';
        $this->string($deleteQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $deleteQueryBuilder->orWhere('tableName3.field', '<', '2');
        $resultQuery .= ' OR tableName3.field < 2';
        $this->string($deleteQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $deleteQueryBuilder->orderBy('tableName.field', 'ASC');
        $resultQuery .= ' ORDER BY tableName.field ASC';
        $this->string($deleteQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $deleteQueryBuilder->orderBy('tableName3.field', 'DESC');
        $resultQuery .= ',tableName3.field DESC';
        $this->string($deleteQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $deleteQueryBuilder->limit(10);
        $this->string($deleteQueryBuilder->buildQuery())->isEqualTo($resultQuery . ' LIMIT 10');

        $deleteQueryBuilder->limit(10, 5);
        $resultQuery .= ' LIMIT 10, 5';
        $this->string($deleteQueryBuilder->buildQuery())->isEqualTo($resultQuery);
    }

    /**
     * @dataProvider createQueryBuilder
     */
    public function testQueryHint(\PicORM\QueryBuilder $selectQueryBuilder) {
        $selectQueryBuilder
            ->queryHint("SQL_NO_CACHE")
            ->select("*")
            ->from("table");
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo("SELECT  SQL_NO_CACHE  * FROM table");
    }

    /**
     * @dataProvider createQueryBuilder
     */
    public function testResetSelect(\PicORM\QueryBuilder $selectQueryBuilder) {
        $selectQueryBuilder->select("field")
            ->from("table")
            ->resetSelect();
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo("SELECT   FROM table");

        $selectQueryBuilder->resetSelect("field");
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo("SELECT  field FROM table");
    }

    /**
     * @dataProvider createQueryBuilder
     */
    public function testResetOrderBy(\PicORM\QueryBuilder $selectQueryBuilder) {
        $selectQueryBuilder->select("field")
            ->from("table")
            ->orderBy("field")
            ->resetOrderBy();
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo("SELECT  field FROM table");

        $selectQueryBuilder->resetOrderBy('field','ASC');
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo("SELECT  field FROM table      ORDER BY field ASC");
    }


    /**
     * @dataProvider createQueryBuilder
     */
    public function testResetLimit(\PicORM\QueryBuilder $selectQueryBuilder) {
        $selectQueryBuilder->select("field")
            ->from("table")
            ->limit("10")
            -> resetLimit();

        $this->string($selectQueryBuilder->buildQuery())->isEqualTo("SELECT  field FROM table");

        $selectQueryBuilder-> resetLimit(15,10);

        $this->string($selectQueryBuilder->buildQuery())->isEqualTo("SELECT  field FROM table       LIMIT 15, 10");

    }

    /**
     * @dataProvider createQueryBuilder
     */
    public function testBuildSelectQuery(\PicORM\QueryBuilder $selectQueryBuilder)
    {

        $selectQueryBuilder->select('*')->from('tableName');
        $resultQuery = 'SELECT  * FROM tableName';
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo($resultQuery);
        $selectQueryBuilder->innerJoin('tableName2', 'tableName.field = tableName2.field');
        $resultQuery .= '  INNER JOIN tableName2 ON tableName.field = tableName2.field';
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $selectQueryBuilder->leftJoin('tableName3', 'tableName2.field = tableName3.field');
        $resultQuery .= ' LEFT JOIN tableName3 ON tableName2.field = tableName3.field';
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $selectQueryBuilder->where('tableName.field', '=', '2');
        $resultQuery .= ' WHERE tableName.field = 2';
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $selectQueryBuilder->andWhere('tableName2.field', '!=', '3');
        $resultQuery .= ' AND tableName2.field != 3';
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $selectQueryBuilder->orWhere('tableName3.field', '<', '2');
        $resultQuery .= ' OR tableName3.field < 2';
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $selectQueryBuilder->groupBy('tableName3.field');
        $resultQuery .= ' GROUP BY tableName3.field';
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $selectQueryBuilder->groupBy('tableName2.field');
        $resultQuery .= ',tableName2.field';
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $selectQueryBuilder->having('tableName2.field > 2');
        $resultQuery .= ' HAVING tableName2.field > 2';
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $selectQueryBuilder->orderBy('tableName.field', 'ASC');
        $resultQuery .= ' ORDER BY tableName.field ASC';
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $selectQueryBuilder->orderBy('tableName3.field', 'DESC');
        $resultQuery .= ',tableName3.field DESC';
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $selectQueryBuilder->limit(10);
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo($resultQuery . ' LIMIT 10');

        $selectQueryBuilder->limit(10, 5);
        $resultQuery .= ' LIMIT 10, 5';
        $this->string($selectQueryBuilder->buildQuery())->isEqualTo($resultQuery);

        $this -> object($selectQueryBuilder) -> isIdenticalTo($selectQueryBuilder -> limit());

    }

    public static function createQueryBuilder()
    {
        return array(
            array(new \PicORM\QueryBuilder())
        );
    }
}