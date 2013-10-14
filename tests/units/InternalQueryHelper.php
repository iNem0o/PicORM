<?php
namespace PicORM\tests\units;
use \atoum;

class InternalQueryHelper extends atoum {
    public function testprefixWhereWithTable() {
        $query = new \PicORM\InternalQueryHelper();
        $where = array(
            'field1' => 'data',
            'field2' => 'data',
            'field3' => 'data'
        );

        $newWhere = $query -> prefixWhereWithTable($where,'tablename');
        $this -> variable(isset($newWhere['tablename.field1']))->isEqualTo(true);
        $this -> variable(isset($newWhere['tablename.field2']))->isEqualTo(true);
        $this -> variable(isset($newWhere['tablename.field3']))->isEqualTo(true);
    }
    public function testprefixOrderWithTable() {
        $query = new \PicORM\InternalQueryHelper();
        $order = array(
            'field1' => 'ASC',
            'field2' => 'DESC',
            'RAND()' => ''
        );

        $newOrder = $query -> prefixOrderWithTable($order,'tablename');

        $this -> variable(isset($newOrder['tablename.field1']))->isEqualTo(true);
        $this -> variable(isset($newOrder['tablename.field2']))->isEqualTo(true);
        $this -> variable(isset($newOrder['RAND()']))->isEqualTo(true);
    }


    public function testBuildWhereFromArray() {
        $select = new \PicORM\InternalQueryHelper();
        $val = 1;
        $select -> select('*') -> from('tableName');
        $resultQuery = "SELECT * FROM tableName   WHERE id = ?";
        $select -> buildWhereFromArray(array(
            'id' => $val,
            'datetime' => array('NOW()'),
            'libelle' => array(
                'operator' => 'LIKE',
                'value' => "CONCAT('%',?,'%')",
            ),
        ));
        exit($select->buildQuery());
        $resParams = $select -> getWhereParamsValues();
        $this -> variable($select->buildQuery())->isEqualTo($resultQuery);
        $this -> variable($resParams[0])->isEqualTo($val);
    }
}