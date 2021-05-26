<?php
use \SqlBuilder\BindedSql;

class tc_binded_sql extends TcBase {

	function test() {
		$dbmole = PgMole::GetInstance();
		$q = new BindedSql('SELECT * FROM cards WHERE id = :id AND str = :str', [':id' => 1, ':str' => 'bum']);
		list($sql, $bind) = $q;
		$this->assertEquals('SELECT * FROM cards WHERE id = :id AND str = :str', $sql);
		$this->assertEquals([':id' => 1, ':str' => 'bum'], $bind);
		$this->assertEquals('SELECT * FROM cards WHERE id = :id AND str = :str', (string) $q);
		$this->assertEquals("SELECT * FROM cards WHERE id = 1 AND str = 'bum'", $q->escaped($dbmole));

	  $q = new BindedSql('SELECT :num', [':num' => 3]);
		$this->assertEquals(3, $q->selectInt($dbmole));
	}
}
