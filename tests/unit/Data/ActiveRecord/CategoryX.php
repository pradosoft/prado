<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

/**
 *
 * CREATE TABLE CategoryX (
 * cat_id integer PRIMARY KEY AUTOINCREMENT,
 * category_name varchar,
 * parent_cat varchar,
 * parent_category integer CONSTRAINT fk_id1 REFERENCES CategoryX(cat_id) ON DELETE CASCADE,
 * child_categories integer CONSTRAINT fk_id2 REFERENCES CategoryX(cat_id) ON DELETE CASCADE
 * )
 */
class CategoryX extends MultipleFKSqliteRecord
{
	public $cat_id;
	public $category_name;
	public $parent_cat;

	public $parent_category;
	public $child_categories = [];

	public static $RELATIONS = [
		'parent_category' => [self::BELONGS_TO, CategoryX::class],
		'child_categories' => [self::HAS_MANY, CategoryX::class],
	];

	public static function finder($class = __CLASS__)
	{
		return parent::finder($class);
	}
}
