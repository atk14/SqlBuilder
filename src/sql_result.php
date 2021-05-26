<?php
namespace SqlBuilder {

/**
 * Result of SqlTable - flattenation of the joined tables to one object
 * Usage:
 * $result = new SqlResult('cards', 'visible = :visible', [':visible' => true ],[ 'limit' =>10 ]);
 * $this->dbmole->selectRows($result->select('id'), $result->bind);
 * $this->dbmole->selectRows($result->select('id'), $result->bind);
 */

class SqlResult {
	function __construct($table='', $where='', $bind = [], $sqlOptions = []) {
		$this->table = $table;
		$this->join = '';
		$this->where = $where;
		$this->bind = $bind;
		$this->sqlOptions = $this->prepareSqlOptions($sqlOptions + ['add_options' => false]);
	}

	/** Default options for **/
	static $DefaultSqlOptions = [
			'order' => null,
			'group' => null,
			'having' => null,
			'limit' => null,
			'offset' => null,
			'add_options' => true //if false, DO NOT add $this->sqlOptions
		];

	function exists($options=[]) {
		return $this->_bindQuery('SELECT EXISTS(' . $this->select($options) . ')');
	}

	function _bindQuery($sql) {
		return new BindedSql($sql, $this->bind);
	}

	function count($field='*', $options=[]) {
		if(is_array($field)) {
			$options=$field;
			$field='*';
		}
		if(isset($this->sqlOptions['count_sql'])) {
			return $this->sqlOptions['count_sql'];
		}
		return $this->select("COUNT($field)", $options);
	}

	function setCountSql($sql) {
		$this->sqlOptions['count_sql'] = $sql;
	}
	/***
	 * Return query selecting given field
	 * $result = new SqlResult('cards', 'visible');
	 * $result->select('id');
	 * >> SELECT id FROM cards WHERE visible
   */
	function select($field = '*', $options = []) {
		if(is_array($field)) {
			$options=$field;
			$field='*';
		}
		$where = $this->where;
		if($where) {
			$where = "WHERE $where";
		}
		$join = $this->_joinWithOrderJoin($options);
		return $this->_bindQuery("SELECT {$field} \n FROM {$this->table} {$join} \n $where {$this->_tail($options)}");
	}

	function tableString() {
		return "{$this->table} {$this->join}";
	}

	function _joinWithOrderJoin(&$sqlOptions) {
		$sqlOptions = $this->prepareSqlOptions($sqlOptions);
		$join = $this->join;
		if($sqlOptions['order'] instanceof SqlJoinOrder) {
			$join .= $sqlOptions['order']->join;
			$sqlOptions['order'] = $sqlOptions['order']->asString();
		}
		return $join;
	}

	/***
	 * Select only rows distinct on given field, but sorted according to the
	 * another fields. It is a bit tricky - it requires distinct-on subquery
	 * with proper fields. Resulting query has sheme
	 * SELECT f1,f2 FROM (<DISTINCTED QUERY>) _q(f1,f2,f3,f4,f5) ORDER BY 3,4,5
	 * $ids = call_user_func_array([$dbmole,'selectIntoArray'], $result->distinctOnSelect());
	 */
	function distinctOnSelect($field = 'id', $distinct_on=null, $sqlOptions = []) {

		if($distinct_on === null) {
			$distinct_on = $field;
		} elseif(is_array($distinct_on)) {
			$sqlOptions = $distinct_on;
			$distinct_on = $field;
		};

		$sqlOptions = $this->prepareSqlOptions($sqlOptions);
		$orderObj = SqlJoinOrder::ToSqlJoinOrder($sqlOptions['order']);
		if(!$orderObj->isOrdered()) {
			$query = $this->select("distinct on ($distinct_on) $field");
		} else {
			#odstranime ASC DESC do separatniho pole
			list($order_fields, $desc) = $orderObj->splitOptions();
			//Tricky part: because of the ordering result must be
			//out of the distincted query to be applied, the result should be
			// SELECT f1,f2 FROM (<DISTINCTED QUERY>) _q(f1,f2,f3,f4,f5) ORDER BY 3,4,5
			//subquery fields
			$sub_fields = $field . ',' . implode(',', $order_fields);
			$flen=static::_countFields($field, $sqlOptions, 'fields_count');
			//aliases of the fields
			$alias = range(1,$orderObj->fieldsCount() +$flen);
			$alias = array_map(function ($v) {return "f$v";}, $alias);
			//outer fields
			$fields = implode(',', array_slice($alias, 0, $flen));
			//outer order
			$order = array_slice($alias, $flen);
			$order = array_map(function($o, $d) {return $o . $d;}, $order, $desc);

			$sqlOptions['order'] = $orderObj->prependOrder($distinct_on);
			$query = $this->select("distinct on ($distinct_on) $sub_fields",
				['limit' => false, 'offset' => false] + $sqlOptions);
			$order = implode(',', $order);
			$alias = implode(',', $alias);
			$query = "SELECT $fields FROM ($query) _q($alias) ORDER BY $order, $fields " . $this->limitOffset($sqlOptions);
		}
		return $this->_bindQuery($query);
	}

	/***
	 * Try to guess number of fields in SQL fragment
	 * Not reliable, so one can give the propper number of fields
	 * in options of given name (see distinctOnSelect)
	 */
	static function _countFields($sql, $opts=[], $name=null) {
		if(isset($opts[$name]) && $opts[$name]) {
			return $opts[$name];
		}
		do {
			$old = $sql;
			$sql=preg_replace("/[(][^()]*[)]/",'', $sql);
		} while($sql != $old);
		$sql = preg_replace('/[^,]/','', $sql);
		return strlen($sql) + 1;
	}

	/***
	 * Return tail part of SQL query according to the options
	 * $result->tail(['group' => 'brand_id', 'limit' => 5]);
	 * > GROUP BY brand_id LIMIT 5
   **/
	function tail($sqlOptions = []) {
		$sqlOptions = $this->prepareSqlOptions($sqlOptions);
		return $this->_tail($sqlOptions);
	}

	function _tail($sqlOptions = []) {
		$tail='';
		if($sqlOptions['group']) { $tail=' GROUP BY '.$sqlOptions['group']; }
		if($sqlOptions['having']) { $tail.=' HAVING '.$sqlOptions['having']; }
		if($sqlOptions['order']) {
			$order = $sqlOptions['order'];
			if(is_array($order)) {
				$order = implode(',', $order);
			}
			$tail.="\n ORDER BY $order";
		}
		if($sqlOptions['limit']) { $tail.=' LIMIT '.$sqlOptions['limit']; }
		if($sqlOptions['offset']) { $tail.=' OFFSET '.$sqlOptions['offset']; }
		return $tail;
	}

	/**
	 * Return limit offset part of sql query
	 * $result->tail(['group' => 'brand_id', 'limit' => 5]);
	 * > LIMIT 5
	 */
	function limitOffset($sqlOptions = []) {
		$sqlOptions = ['group' => false, 'having' => false, 'order'=>false] +
									$sqlOptions;
		return $this->tail($sqlOptions);
	}

	/** Set SQL options
	 *  $result->SqlOptions(['limit' => 10]);
   *  echo $result->select('id');
	 *  >> SELECT id FROM cards LIMIT 10;
   *  echo $result->select('id', ['add_options' => false]);
	 *  >> SELECT id FROM cards;
	 **/
	function sqlOptions($options) {
		$this->sqlOptions = $options + $this->sqlOptions;
	}

	/***
	 * Join another Sql result to given result
	 * $result = new SqlResult('cards');
	 * $child = new SqlResult('product', 'cards.id = product.id');
	 * $result->join($child, 'LEFT JOIN');
	 * echo $result->select('id, product.id');
	 * > SELECT id, product.id FROM cards LEFT JOIN products ON ( cards.id = product.id )
	 */
	function join($child, $by='JOIN') {
		if( $by === 'exists') {
			$this->andWhere("EXISTS({$child->select('*')})");
		} else {
			if($child->join) {
				$table = "({$child->table} {$child->join})";
			} else {
				$table = $child->table;
			}
			$this->join .= "\n $by $table ON ({$child->where})";
		}
		$this->bind+=$child->bind;
		return $this;
	}

	/***
	 * Add condition
	 * $result = new SqlResult('cards');
	 * $result->addWhere('brand_id = 1');
	 * echo $result->select('id');
	 * > SELECT id FROM cards WHERE brand_id = 1
	 */
	function andWhere($where) {
		if(!$where) { return; }
		if($this->where) {
			$this->where = "({$this->where}) AND ($where)";
		} else {
			$this->where = $where;
		}
	}

	//INTERNAL METHODS

	/**
	 * Complete Sql options either by self::$DefaultValues (if add_options = false) or
	 * $this->sqlOptions
	 */
	function prepareSqlOptions($options = []) {
		if($options === false) {
			$options = self::$DefaultSqlOptions;
		} elseif(key_exists('add_options', $options) && !$options['add_options']) {
			$options += self::$DefaultSqlOptions;
			unset($options['add_options']);
		} else {
			$options +=$this->sqlOptions;
		}
		return $options;
	}

	function addBind($bind) {
		$this->bind+=$bind;
	}
}
}
