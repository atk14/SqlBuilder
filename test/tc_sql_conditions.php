<?php
class TcSqlConditions extends TcBase {

	function test(){
		$sql = new SqlConditions("cards");
		$sql->where("visible=:visible",":visible",true);
		$sql->where("title LIKE :q")->bind(":q","%Soap%");

		$this->assertEquals(ltrim("
 SELECT id 
 FROM cards  
 WHERE (visible=:visible) AND (title LIKE :q) "),$sql->result()->select("id"));
	}
}
