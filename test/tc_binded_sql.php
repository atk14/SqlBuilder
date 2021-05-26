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

	function test_concat() {
		$a = new BindedSql('1', ['1' => 'a']);
		$b = new BindedSql('2', ['2' => 'b']);
		$out = $a->concat($b);
		$this->assertTrue($a !== $out);
		$this->assertTrue($b !== $out);
		$this->assertEquals('12', $out->sql);
		$this->assertEquals(['1' => 'a', '2' => 'b'], $out->bind);

		$out = $a->concat('45');
		$this->assertTrue($a !== $out);
		$this->assertEquals('145', $out->sql);
		$this->assertEquals(['1' => 'a'], $out->bind);

		$a->append('45');
		$this->assertEquals('145', $a->sql);
		$this->assertEquals(['1' => 'a'], $a->bind);
		$a->append($b);
		$this->assertEquals('1452', $a->sql);
		$this->assertEquals(['1' => 'a', '2' => 'b'], $a->bind);

		$a = new BindedSql('1', ['1' => 'a']);
		$out = BindedSql::Concatenate($a, 45);
		$this->assertTrue($a !== $out);
		$this->assertEquals('145', $out->sql);
		$this->assertEquals(['1' => 'a'], $out->bind);

		$out = BindedSql::Concatenate('', $a);
		$this->assertTrue($a !== $out);
		$this->assertEquals('1', $out->sql);
		$this->assertEquals(['1' => 'a'], $out->bind);

		$out = BindedSql::Concatenate($a, $b);
		$out = $a->concat($b);
		$this->assertTrue($a !== $out);
		$this->assertTrue($b !== $out);
		$this->assertEquals('12', $out->sql);
		$this->assertEquals(['1' => 'a', '2' => 'b'], $out->bind);


	}
}
