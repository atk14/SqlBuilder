<?php
use \SqlBuilder\SqlTable;
use \SqlBuilder\MaterializedSqlTable;

class TcMaterializedSqlTable extends TcBase {

	function test() {
		global $dbmole;
		$dbmole = PgMole::GetInstance();
		$dbmole->doQuery('CREATE TEMPORARY SEQUENCE test__t START WITH 1');
		$table = new SqlTable("(SELECT 1 as a, nextval('test__t') as seq, i FROM generate_series(1,3) g(i)) tab");

		$mt = new MaterializedSqlTable($table, $dbmole, ['fields' => 'a, seq, i']);
		$mt->where('i > 1');
		$this->assertEquals(2, $mt->result()->count()->selectInt());
		$mt->where('seq < 4');
		$this->assertEquals(2, $mt->result()->count()->selectInt());
		$this->assertEquals(2, $mt->result()->count()->selectInt());
		$this->assertEquals(1, $mt->table->result()->count()->selectInt());
		$this->assertEquals(0, $mt->table->result()->count()->selectInt());
		$this->assertEquals(2, $mt->result()->count()->selectInt());
		$mt->where('seq < 3');
		$this->assertEquals(2, $mt->result()->count()->selectInt());


		$dbmole->doQuery('ALTER SEQUENCE test__t RESTART WITH 1');
		$table = new SqlTable("(SELECT 1 as a, nextval('test__t') as seq, i FROM generate_series(1,3) g(i)) tab");
		$mt = new MaterializedSqlTable($table, $dbmole, ['fields' => 'a, seq, i'], ['materialize' => false]);
		$mt->where('seq < 6');
		$this->assertEquals(3, $mt->result()->count()->selectInt());
		$this->assertEquals(2, $mt->result(['materialize' => true])->count()->selectInt());
		$this->assertEquals(2, $mt->result()->count()->selectInt());
		$mt->join('(VALUES (1,2)) x(a,b) ', 'x.a = tab.i');
		$this->assertEquals(1, $mt->result()->count()->selectInt());
	}
}
