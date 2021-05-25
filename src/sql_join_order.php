<?php
namespace SqlBuilder {

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
			$this->array = FieldsUtils::SplitFieldsToArray($this->order);
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
		return FieldsUtils::SplitOrderOptionsFromFields($this->asArray());
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
			$this->array = array_map(['\SqlBuilder\FieldsUtils', 'ReverseOrder'], $fields);
			$this->order = null;
		}
	}

}
}
