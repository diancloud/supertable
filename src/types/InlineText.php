<?php
/**
 * Type InlineText
 *
 * CLASS 
 *
 * 	   InlineText 
 *
 * USEAGE: 
 *
 * 
 */

Namespace Tuanduimao\Supertable\Types;
use Tuanduimao\Supertable\Type;
use Tuanduimao\Supertable\Validation;
use \Exception as Exception;

class InlineText extends Type {
	
	function __construct( $data=array(), $option=array() ) {
		
		$data_input = array(
			'maxlength' => array(
				'screen_name' =>  '最大字数',
				'placeholder' => '最多可以输入字数',
				'input_type' => 'text',
				'default' => 50,
				'validation' => array(
					'required' => false,
					'digits'=>true,
					'min'=>1,
					'max'=>50,
				),

				'message' => array(
					'digits' => '{screen_name}({name})格式不正确，请输入数字',
					'min' => '{screen_name}({name})数值不能少于{value}',
					'max' => '{screen_name}({name})数值不能大于{value}',
				),
			),

			'minlength' => array(
				'screen_name' =>  '最小字数',
				'placeholder' => '至少输入字数',
				'input_type' => 'text',
				'default' => 1,
				'validation' => array(
					'required' => false,
					'digits'=>true,
					'min'=>1,
					'max'=>50,
				),

				'message' => array(
					'digits' => '{screen_name}({name})格式不正确，请输入数字',
					'min' => '{screen_name}({name})数值不能少于{value}',
					'max' => '{screen_name}({name})数值不能大于{value}',
				),
			),

			'default' => array(
				'screen_name' =>  '默认值',
				'placeholder' =>  '填写该字段的默认值',
				'input_type' => 'text',
				'validation' => array(
					'required' => false,
					'minwlength'=>'{minlength}.value',
					'maxwlength'=>'{maxlength}.value',
				),

				'message' => array(
					'maxwlength' => '{screen_name}({name})不能超过{value}个字',
					'minwlength' => '{screen_name}({name})至少输入{value}个字',
				),
			),

			'placeholder' => array(
				'screen_name' =>  '填写提示',
				'placeholder' =>  '字段格式的提示信息',
				'input_type' => 'text',
				'validation' => array(
					'required' => false,
					'minlength'=>1,
					'maxlength'=>50,
				),

				'message' => array(
					'minlength' => '{screen_name}({name})不能超过{value}个字',
					'maxlength' => '{screen_name}({name})至少输入{value}个字',
				),
			),
		);

		$data_message = array(
			'required' =>  '请填写{screen_name}',
			'maxlength' => '{screen_name}不能超过{maxlength}个字',
			'minlength' => '{screen_name}至少{minlength}个字',
		);

		parent::__construct( $data, $option );
		$this->setDataInput( $data_input );
		$this->setDataMessage( $data_message );
	
	}


	/**
	 * 重载数据校验函数
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function validation( & $value ) {
		
		$this->cleanError();
		

		$value = strval($value);
		$errflag = false;

		// 校验是否必填
		if( $this->_option['required'] && ($value === null  || $value === "") ) {
			$message = $this->_message('required', array('screen_name'=>$this->_option['screen_name']) );
			array_push($this->errors, array('required'=>$message) );
			return false;
		}

		$check = new Validation();

		// 校验最小值
		if ( !$check->minwlength($value, $this->_data['minlength']) ) {
			$message = $this->_message('minlength', array(
				'screen_name'=>$this->_option['screen_name'],
				'minlength'=>$this->_data['minlength'],
			));
			array_push($this->errors, array('minlength'=>$message) );
			$errflag = true;
		}

		// 校验最大值
		if ( !$check->maxwlength($value, $this->_data['maxlength']) ) {
			$message = $this->_message('maxlength', array(
				'screen_name'=>$this->_option['screen_name'],
				'maxlength'=>$this->_data['maxlength'],
			));
			array_push($this->errors, array('maxlength'=>$message) );
			$errflag = true;
		}

		return !$errflag;
	}

}