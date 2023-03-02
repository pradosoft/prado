<?php

/**
 * TUSkinTemplateTest for testing TSkinTemplate
 * the reason for the odd naming is that it needs to be after TTemplateTest to extend the class.
 */

class TUSkinTemplateTest extends TTemplateTest
{
	protected function getTestClass()
	{
		return 'Prado\\Web\\UI\\TSkinTemplate';
	}
	
	public function testConstruct()
	{
		self::assertFalse($this->obj->getAttributeValidation());
	}
}
