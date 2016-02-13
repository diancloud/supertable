<?php
/**
 * Type BaseDate
 *
 * CLASS 
 *
 * 	   BaseDate 
 *
 * USEAGE: 
 *
 *   $table = new YourSuperTable(...);
 *   
 *   $TypeNested = $table->type('BaseDate');
 *
 * 	 参数表:
 * 	 
 *   $TypeNested = $table->type('BaseDate',[
 *   	'screen_name' => '团队',  // 字段显示名称 ( 默认生成 BaseObject_198232381 字符串 ) 
 *   	'schema' => [
 *   		'name'=> [
 *   			'screen_name'=> '团队名称',
 *   			'input_type' => 'text',
 *   			'input_width' => 6,
 *   			'input_value'=> '',
 *   			'placeholder'=> '请输入团队名称',
 *   			'searchable' => true,
 *   			'validation' => [
 *   				'required' => true,
 *   				'type' => 'string',
 *   			],
 *   			'message' => [
 *   				'required' => '团队名称未填写',
 *   				'type' => '团队名称格式错误',
 *   			]
 *   		],
 *   		'type'=> [
 *   			'screen_name'=> '团队类型',
 *   			'input_type' => 'select',
 *   			'searchable' => true,
 *      		'input_width' => 6,
 *   			'placeholder'=> '请选择团队类型',
 *   			'input_option'=> [
 *   				'private'=>['name'=>'私有', 'selected'=>false, 'value'=>'private'],
 *   				'public'=>['name'=>'公有', 'selected'=>true, 'value'=>'public']
 *   			],
 *   			'validation' => [
 *   				'required' => true,
 *   				'allow' => ['private','public'],
 *   			],
 *   			'message' => [
 *   				'required' => '团队名称未填写',
 *   				'type' => '团队名称格式错误',
 *   			]
 *   		],
 *   		'desp'=> [
 *   			'validation' => [
 *   				'required' => true,
 *   				'type' => 'string',
 *   			],
 *   			'message' => [
 *   				'required' => '团队介绍未填写',
 *   				'type' => '团队介绍格式错误',
 *   			]
 *   		]
 *   	],  // 类型结构体 validation rule
 *   	
 *   	'default' => [['name'=>'团队猫', 'type'=>'private', 'desp'=>'团队猫，喵喵喵']],  // 字段默认数值  默认为空
 * 		'placeholder' => '请填写数量', // 字段 PlaceHolder 默认值 SuperTable BaseNested ( Object Array )
 * 		'required' => 1,  // 作为必填字段 1 必填 0 非必填 默认 0 
 * 		'searchable' => 0,  // 作为搜索条件 1 搜索 0 不搜素 默认 1
 * 		'summary' => 1,  // 作为摘要数据 1 作为摘要数据 0 不作为摘要数据 默认 0 
 * 		'order' => 1,  // 字段再结构列表中排序 （ hidden=0 时候生效， 数值越大越靠前) 默认为1
 * 		'hidden' => 0,  // 是否在表单中隐藏该字段 1 隐藏 0 不隐藏  默认为0
 * 		'hidden_column' => 1,  // 是否在表单中隐藏该字段 1 隐藏 0 不隐藏  默认为1
 * 		'hidden_data' => 1,  // 是否在表单中隐藏该字段 1 隐藏 0 不隐藏  默认为1
 * 		'hidden' => 0,  // 是否在表单中隐藏该字段 1 隐藏 0 不隐藏  默认为0
 *   ]);
 */

Namespace Tuanduimao\Supertable\Types;
use Tuanduimao\Supertable\Type;
use Tuanduimao\Supertable\Validation;
use \Exception as Exception;

class BaseDate extends Type {

	function __construct( $data = array(), $option=array() ) {
		
		$opts = array_merge($data, $option );

		// FORMINPUT DATA
		$opts['default'] = (isset($opts['default']))? $opts['default'] : null;
		$opts['placeholder'] = (isset($opts['placeholder']))? $opts['placeholder'] : 'SuperTable BaseDate';


		// OPTINONS 
		$opts['screen_name'] = (isset($opts['screen_name']))? $opts['screen_name'] : 'BaseObject_' . time() . rand(100000,999999);
		$opts['required'] = (isset($opts['required']))? $opts['required'] : 0;
		$opts['searchable'] = (isset($opts['searchable']))? $opts['searchable'] : 1;
		$opts['summary'] = (isset($opts['summary']))? $opts['summary'] : 0;
		$opts['unique'] = (isset($opts['unique']))? $opts['unique'] : 0;
		$opts['order'] = (isset($opts['order']))? $opts['order'] : 1;
		$opts['hidden'] = (isset($opts['hidden']))? $opts['hidden'] : 0;
		$opts['hidden_column'] = (isset($opts['hidden_column']))? $opts['hidden_column'] : 1;
		$opts['hidden_data'] = (isset($opts['hidden_data']))? $opts['hidden_data'] : 1;
		$opts['dropable'] = (isset($opts['dropable']))? $opts['dropable'] : 0; // 能否移除 默认为0 不可移除
		$opts['alterable'] = (isset($opts['alterable']))? $opts['alterable'] : 0; // 能否移除 默认为0 不可移除
		$opts['column_name'] = (isset($opts['column_name']))? $opts['column_name'] : "";
		$opts['width'] = (isset($opts['width']))? $opts['width'] : 12;

		$option = array_merge([
			'screen_name' => $opts['screen_name'] ,
		 	'required' => $opts['required'],
		 	'summary' => $opts['summary'],
		 	'searchable' => $opts['searchable'],
		 	'unique' => $opts['unique'],
		 	'order' => $opts['order'],
		 	'hidden' => $opts['hidden'],
		 	'width' =>  $opts['width'],
		 	'hidden_column' => $opts['hidden_column'],
		 	'hidden_data' => $opts['hidden_data'],
		 	'dropable' => $opts['dropable'],
		 	'alterable'=> $opts['alterable'],
		 	'column_name' => $opts['column_name'],
		], $opts);

		$data = [
			'screen_name' => $opts['screen_name'],
			'default' => $opts['default'],
			'placeholder' => $opts['placeholder'],
		];

		// 错误提示
		$data_message = [
			'required' =>  '请填写{screen_name}',
			'type' => '{screen_name}数据格式不正确',
		];

		parent::__construct( $data, $option );
		$this->setDataInput([]);
		$this->setDataMessage( $data_message );
		$this->setDataFormat('date');
	}



	/**
	 * 重载数值解码函数
	 */
	public function valueDecode( $value ) {
		if ( $value == null) return null;
		return str_replace('T', ' ', $value );
	}

	/**
	 * 重载数值编码函数
	 */
	public function valueEncode( $value ) {
		if ( $value == null) return null;
		return str_replace(' ', 'T', $value );
	}


	/**
	 * 重载数据校验函数
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function validation( & $value ) {
		
		$this->cleanError();
		// $value = $this->valueEncode( $value );
		// echo "Value == $value \n";
		$name = $this->_data['screen_name'];
		$name = ( $name != "" ) ? $name : $this->_option['screen_name'];
		$field_name =  ( $this->option('column_name') == null ) ? $name : $this->option('column_name');

		if ( preg_match_all('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/', $value, $matchs ) ) {
			return true;
		}

		$rule = [
			"$name" => [
				'validation' => [
					'required' => $this->_option['required'],
					'match' => '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/',
				],
				'message' => [
					'required' => $this->_message('required', ['screen_name'=>$name]),
					'match' => $this->_message('type', ['screen_name'=>$name]),
				],

				'field_name' => $this->option('column_name')
			],
		];

		$v = new Validation();
		$valueCheck[$name] = $value;
		if ( $v->check($valueCheck, $rule, $errlist ) === false ) {
			$this->errors = array_merge( $this->errors, $errlist );
			return false;
		}

		return true;

		/*

		$this->errors = array_merge( $this->errors, ["$name"=>[
				'message'=>'日期格式不正确',
				'method'=>'validation',
				'format'=>'^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$',
				'field' => $field_name,
				'name'=> $name,
				'value' => $value
			]
		]); */

		return false;

	}

}