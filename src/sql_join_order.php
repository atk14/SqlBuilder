<?php

/**
 *  Class, that holds order for a given table, possibly with a join clause:
 *  new SqlJoinOrder('a,b,c');
 *  new SqlJoinOrder(['a','b','c']);
 *  new SqlJoinOrder('a,b,cards.c', 'JOIN cards');
 *  To be used with sqlResult:
 *  $sqlResult->select('id', ['order' => SqlJoinOrder('rank',
 *                                                    'JOIN (SELECT rank FROM ranktable WHERE ...) order_table'
 **/

class SqlJoinOrder {

	static function ToSqlJoinOrder($order) {
		return $order instanceof SqlJoinOrder ? $order : new SqlJoinOrder($order);
	}

	function __construct($order, $join='', $reversed=false) {
		$this->join = $join;
		$this->reorder($order);
		$this->reversed=$reversed;
	}

	function isOrdered() {
		return $this->array || $this->order;
	}

	function prependOrder($field) {
		$this->_handle_reverse();
		if($this->array) {
			array_unshift($this->array, $field);
			$this->order = null;
		} else {
			if($this->order!=='') {
				$this->order = "$field,".$this->order;
			} else {
				$this->order = $field;
			}
			$this->array = null;
		}
		return $this;
	}
	/**
	 *  Return order fields as array
	 **/
	function asArray() {
		$this->_handle_reverse();
		if($this->array === null) {
			$this->array = static::SplitFieldsToArray($this->order);
		}
		return $this->array;
	}

	/**
	 *  Return order fields as string
	 **/
	function asString() {
		$this->_handle_reverse();
		if($this->order === null) {
			$this->order = implode(', ', $this->array);
		}
		return $this->order;
	}

	/***
	 * new SqlJoinOrder('a,b DESC, c NULLS FIRST')->splitOptions()
   * >> [ ['a', 'b', 'c' ], ['','DESC', 'NULLS FIRST'] ]
	 ***/
	function splitOptions() {
		return static::SplitOptionsFrom($this->asArray());
	}

	/***
	 * SqlJoinOrder::SplitOptionsFrom('a, b DESC, c NULLS FIRST');
   * >> [ ['a', 'b', 'c' ], ['','DESC', 'NULLS FIRST'] ]
	 ***/
	static function splitOptionsFrom($order) {
			if(!is_array($order)) {
				$order=static::SplitFieldsToArray($order);
			}
			$fields = array_map(function ($v) { return preg_replace('/([^\s])\s+((ASC|DESC)(\s+|$))?(NULLS\s+(FIRST|LAST))?\s*$/i','\1', $v); },  $order);
			$desc = array_map(function ($order, $field) {return substr($order,strlen($field));}, $order, $fields);
			return [$fields, $desc];
	}

	function fieldsCount() {
			return count($this->asArray());
	}

	function reorder($new_order) {
		if(is_array($new_order)) {
			$this->order = null;
			$this->array = $new_order;
		} else {
			$this->order = (string) $new_order;
			$this->array = null;
		}
		return $this;
	}

	function reversed() {
		return new SqlJoinOrder($this->order, $this->join, !$this->reversed);
	}

	function _handle_reverse() {
		if($this->reversed) {
			$this->reversed = false;
			$fields = $this->asArray();
			$this->array = array_map([$this, 'ReverseOrder'], $fields);
			$this->order = null;
		}
	}

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


}
