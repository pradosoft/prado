<?php

class ItemRecord extends TActiveRecord
{
	const TABLE = 'items';
	public $item_id;
	public $name;
	public $brand_specific;
	public $description;
	public $meta;
	public $active;
	public $need_review;
	public $category_id;
	public $type_id;
	public $content;
	public $standard_id;
	public $timestamp;

	public $related_items = [];
	public $related_item_id;

	public static $RELATIONS = [
		'related_items' => [self::MANY_TO_MANY, 'ItemRecord', 'related_items.related_item_id'],
	];

	public function getDbConnection()
	{
		static $conn;
		if ($conn === null) {
			$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest', 'prado_unitest');
			$this->OnExecuteCommand[] = [$this, 'logger'];
		}
		return $conn;
	}

	public function logger($sender, $param)
	{
		//var_dump($param->Command->Text);
	}

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
