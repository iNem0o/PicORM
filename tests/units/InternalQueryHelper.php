<?php
namespace PicORM\tests\units;

use \atoum;

class InternalQueryHelper extends atoum
{
    public static function createInternalQueryHelper()
    {
        return array(
            array(new \PicORM\InternalQueryHelper())
        );
    }

    /**
     * @dataProvider createInternalQueryHelper
     */
    public function testprefixWhereWithTable($internalQueryBuilder)
    {
        $where = array(
            'field1' => 'data',
            'field2' => 'data',
            'field3' => 'data'
        );

        $this
            ->if($newWhere = $internalQueryBuilder->prefixWhereWithTable($where, 'tablename'))
            ->boolean(isset($newWhere['tablename.field1']))->isEqualTo(true)
            ->boolean(isset($newWhere['tablename.field2']))->isEqualTo(true)
            ->boolean(isset($newWhere['tablename.field3']))->isEqualTo(true);
    }

    /**
     * @dataProvider createInternalQueryHelper
     */
    public function testprefixOrderWithTable($internalQueryBuilder)
    {
        $order = array(
            'field1' => 'ASC',
            'field2' => 'DESC',
            'RAND()' => ''
        );

        $this
            ->if($newOrder = $internalQueryBuilder->prefixOrderWithTable($order, 'tablename'))
            ->boolean(isset($newOrder['tablename.field1']))->isEqualTo(true)
            ->boolean(isset($newOrder['tablename.field2']))->isEqualTo(true)
            ->boolean(isset($newOrder['RAND()']))->isEqualTo(true);
    }

    /**
     * @dataProvider createInternalQueryHelper
     */
    public function testBuildWhereFromArray($selectInternalQueryBuilder)
    {
        $selectInternalQueryBuilder
            ->select('*')
            ->from('tableName')
            ->buildWhereFromArray(array(
                                      'id'       => 1,
                                      'text'     => 'hello world',
                                      'datetime' => array('NOW()'),
                                      'libelle'  => array(
                                          'operator' => 'LIKE',
                                          'value'    => array("CONCAT('%',?,'%')")
                                      )
                                  )
            );
        $resParams = $selectInternalQueryBuilder->getWhereParamsValues();

        $resultQuery = "SELECT  * FROM tableName   WHERE id = ? AND text = ? AND datetime = NOW() AND libelle LIKE CONCAT('%',?,'%')";
        $this->string($selectInternalQueryBuilder->buildQuery())->isEqualTo($resultQuery)
             ->integer($resParams[0])->isEqualTo(1)
             ->string($resParams[1])->isEqualTo('hello world');
    }

    /**
     * @dataProvider createInternalQueryHelper
     */
    public function testCleanQueryBeforeSwitching(\PicORM\InternalQueryHelper $selectInternalQueryBuilder) {

        $selectInternalQueryBuilder -> select("*") -> from("table")->innerJoin("table2","ON table.id = table2.id");
        $selectInternalQueryBuilder->cleanQueryBeforeSwitching();

        $property = new \ReflectionProperty("\PicORM\InternalQueryHelper", '_join');
        $property->setAccessible(true);

        $this
            ->integer(count($property->getValue($selectInternalQueryBuilder)))
            ->isEqualTo(0);
    }
}