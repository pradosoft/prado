<?php

namespace Prado\Test\Unit\Data\SqlMap;

use Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandler;

class CustomInheritance extends TSqlMapTypeHandler
{
	public function getResult($type)
	{
		switch ($type) {
			case 'Monograph': case 'Book':
				return 'Book';
			case 'Tabloid': case 'Broadsheet': case 'Newspaper':
				return 'Newspaper';
			default:
				return 'Document';
		}
	}

	public function getParameter($parameter)
	{
		throw new \TDataMapperException('not implemented');
	}

	public function createNewInstance($data = null)
	{
		throw new \TDataMapperException('can not create');
	}
}
