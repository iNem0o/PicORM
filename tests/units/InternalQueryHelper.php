<?php
namespace PicORM\tests\units;
use \atoum;

class InternalQueryHelper extends atoum {
    public static function createInternalQueryHelper() {
        return array(
            array(new \PicORM\InternalQueryHelper())
        );
    }
    /**
     * @dataProvider createInternalQueryHelper
     */
    public function testprefixWhereWithTable($internalQueryBuilder) {
        $where = array(
            'field1' => 'data',
            'field2' => 'data',
            'field3' => 'data'
        );

        $newWhere = $internalQueryBuilder -> prefixWhereWithTable($where,'tablename');
        $this -> variable(isset($newWhere['tablename.field1']))->isEqualTo(true);
        $this -> variable(isset($newWhere['tablename.field2']))->isEqualTo(true);
        $this -> variable(isset($newWhere['tablename.field3']))->isEqualTo(true);
    }
    /**
     * @dataProvider createInternalQueryHelper
     */
    public function testprefixOrderWithTable($internalQueryBuilder) {
        $order = array(
            'field1' => 'ASC',
            'field2' => 'DESC',
            'RAND()' => ''
        );

        $newOrder = $internalQueryBuilder -> prefixOrderWithTable($order,'tablename');

        $this -> variable(isset($newOrder['tablename.field1']))->isEqualTo(true);
        $this -> variable(isset($newOrder['tablename.field2']))->isEqualTo(true);
        $this -> variable(isset($newOrder['RAND()']))->isEqualTo(true);
    }

    /**
     * @dataProvider createInternalQueryHelper
     */
    public function testBuildWhereFromArray($selectInternalQueryBuilder) {
        $val = 1;
        $selectInternalQueryBuilder -> select('*') -> from('tableName');
        $resultQuery = "SELECT * FROM tableName   WHERE id = ? AND datetime = NOW() AND libelle LIKE CONCAT('%',?,'%')";
        $selectInternalQueryBuilder -> buildWhereFromArray(array(
            'id' => $val,
            'datetime' => array('NOW()'),
            'libelle' => array(
                'operator' => 'LIKE',
                'value' => array("CONCAT('%',?,'%')"),
            ),
        ));
        $resParams = $selectInternalQueryBuilder -> getWhereParamsValues();
        $this -> variable($selectInternalQueryBuilder->buildQuery())->isEqualTo($resultQuery);
        $this -> variable($resParams[0])->isEqualTo($val);
    }
}