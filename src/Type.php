<?php
/**
 * SuperTable 基类
 *
 * CLASS 
 *
 * 	   Schema 
 *
 * USEAGE: 
 *
 *     不要直接使用
 * 
 */

namespace Tuanduimao\Supertable;

class Type {
	
	private $_name;
	private $_data;
	private $_options;


	function __construct( $name, $data, $options ) {
		$this->_name =  $name;
		$this->_options = $options;
		$this->_data = $data;
	}

	function toJSON() {
		return json_encode($this->toArray());
	}
	
	function toArray() {
		return array(
			'name' => $this->_name,
			'options' => $this->_options,
			'data' => $this->_data,
		);
	}


}