<?php
use \SqlBuilder\SqlJoinOrder;
use \SqlBuilder\SqlResult;

class TcSqlJoinOrder extends TcBase {

	function assertSqlEquals($a, $b) {
		$m = function($s) {
			$out=preg_replace('/\s+/',' ', $s);
			$out=trim($out);
			return $out;
		};
		return $this->assertEquals($m($a), $m($b));
	}

	function test_base_usage(){
		$o = new SqlJoinOrder("a,b,c");
		$this->assertEquals("a,b,c",$o->asString());
		$this->assertEquals(["a","b","c"],$o->asArray());

		$o = new SqlJoinOrder(["d","e","f"]);
		$this->assertEquals("d, e, f",$o->asString());
		$this->assertEquals(["d","e","f"],$o->asArray());

		$o = new SqlJoinOrder("a,b,cards.c", "JOIN cards");
		$this->assertEquals("a,b,cards.c",$o->asString());
		$this->assertEquals(["a","b","cards.c"],$o->asArray());
	}

	function test() {
		$o = new SqlJoinOrder(null);
		$this->assertEquals([], $o->asArray());
		$this->assertFalse($o->isOrdered());
		$this->assertFalse($o->reversed()->isOrdered());
		$this->assertEquals(0, $o->fieldsCount());
		$o->prependOrder('x');
		$this->assertTrue($o->isOrdered());
		$this->assertEquals("x", $o->asString());
		$this->assertEquals(["x"], $o->asArray());
		$o->reorder('a,b');
		$this->assertEquals("a,b", $o->asString());
		$this->assertEquals(2, $o->fieldsCount());
		$this->assertEquals(["a","b"], $o->asArray());


		$o = new SqlJoinOrder("a, b DESC, c NULLS FIRST");
		$this->assertTrue($o->isOrdered());
		$this->assertEquals(["a", "b DESC", "c NULLS FIRST"], $o->asArray());
		$this->assertEquals("a DESC, b ASC, c DESC NULLS LAST", $o->reversed()->asString());
		$o = new SqlJoinOrder("a, b ASC, c DESC NULLS LAST");
		$this->assertEquals("a DESC, b DESC, c ASC NULLS FIRST", $o->reversed()->asString());
		$this->assertEquals([['a', 'b', 'c'], ['',' ASC',' DESC NULLS LAST']], $o->splitOptions());

		$o = new SqlJoinOrder("aaa, b - ASCNULLSFIRST");
		$this->assertEquals("aaa DESC, b - ASCNULLSFIRST DESC", $o->reversed()->asString());

		$r = new SqlResult('cards');
		$this->assertSqlEquals('SELECT * FROM cards', $r->select());
		$this->assertSqlEquals('SELECT * FROM cards ORDER BY aaa, b - ASCNULLSFIRST', $r->select('*', ['order' => $o]));
		$o->join ='JOIN products ON (xxx)';
		$this->assertSqlEquals('SELECT * FROM cards JOIN products ON (xxx) ORDER BY aaa, b - ASCNULLSFIRST', $r->select('*', ['order' => $o]));
		$this->assertSqlEquals('SELECT * FROM cards JOIN products ON (xxx) ORDER BY aaa DESC, b - ASCNULLSFIRST DESC', $r->select('*', ['order' => $o->reversed()]));

		$o2 = $o->reversed();
		$o2->prependOrder('X');
		$this->assertEquals(["X", "aaa DESC", "b - ASCNULLSFIRST DESC"], $o2->asArray());
		$this->assertEquals(3, $o2->fieldsCount());
	}
}
