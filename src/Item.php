<?php
/**
 * SuperTable 列表类
 *
 * CLASS 
 *
 * 	   SuperTable 
 *
 * USEAGE: 
 *
 *     不要直接使用
 * 
 */

namespace Tuanduimao\Supertable;

class Item {
	
	private $_data = [];

	function __construct( $data ) {
		$this->_data = $data;
	}

	function get( $name ) {
		return $this->_data[$name];
	}

	function set( $name, $value ) {
		$this->_data[$name] = $value;
		return $this->get($name);
	}

	function toArray() {
		return $this->_data;
	}

	function toJSON() {
		return json_encode($this->toArray());
	}
}