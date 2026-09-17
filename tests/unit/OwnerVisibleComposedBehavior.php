<?php

namespace Prado\Test\Unit;


class OwnerVisibleComposedBehavior extends OwnerVisibleMethodsBehavior
{
	public function anotherVisibleMethod()
	{
		return 'another';
	}
	public function getOwnerVisibleMethods(): null|string|array
	{
		return array_merge((array) parent::getOwnerVisibleMethods(), ['anotherVisibleMethod']);
	}
}
