<?php
use \SqlBuilder\SqlValues;

class TcSqlValues extends TcBase {

	function test(){
		$values = new SqlValues(["id","name"]);

		$this->assertEquals("(SELECT)",$values->sql());
		$this->assertEquals([],$values->bind());

		$this->assertEquals("data(id,name) AS ((SELECT))",$values->withSql("data"));

		$values->add([4,"Jan"]); 
		$values->add([6,"Petr"]);
		$this->assertEquals("VALUES (4,:name_0),(6,:name_1)",$values->sql());
		$this->assertEquals([":name_0" => "Jan", ":name_1" => "Petr"],$values->bind());

		$this->assertEquals("data(id,name) AS (VALUES (4,:name_0),(6,:name_1))",$values->withSql("data"));

		// --

		$values = new SqlValues(["id","name"],["bind_ar" => [":today" => "2019-06-04 17:06:00"]]);

		$this->assertEquals("(SELECT)",$values->sql());
		$this->assertEquals([":today" => "2019-06-04 17:06:00"],$values->bind());

		$values->add([4,"Jan"]);
		$values->add(6,"Petr");
		$this->assertEquals("VALUES (4,:name_0),(6,:name_1)",$values->sql());
		$this->assertEquals([":name_0" => "Jan", ":name_1" => "Petr", ":today" => "2019-06-04 17:06:00"],$values->bind());
		$this->assertEquals("CREATE TEMPORARY TABLE a AS VALUES (4,:name_0),(6,:name_1)",$values->createTemporaryTableSql('a'));
		$this->assertEquals(2,$values->count());
	}
}
