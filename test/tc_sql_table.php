<?php
use \SqlBuilder\SqlTable;

class TcSqlTable extends TcBase {

	function assertSqlEquals($a, $b) {
		$m = function($s) {
			$out=preg_replace('/\s+/',' ', $s);
			$out=trim($out);
			return $out;
		};
		return $this->assertEquals($m($a), $m($b));
	}

	function test(){
		$sql = new SqlTable("cards");
		$sql->where("visible=:visible",":visible",true);
		$sql->where("title LIKE :q")->bind(":q","%Soap%");

		$this->assertSqlEquals("SELECT * FROM cards WHERE (visible=:visible) AND (title LIKE :q)",$sql->result()->select());
		$this->assertSqlEquals("SELECT * FROM cards WHERE (visible=:visible) AND (title LIKE :q) AND (inteligent)",$sql->result(['add_where' => 'inteligent'])->select());
		$this->assertSqlEquals("SELECT * FROM cards WHERE (visible=:visible) AND (title LIKE :q) AND (inteligent) AND (pretty)",$sql->result(['add_where' => ['inteligent', 'pretty']])->select());
		$this->assertEquals([':visible' => true, ':q' => '%Soap%'],$sql->result()->bind);
		$this->assertSqlEquals("SELECT id FROM cards WHERE (visible=:visible) AND (title LIKE :q)",$sql->result()->select("id"));
		$this->assertSqlEquals("SELECT id FROM cards WHERE (visible=:visible) AND (title LIKE :q) ORDER BY id LIMIT 5 OFFSET 6",$sql->result()->select("id", ['order' => 'id', 'limit' => 5, 'offset' => 6]));

		$sql->where = [];
		$sql->where('visible');
		$sql->where(['cond' => 'NOT deleted']);
		$this->assertSqlEquals("SELECT * FROM cards WHERE (visible) AND (NOT deleted)",$sql->result()->select());
		$this->assertSqlEquals("SELECT * FROM cards WHERE (visible)",$sql->result(['disable_where' => 'cond'])->select());
		$this->assertSqlEquals("SELECT * FROM cards WHERE (visible)",$sql->result(['disable_where' => ['cond']])->select());
		$this->assertSqlEquals("SELECT * FROM cards WHERE (visible) AND (NOT (NOT deleted))",$sql->result(['not_where' => ['cond']])->select());
		$this->assertSqlEquals("SELECT * FROM cards WHERE (NOT deleted)",$sql->result(['named_where_only' => true])->select());

		$sql2 = $sql->copy();
		$prods = $sql2->join('products')->where('cards.id = products.card_id');
		$this->assertSqlEquals("SELECT * FROM cards JOIN products ON ((cards.id = products.card_id)) WHERE (visible) AND (NOT deleted)",$sql2->result()->select());
		$this->assertSqlEquals("SELECT * FROM cards JOIN products ON ((cards.id = products.card_id)) WHERE (visible) AND (NOT deleted)",$sql2->result(['override_join' => ['a' => 'LEFT']])->select());
		$this->assertSqlEquals("SELECT * FROM cards LEFT JOIN products ON ((cards.id = products.card_id)) WHERE (visible) AND (NOT deleted)",$sql2->result(['override_join' => ['products' => 'LEFT JOIN']])->select());

		$sql->options['autojoins'] = true;
		$prods = $sql->join('products')->where('cards.id = products.card_id');
		$this->assertSqlEquals("SELECT * FROM cards WHERE (visible) AND (NOT deleted)",$sql->result()->select());
		$prods->where(['visible' => 'visible']);
		$this->assertSqlEquals("SELECT * FROM cards JOIN products ON ((cards.id = products.card_id) AND (visible)) WHERE (visible) AND (NOT deleted)",$sql->result()->select());
		unset($prods->where['visible']);

		$prices=$prods->join('price_items pi', 'products.id = pi.product_id', ['join' => 'LEFT JOIN']);
		$this->assertTrue($prices->isActive());

		$this->assertSqlEquals("SELECT * FROM cards JOIN (products LEFT JOIN price_items pi ON ((products.id = pi.product_id))) ON ((cards.id = products.card_id)) WHERE (visible) AND (NOT deleted)",$sql->result()->select());
		$prices->setActive('auto');
		$this->assertFalse($prices->isActive());

		$this->assertSqlEquals("SELECT * FROM cards WHERE (visible) AND (NOT deleted)",$sql->result()->select());
		$prices->where(['cond' => 'not deleted']);
		$this->assertTrue($prices->isActive());
		$this->assertEquals('pi', $prices->getName());

		$this->assertSqlEquals("SELECT * FROM cards JOIN (products LEFT JOIN price_items pi ON ((products.id = pi.product_id) AND (not deleted))) ON ((cards.id = products.card_id)) WHERE (visible) AND (NOT deleted)",$sql->result()->select());
		$prices->setActive(false);
		$this->assertSqlEquals("SELECT * FROM cards WHERE (visible) AND (NOT deleted)",$sql->result()->select());
		$this->assertSqlEquals("SELECT * FROM cards JOIN (products LEFT JOIN price_items pi ON ((products.id = pi.product_id) AND (not deleted))) ON ((cards.id = products.card_id)) WHERE (visible) AND (NOT deleted)",$sql->result(['active_join' => 'pi'])->select());
	}


	function test_naming() {
		$sql = new SqlTable('cards');
		$this->assertEquals('cards', $sql->getName());
		$this->assertEquals('cards', $sql->getTableName());
		$this->assertEquals('cards', $sql->getSqlTable());

		$sql = new SqlTable('cards alias');
		$this->assertEquals('alias', $sql->getName());
		$this->assertEquals('alias', $sql->getTableName());
		$this->assertEquals('cards', $sql->getSqlTable());

		$sql = new SqlTable('cards JOIN products ON (id = ud)');
		$this->assertEquals('products', $sql->getName());
		$this->assertEquals('products', $sql->getTableName());
		$this->assertEquals('cards JOIN products ON (id = ud)', $sql->getSqlTable());

		$sql = new SqlTable('(cards JOIN products ON (id = ud)) alias');
		$this->assertEquals('alias', $sql->getName());
		$this->assertEquals('alias', $sql->getTableName());
		$this->assertEquals('(cards JOIN products ON (id = ud))', $sql->getSqlTable());

	}

	function test_materialize() {
		$dbmole = PgMole::GetInstance();
		$dbmole->doQuery("CREATE TEMPORARY TABLE test__a(a,b,c) AS (VALUES (1,1,1), (2,2,2), (3,3,3))");
		$mat = (new SqlTable("test__a", [], [], ['limit => 1', 'offset' => 50]))->where('a>1')->materialize($dbmole, "a,b");
		$this->assertEquals(5, $dbmole->selectInt($mat->result()->select('SUM(a)')));
		$this->assertEquals(['a', 'b'], $dbmole->selectIntoArray("SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = :name", [':name' => $mat->getSqlTable() ]));
	}

}
