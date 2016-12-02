<?php

namespace wulaphp\db;

use wulaphp\util\Annotation;

/**
 * 声明了字段的数据表.
 *
 * @package wulaphp\db
 */
abstract class FormTable extends Table {
	/**
	 * 通过public定义的字段.
	 *
	 * @var array
	 */
	private static $fields = false;

	public function __construct($db = null) {
		if (self::$fields === false) {
			$this->parseFields();
		}
		parent::__construct($db);
	}

	/**
	 * 根据定义的字段从请求中填充字段值.
	 *
	 * @param string|bool $excepts    不填充的字段,多个字段以逗号分隔.当其值为true时，$useDefault=true。
	 * @param bool        $useDefault 是否使用默认值填充数据.
	 *
	 * @return array 填充后的数组.
	 */
	public function inflate($excepts = '', $useDefault = false) {
		$data = [];
		if ($excepts === true) {
			$useDefault = true;
		} else {
			$excepts = $excepts ? explode(',', $excepts) : [];
		}
		foreach (self::$fields as $key => $v) {
			if (in_array($key, $excepts)) {
				continue;
			}
			$name = $v['name'];
			if (rqset($name)) {
				$value                  = rqst($name);
				$data[ $key ]           = $value;
				$this->{$v['property']} = $value;
			} elseif ($useDefault && isset($v['default'])) {
				$value        = $v['default'];
				$data[ $key ] = $value;
			}
		}

		return $data;
	}

	/**
	 * 过滤数据.
	 *
	 * @param array $data 要过滤的数据.
	 */
	protected function filterFields(&$data) {
		$keys = array_keys($data);
		foreach ($keys as $key) {
			if (!isset(self::$fields[ $key ]) || self::$fields[ $key ]['skip']) {
				unset($data[ $key ]);
			}
		}
	}

	/**
	 * 解析字段.可解析以下注解:
	 * 1. var 数据类型, int,string,bool,array,json等.
	 * 2. skip 不更新到数据表
	 * 3. name 表单里的字段名,如果不指定则与属性同名.
	 * 4. 验证注解，见Validator.
	 * 5. 第三方插件支持的注解.
	 */
	private function parseFields() {
		self::$fields = [];
		$refobj       = new \ReflectionObject($this);
		$fields       = $refobj->getProperties(\ReflectionProperty::IS_PUBLIC);
		foreach ($fields as $field) {
			$fname = $field->getName();
			if (preg_match('/^field_(.+)$/', $fname, $ms)) {
				$ann                    = new Annotation($field);
				self::$fields[ $ms[1] ] = ['annotation' => $ann, 'property' => $fname, 'var' => $ann->getString('var', 'string'), 'skip' => $ann->has('skip'), 'name' => $ann->getString('name', $ms[1]), 'default' => $field->getValue($this)];
			}
		}
	}
}