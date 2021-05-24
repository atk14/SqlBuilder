<?php
class TcSqlWhere extends TcBase {

	function test() {
		$w = new SqlWhere();
		$this->assertTrue($w->isEmpty());
		$this->assertEquals('FALSE', (clone $w)->not());
		$this->assertEquals('1=1', (string) (clone $w)->or('1=1'));
		$this->assertEquals('TRUE', (clone $w)->or('1=1', true));
		$w->and('id = 1');
		$this->assertFalse($w->isEmpty());
		$this->assertEquals('id = 1', (string) $w);
		$w->and('ud = 2');
		$this->assertEquals('(id = 1) AND (ud = 2)', (string) $w);
		$w->or('x = 2');
		$this->assertEquals('((id = 1) AND (ud = 2)) OR (x = 2)', (string) $w);
	}

}
