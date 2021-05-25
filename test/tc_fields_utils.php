<?php

use \SqlBuilder\FieldsUtils;


class TcFieldsUtils extends TcBase {

	function data_split_fields_to_array() {
		return array_chunk([
			['a', 'b', 'c'],
			['a+b', '(c+d*(e+fffff))'],
			['eeee', 'zzz(uu,fff(fff,vvvv))' ],
			['eeee', 'zzz(uu,fff(")))",vvvv))' ],
			['eeee', "zzz(uu,fff(\")))'\",'))',vvvv))" ],
		],1);
	}
	/**
	 * @dataProvider data_split_fields_to_array
	 **/
	function test_split_fields_to_array($data) {
		$this->assertEquals($data, FieldsUtils::SplitFieldsToArray(implode(', ', $data)));
	}

	function data_reverse_order() {
		return array_chunk([
			'a', 'a DESC',
			'a DESC', 'a ASC',
			'a(x) DESC NULLS FIRST', 'a(x) ASC NULLS LAST',
			'a(x) - 5 DESC NULLS LAST', 'a(x) - 5 ASC NULLS FIRST',
			'a DESC NULLS', 'a DESC NULLS DESC',
			'"a DESC"', '"a DESC" DESC',
			'aDESC NULLS FIRST', 'aDESC DESC NULLS LAST',
			'a)DESC NULLS FIRST', 'a) ASC NULLS LAST',
		],2);
	}
	/**
	 * @dataProvider data_reverse_order
	 **/
	function test_reverse_order($data, $result) {
		$this->assertEquals($result, FieldsUtils::ReverseOrder($data));
	}

	function data_split_order_options_from_field() {
		return array_chunk([
			'a', '',
			'"adsaddd"',' NULLS FIRST',
			'"ads  addd"',' nulls first',
			'"adsa   ddd"',' nulls first',
			"'asdada NULLS'",' NULLS LAST',
			'a(x,1)',' DESC',
			'addd - sadsad + asdasdsa',' ASC',
			'((a + 1 * NULLS LAST))',' ASC NULLS LAST',
			'faaa',' ASC NULLS FIRST',
			'ffdadad ad ad asasd ada',' ASC NULLS LAST',
			'(SELECT * FROM X ORDER BY asadasd)',' ASC NULLS FIRST',
		],2);
	}
	/**
	 * @dataProvider data_split_order_options_from_field
	 **/
	function test_split_order_options_from_field($a, $b) {
		$this->assertEquals([$a,$b], FieldsUtils::SplitOrderOptionsFromField("$a$b"));
	}

	function data_split_order_options_from_fields() {
		return array_chunk([
			'a DESC, b DESC, c ASC NULLS FIRST', [['a', 'b', 'c'], [' DESC',' DESC',' ASC NULLS FIRST']],
		], 2);
	}
	/**
	 * @dataProvider data_split_order_options_from_fields
	 **/
	function test_split_order_options_from_fields($data, $result) {
		$this->assertEquals($result, FieldsUtils::SplitOrderOptionsFromFields($data));
	}

	function data_strip_field() {
		return array_chunk([
			'a', 'a',
			'aa.bb', 'aa.bb',
			'NOT a', 'a',
			'NOT a DESC', 'a',
			'fce(A0_1) NULLS FIRST', 'A0_1',
			'fce1(fce2(axx)) ASC', 'axx',
			'fce(a,b)', null,
			"'a'", null,
		], 2);
	}
	/**
	 * @dataProvider data_strip_field
	 **/
	function test_strip_field($data, $result) {
		$this->assertEquals($result, FieldsUtils::StripField($result));
	}

	function data_strip_fields() {
		return array_chunk([
			'a', ['a'],
			'NOT a, b', ['a', 'b'],
			'NOT a DESC, (a + b)', ['a'],
			'fce(a) NULLS FIRST,fce(c,d),d,e', ['a','d','e'],
			'fce1(fce2(axx)) ASC', ['axx']
		], 2);
	}
	/**
	 * @dataProvider data_strip_fields
	 **/
	function test_strip_fields($data, $result) {
		$this->assertEquals($result, FieldsUtils::StripFields($result));
	}

}
