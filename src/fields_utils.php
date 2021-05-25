<?php
namespace SqlBuilder {

class FieldsUtils {

	/**
	 * splitFieldsToArray("a, b, count(distinct d), a+b")
   * > [ 'a', 'b', 'count(distinct d)', a+b ]
   **/
	static function SplitFieldsToArray($fields) {
		$fields = trim($fields);
		if($fields=='') return [];
		$out = [];
		$start = 0;
		$len = strlen($fields);
		$state = null;
		$parenthesis = 0;

		for($i=0;$i<strlen($fields);$i++) {
			switch($state) {
				case "'": if($fields[$i]=="'") $state=''; break;
				case '"': if($fields[$i]=='"') $state=''; break;
				default: switch($fields[$i]) {
				case '"': $state = '"'; break;
				case "'": $state = "'"; break;
				case '(': $parenthesis +=1;break;
				case ')': $parenthesis -=1;break;
				case ',': if(!$parenthesis) {
						$out[] = trim(substr($fields,$start,$i-$start));
						$start = $i+1;
					};
					break;
				}
				break;
			}
		}

		$out[] = trim(substr($fields, $start));
		return $out;
	}

	/***
	 * reverseOrder(a)
	 * > a DESC
	 * reverseOrder(aaa ASC NULLS FIRST)
	 * > aaaa DESC NULLS LAST
	 ***/
	static function ReverseOrder($field) {
		$nulls = '(\bNULLS\s+(FIRST|LAST))?';
		if(!preg_match("/^(.*?)\s*(?:\b(DESC|ASC)\s*)?$nulls\s*$/i", $field, $matches)) {
			return trim($field) . ' DESC';
		}
		$asc = (count($matches)>2 && strtolower($matches[2]) === 'desc')?' ASC':' DESC';
		if(count($matches) > 3) {
			$nulls = ' NULLS ';
			$nulls .= strtolower($matches[4]) === 'first' ? 'LAST':'FIRST';
		} else {
			$nulls = '';
		}
		return $matches[1] . $asc . $nulls;
	}

	/***
	 * SplitOrderOptions('a, b DESC, c NULLS FIRST');
   * >> [ ['a', 'b', 'c' ], ['','DESC', 'NULLS FIRST'] ]
	 ***/
	static function SplitOrderOptionsFromFields($fields) {
			if(!is_array($fields)) {
				$fields=static::SplitFieldsToArray($fields);
			}
			$out=array_map(['\SqlBuilder\FieldsUtils','SplitOrderOptionsFromField'], $fields);
			return [ array_column($out, 0), array_column($out, 1) ];
	}

	/***
	 * SplitOrderOptions('a DESC');
   * >> ['a', 'DESC']
	 ***/
	static function SplitOrderOptionsFromField($field) {
			$base = preg_replace('/([^\s])\s+((ASC|DESC)(\s+|$))?(NULLS\s+(FIRST|LAST))?\s*$/i','\1', $field);
			$order = substr($field,strlen($base));
			return [$base, $order];
	}

	/***
	 * StripField('NOT fce(X)')
	 * > X
	 * Not reliable, just a fast guess. If the field is more complex, the function returns null.
   **/
	static function StripField($field) {
			$base = self::SplitOrderOptionsFromField($field);
			$old = '';
			while($old !== $field) {
				$old = $field;
				$field = preg_replace('/^\s*[a-z0-9_]*\s*\(\s*(.*)\s*\)\s*$/i', '\1', $field);
				$field = preg_replace('/\s*(?:NOT|-)\s*(.*)/','\1', $field);
			}
			$field = trim($field);
			if(!preg_match("/^[a-z0-9_]+(\.[a-z0-9_])*$/i", $field)) {
				return null;
			}
			return $field;
	}

	/***
	 * StripField('NOT fce(X), Y DESC')
	 * > [ X,Y ]
	 * Not reliable, just a fast guess
	 **/
	static function StripFields($fields) {
		if(!is_array($fields)) {
			$fields=static::SplitFieldsToArray($fields);
		}
		return array_filter(array_map(['\SqlBuilder\FieldsUtils','StripField'], $fields));
	}

}
}
