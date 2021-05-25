<?php
use \SqlBuilder\SqlBindQuery;

class tc_sql_bind_query extends TcBase {

	function test() {
		$dbmole = PgMole::GetInstance();
		$q = new SqlBindQuery('SELECT * FROM cards WHERE id = :id AND str = :str', [':id' => 1, ':str' => 'bum']);
		list($sql, $bind) = $q;
		$this->assertEquals('SELECT * FROM cards WHERE id = :id AND str = :str', $sql);
		$this->assertEquals([':id' => 1, ':str' => 'bum'], $bind);
		$this->assertEquals('SELECT * FROM cards WHERE id = :id AND str = :str', (string) $q);
		$this->assertEquals("SELECT * FROM cards WHERE id = 1 AND str = 'bum'", $q->escaped($dbmole));

	  $q = new SqlBindQuery('SELECT :num', [':num' => 3]);
		$this->assertEquals(3, $q->selectInt($dbmole));
	}
}
