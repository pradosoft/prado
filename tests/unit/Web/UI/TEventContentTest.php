<?php

use Prado\Web\UI\TEventContent;



class TEventContentTest extends PHPUnit\Framework\TestCase
{
	protected $obj;
	
	protected $_call;

	protected function setUp(): void
	{
		$this->obj = new TEventContent();
	}

	protected function tearDown(): void
	{
		$this->obj = null;
	}

	public function testConstruct()
	{
		self::assertInstanceOf(TEventContent::class, $this->obj);
	}

	public function testBroadcastEvent()
	{
		self::assertEquals('', $this->obj->getBroadcastEvent());
		$this->obj->setBroadcastEvent('fxTestContentEvent');
		self::assertEquals('fxTestContentEvent', $this->obj->getBroadcastEvent());
	}
	
	public function callHandler($sender, $param)
	{
		$this->_call = $param->getEventName();
		$param->getParameter()[0] = 'value';
	}
	
	public function testCreateChildControls()
	{
		$this->obj->setBroadcastEvent('fxTestContentEvent');
		Prado::getApplication()->fxTestContentEvent[] = [$this, 'callHandler'];
		$this->obj->createChildControls();
		
		self::assertEquals('fxtestcontentevent', $this->_call);
		self::assertEquals('value', $this->obj->getControls()[0]);
		
		Prado::getApplication()->detachEventHandler('fxTestContentEvent', [$this, 'callHandler']);
	}
}
