<?php

namespace Tuanduimao\Supertable;


/**
 * 数据校验函数清单
 * 
 */

class Validation {

	function __construct() {
	}

	/**
	 * 数据校验
	 * @param  [type] $data   待校验数据 array()
	 * @param  [type] $rule   校验规则
	 * @param  [type] $errors 错误返回
	 * @param  [type] $parent 父字段名称 (用于递归查询)
	 * @return bool 成功返回 true, 失败返回 false
	 */
	public static function check( $data, $rule, & $errors, $parent=null ) {
		$errors = array();
		$check = new \Tuanduimao\Supertable\Validation();
		if ( !is_array($data) ) {
			$errors[] = [
				"message"=>'待验证数据格式不正确', 
				'name'=>'data', 'method'=>'check', 'format'=>'array', 
				'type'=>self::getType($data), 
				'value'=>$data 
			];
			return false;
		}


		foreach ($rule as $field => $ru ) {

			if ( isset($ru['_LIST']) ) { // 数据是JSON格式，递归调用
				self::check( $data[$field], $ru['_LIST'], $child_errors, $field );
				$errors = array_merge($errors, $child_errors );
				continue;
			}

			if ( !isset($ru['validation']) ) {  //未填写验证选项，跳过
				continue;
			}

			$validationList = $ru['validation'];
			$void = null;

			// 读取数值和字段名
			$value = &$void;
			if ( isset($data[$field]) ) {
				$value = &$data[$field];
			}

			if ( $parent != null ) {
				$name = "$parent.$field";
				$error = &$errors[$parent];
				$field_name = $parent;

			} else {
				$name = $field;
				$error = &$errors[$field];
				$field_name = $field;
			}

			// 是否有字段名称
			if ( isset($ru['field_name']) && $ru['field_name'] != null  ) {
				$field_name = $ru['field_name'];
			}


			// 验证必填字段
			$required = (isset($validationList['required']))? $validationList['required'] : false;
			$message = (isset($ru['message']))?$ru['message']:"$name 未填写";
			if ( is_array($message) ) {
				$message = ( isset($message['required'])) ? $message['required']: "$name 未填写";
			}

			if ( $required && $value == null ) { // 必填字段，未填写数值 (报错， 忽略后续验证)
				$error[] = array(
					"message"=>$message, 
					'method'=>'required', 
					'format'=>true, 
					'field' => $field_name,
					'name'=>$name,
					'value'=>null,
				);
				continue;
			} else if ( !$required  && $value == null ) {  // 非必填字段，未填写数值 (忽略后续验证)
				unset($errors[$field_name]);
				continue;
			}

			if (isset($validationList['required'])) {
				unset($validationList['required']);
			}

			// 验证数据格式
			foreach ($validationList as $method => $format ) {
				$message = (isset($ru['message']))?$ru['message']:"$name 数据格式不正确";
				if ( is_array($message) ) {
					$message = ( isset($message[$method])) ? $message[$method]: "$name 数据格式不正确";
				}
				if ( !method_exists($check, $method) ) {
					$error[] = [
						"message"=>"校验方法 ( Validation::$method ) 不存在", 
						'method'=>$method, 
						'format'=>$format, 
						'field' => $field_name,
						'name'=>$name,
						'value'=>$value,
					];
					continue;
				}

				if ( $check->$method( $value, $format ) === false ) {
					$error[] = [
						"message"=>$message, 
						'method'=>$method, 
						'format'=>$format, 
						'field' => $field_name,
						'name'=>$name,
						'value'=>$value,
					];
				}
			}

			if ( count($error) == 0 ) {
				unset($errors[$field_name]);
			}
		}

		if ( count($errors) == 0 ) {
			return true;
		}

		return false;
	}

	public static function getType($var) {

        if (is_array($var)) return "array";
        if (is_bool($var)) return "boolean";
        if (is_float($var)) return "float";
        if (is_int($var)) return "integer";
        if (is_null($var)) return "NULL";
        if (is_numeric($var)) return "numeric";
        if (is_object($var)) return "object";
        if (is_resource($var)) return "resource";
        if (is_string($var)) return "string";
        return "unknown type";
    }



    public function allowMatchArray( & $value, $format ) {
    	if ( !is_array($value) ) {
    		return false;
    	}

    	$ret = true;
    	foreach ($value as $val) {
    		$curr = false;
	    	foreach ($format as $reg ) {
	    		if ( @preg_match($reg, $val, $match) ) {
	    			$curr = true;
	    			break;
	    		}
	    	}
	    	$ret = $curr;
    	}
    	return $ret;
    }

    public function match( & $value, $format ) {
    	if ( @preg_match($format, $value, $match) ) {
	    	return true;
	    }
	    return false;
    }


    public function type( & $value, $format ) {
    	
    	$arr = (is_array($format))? $format : array($format);
    	foreach ($arr as $fmt ) {
    		if ( self::getType($value) == $fmt ) {
    			return true;
    		}
    	}
    	return false;
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