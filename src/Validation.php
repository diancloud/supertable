<?php

namespace Tuanduimao\Supertable;


/**
 * 数据校验函数清单
 * 
 */

class Validation {
	
	function __construct() {
	}

	public function digits( & $value, $format ){
		$value = "$value";
		if ( !is_numeric($value)) return false;
		return true;
	}

	public function min( & $value , $format ) {
		if ( !is_numeric("$value") ) {
			return false;
		}

		if ( floatval($value) < floatval($format) ) {
			return false;
		}

		return true;
	}

	public function max( & $value , $format ) {
		if ( !is_numeric("$value") ) {
			return false;
		}

		if ( floatval($value) > floatval($format) ) {
			return false;
		}
		return true;
	}


	public  function minlength( & $value, $format ) {
		if ( !is_string($value)) return false;
		if ( strlen($value) < $format ) {
			return false;
		}
		return true;
	}

	public  function maxlength( & $value, $format ) {
		if ( !is_string($value)) return false;
		if ( strlen($value) > $format ) {
			return false;
		}
		return true;
	}

	public  function minwlength( & $value, $format ) {
			
		if ( !is_string($value)) return false;
		if ( mb_strlen($value,'utf-8') < $format ) {
			return false;
		}
		return true;
	}

	public  function maxwlength( & $value, $format ) {
		if ( !is_string($value)) return false;
		if ( mb_strlen($value,'utf-8') > $format ) {
			return false;
		}
		return true;
	}


	public  function password(  & $value, $format ) {		
		if ( !preg_match('/^[a-z0-9A-Zu0000-u00FF]{6,10}$/', $value) ) {
			return false;
		}
		$value = md5($value);
		return true;
	}


	public  function allow( & $value, $format ) {
		
		foreach ($format as $val ) {
			if ( $value == $val ) {
				return true;
			}
		}
		return false;
	}

	public  function email( & $value, $format ) {
		
		if ( !preg_match('/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/', $value) ) {
			return false;
		}

		return true;
	}

	public function url( & $value, $format ){
		if ( filter_var($value, FILTER_VALIDATE_URL) === false ) {
			return false;
		}
		return true;
	}


	public  function mobile( & $value, $format ) {
		if ( !preg_match('/^[0-9]{11}$/', $value) ) {
			return false;
		}
		return true;
	}
}