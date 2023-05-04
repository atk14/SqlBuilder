<?php
use \SqlBuilder\SqlValues;

class TcSqlValues extends TcBase {

	function test(){
		$values = new SqlValues(["id","name"]);

		$this->assertEquals("(SELECT)",$values->sql());
		$this->assertEquals([],$values->bind());

		$this->assertEquals("data(id,name) AS (SELECT NULL,NULL WHERE false)",$values->withSql("data"));

		$values->add([4,"Jan"]);
		$values->add(6,"Petr");
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
		$this->assertEquals("CREATE TEMPORARY TABLE a(id,name) AS (VALUES (4,:name_0),(6,:name_1))",$values->createTemporaryTableSql('a'));
		$this->assertEquals(2,$values->count());


		$values = new SqlValues(["id","name"]);
		$this->assertEquals("tt(id,name) AS (SELECT NULL,NULL WHERE false)", $values->withSql('tt'));
		$values = new SqlValues(["id::integer","name"],["bind_ar" => [":today" => "2019-06-04 17:06:00"]]);
		$this->assertEquals("tt(id,name) AS (SELECT NULL::integer,NULL WHERE false)", $values->withSql('tt'));
		$values = new SqlValues(["id","name"], ['types' => 'integer']);
		$this->assertEquals("tt(id,name) AS (SELECT NULL::integer,NULL::integer WHERE false)", $values->withSql('tt'));
		$values->add([1,'x']);
		$values->add([2,'y']);
		$this->assertEquals("tt(id,name) AS (VALUES (1::integer,:name_0::integer),(2,:name_1))", $values->withSql('tt'));
		$this->assertEquals("ARRAY[(1::integer,:name_0::integer),(2,:name_1)]::type[]", $values->sqlArray('type'));

		$values = new SqlValues(["id","name"], ['types' => ['integer', 'varchar']]);
		$this->assertEquals("tt(id,name) AS (SELECT NULL::integer,NULL::varchar WHERE false)", $values->withSql('tt'));
		$this->assertEquals("ARRAY[]::type[]", $values->sqlArray('type'));
		$values = new SqlValues(["id::integer"]);
		$values->addMore([1,2]);
		$this->assertEquals("ARRAY[1::integer,2]::integer[]", $values->sqlArray());
	}
}
