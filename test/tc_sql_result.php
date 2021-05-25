<?php
class TcSqlResult extends TcBase {

	function assertSqlEquals($a, $b) {
		$m = function($s) {
			$out=preg_replace('/\s+/',' ', $s);
			$out=trim($out);
			return $out;
		};
		return $this->assertEquals($m($a), $m($b));
	}

	function test() {
		$r = new SqlResult('cards', 'id = 1');
		$this->assertSqlEquals('SELECT * FROM cards WHERE id = 1', $r->select());
		$this->assertSqlEquals('SELECT id FROM cards WHERE id = 1', $r->select('id'));
		$this->assertSqlEquals('SELECT id FROM cards WHERE id = 1 LIMIT 1', $r->select('id', ['limit' => 1]));
		$this->assertSqlEquals('SELECT id FROM cards WHERE id = 1 OFFSET 1', $r->select('id', ['offset' => 1]));
		$this->assertSqlEquals('SELECT id FROM cards WHERE id = 1 ORDER BY 1', $r->select('id', ['order' => 1]));
		$this->assertSqlEquals('SELECT id FROM cards WHERE id = 1 GROUP BY field HAVING 1=1 ORDER BY sum(id) LIMIT 1 OFFSET 2', $r->select('id', [
			'limit' => 1,
			'offset' => 2,
			'group' => 'field',
			'having' => '1=1',
			'order' => 'sum(id)']));
		$rr = new SqlResult('cards', 'id = 1', [], ['limit' => 2]);
		$this->assertSqlEquals('SELECT id FROM cards WHERE id = 1 LIMIT 1', $rr->select('id', ['limit' => 1]));
		$this->assertSqlEquals('SELECT id FROM cards WHERE id = 1 LIMIT 2 OFFSET 1', $rr->select('id', ['offset' => 1]));
		$this->assertSqlEquals('SELECT id FROM cards WHERE id = 1 ORDER BY 1 LIMIT 2', $rr->select('id', ['order' => 1]));
		$this->assertSqlEquals('SELECT id FROM cards WHERE id = 1 ORDER BY 1', $rr->select('id', ['order' => 1, 'add_options'=> false]));
		$this->assertSqlEquals('SELECT id FROM cards WHERE id = 1 ORDER BY 1', $rr->select('id', ['order' => 1, 'limit'=> null]));
		$rr->andWhere('x=1');
		$this->assertSqlEquals('SELECT id FROM cards WHERE (id = 1) AND (x=1) LIMIT 1', $rr->select('id', ['limit' => 1]));
		$rr->sqlOptions(['offset' => 5, 'limit' => 7]);
		$this->assertSqlEquals('SELECT id FROM cards WHERE (id = 1) AND (x=1) LIMIT 7 OFFSET 5', $rr->select('id'));

		$rr->addBind([':x' => 1]);
		$this->assertEquals($rr->bind, [':x' => 1]);
		$rr->addBind([':y' => 'u']);
		$this->assertEquals($rr->bind, [':x' => 1, ':y' => 'u']);

		$this->assertSqlEquals('SELECT COUNT(*) FROM cards WHERE id = 1', $r->count());
		$this->assertSqlEquals('SELECT COUNT(id) FROM cards WHERE id = 1', $r->count('id'));
		$rr = new SqlResult('cards', 'id = 1', [], ['limit' => 2, 'count_sql' => 'SELECT count(*) FROM another_table']);
		$this->assertSqlEquals('SELECT count(*) FROM another_table', $rr->count([]));

		$this->assertSqlEquals('SELECT EXISTS(SELECT * FROM cards WHERE id = 1 )', $r->exists());

		$r2 = clone $rr;
		$t = (new SqlTable('products'))->where('products.card_id = cards.id')->result();
		$rr->join($t, 'LEFT JOIN');
		$this->assertSqlEquals("SELECT * FROM cards LEFT JOIN products ON ((products.card_id = cards.id)) WHERE id = 1 LIMIT 2", $rr->select());
		$r2->join($t, 'exists');
		$this->assertSqlEquals("SELECT * FROM cards WHERE (id = 1) AND (EXISTS(SELECT * FROM products WHERE (products.card_id = cards.id) )) LIMIT 2", $r2->select());


	}

	function test_distinct_on() {
		$dbmole = PgMole::GetInstance();
		$dbmole->doQuery("CREATE TEMPORARY TABLE test__b(a,b,c) AS (VALUES
			(1,1,1),
			(1,2,2),
			(1,3,100),
			(2,10,20),
			(2,20,10)
		)");
		$r = new SqlResult('test__b', 'c!=100');
		$this->assertEquals([1,20], $dbmole->selectIntoArray($r->distinctOnSelect('b', 'a', ['order' => 'c, b'])));
		$this->assertEquals([1,2], $dbmole->selectIntoArray($r->distinctOnSelect('a', ['order' => 'c, a'])));
		$this->assertEquals([2,1], $dbmole->selectIntoArray($r->distinctOnSelect('a', ['order' => 'c DESC NULLS LAST, a'])));
		$this->assertEquals([1,20], $dbmole->selectIntoArray($r->distinctOnSelect('b', 'a', ['order' => 'c NULLS FIRST, a'])));
		$dbmole->doQuery("INSERT INTO test__b VALUES(1, NULL, 110)");
		$this->assertEquals([null,20], $dbmole->selectIntoArray($r->distinctOnSelect('b', 'a', ['order' => 'b DESC NULLS FIRST, a'])));
		$this->assertEquals([1,10], $dbmole->selectIntoArray($r->distinctOnSelect('b', 'a', ['order' => 'b NULLS LAST, a'])));
	}

}
