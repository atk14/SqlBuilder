<?php
use \SqlBuilder\SqlWhere;

class TcSqlWhere extends TcBase {

	function test() {
		if(PHP_VERSION_ID > 70000) {
			$data = [['and', 'or'], ['andWith', 'orWith']];
		} else {
			$data = [['andWith', 'orWith']];
		}

		foreach($data as $d) {
			list($and, $or) = $d;

			$w = new SqlWhere();
			$this->assertTrue($w->isEmpty());
			$ww= clone $w;
			$this->assertEquals('FALSE', $ww->not());
			$clone = clone $w;
			$this->assertEquals('1=1', (string) $clone->$or('1=1'));
			$ww= clone $w;
			$this->assertEquals('TRUE', $ww->$or('1=1', true));
			$w->$and('id = 1');
			$this->assertFalse($w->isEmpty());
			$this->assertEquals('id = 1', (string) $w);
			$w->$and('ud = 2');
			$this->assertEquals('(id = 1) AND (ud = 2)', (string) $w);
			$w->$or('x = 2');
			$this->assertEquals('((id = 1) AND (ud = 2)) OR (x = 2)', (string) $w);
		}
	}

}
