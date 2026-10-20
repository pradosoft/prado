<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TNotSupportedException;
use Prado\TEventParameter;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\IDataSource;
use Prado\Web\UI\WebControls\TDataSourceControl;

/**
 * Concrete TDataSourceControl that keeps the base class behavior.
 */
class TDataSourceControlTestSource extends TDataSourceControl
{
}

/**
 * Plain naming container used to host a data source control.
 */
class TDataSourceControlTestContainer extends TControl implements INamingContainer
{
}

class TDataSourceControlTest extends TestCase
{
	private TDataSourceControlTestSource $source;

	protected function setUp(): void
	{
		$this->source = new TDataSourceControlTestSource();
	}

	public function testIsDataSourceControl(): void
	{
		$this->assertInstanceOf(IDataSource::class, $this->source);
		$this->assertInstanceOf(TControl::class, $this->source);
	}

	public function testGetViewReturnsNull(): void
	{
		$this->assertNull($this->source->getView(''));
		$this->assertNull($this->source->getView('anyView'));
	}

	public function testGetViewNamesIsEmpty(): void
	{
		$this->assertSame([], $this->source->getViewNames());
	}

	public function testOnDataSourceChangedRaisesEvent(): void
	{
		$received = [];
		$this->source->attachEventHandler('OnDataSourceChanged', function ($sender, $param) use (&$received) {
			$received[] = [$sender, $param];
		});
		$param = new TEventParameter();
		$this->source->onDataSourceChanged($param);
		$this->assertCount(1, $received);
		$this->assertSame($this->source, $received[0][0]);
		$this->assertSame($param, $received[0][1]);
	}

	public function testOnDataSourceChangedWithoutHandlers(): void
	{
		$this->source->onDataSourceChanged(null);
		$this->assertFalse($this->source->hasEventHandler('OnDataSourceChanged'));
	}

	public function testFocusThrows(): void
	{
		try {
			$this->source->focus();
			$this->fail('focus() must throw TNotSupportedException.');
		} catch (TNotSupportedException $e) {
			$this->assertSame('datasourcecontrol_focus_unsupported', $e->getErrorCode());
			$this->assertSame($this->source::class . ' does not support Focus.', $e->getMessage());
		}
	}

	public function testEnableThemingIsFalse(): void
	{
		$this->assertFalse($this->source->getEnableTheming());
	}

	public function testSetEnableThemingThrows(): void
	{
		try {
			$this->source->setEnableTheming(true);
			$this->fail('setEnableTheming() must throw TNotSupportedException.');
		} catch (TNotSupportedException $e) {
			$this->assertSame('datasourcecontrol_enabletheming_unsupported', $e->getErrorCode());
		}
		$this->assertFalse($this->source->getEnableTheming());
	}

	public function testSkinIDIsEmpty(): void
	{
		$this->assertSame('', $this->source->getSkinID());
	}

	public function testSetSkinIDThrows(): void
	{
		try {
			$this->source->setSkinID('mySkin');
			$this->fail('setSkinID() must throw TNotSupportedException.');
		} catch (TNotSupportedException $e) {
			$this->assertSame('datasourcecontrol_skinid_unsupported', $e->getErrorCode());
		}
		$this->assertSame('', $this->source->getSkinID());
	}

	public function testVisibleIsFalse(): void
	{
		$this->assertFalse($this->source->getVisible());
		$this->assertFalse($this->source->getVisible(false));
	}

	public function testSetVisibleThrows(): void
	{
		try {
			$this->source->setVisible(true);
			$this->fail('setVisible() must throw TNotSupportedException.');
		} catch (TNotSupportedException $e) {
			$this->assertSame('datasourcecontrol_visible_unsupported', $e->getErrorCode());
		}
		$this->assertFalse($this->source->getVisible());
	}

	public function testPropertyAccessUsesOverrides(): void
	{
		$this->assertFalse($this->source->Visible);
		$this->assertFalse($this->source->EnableTheming);
		$this->assertSame('', $this->source->SkinID);
		$this->expectException(TNotSupportedException::class);
		$this->source->Visible = false;
	}

	public function testFoundByIdInNamingContainer(): void
	{
		$container = new TDataSourceControlTestContainer();
		$container->setID('container');
		$this->source->setID('source');
		$container->getControls()->add($this->source);

		$this->assertSame($this->source, $container->findControl('source'));
		$this->assertSame($container, $this->source->getNamingContainer());
	}

	public function testHiddenInsideVisibleParent(): void
	{
		$container = new TDataSourceControlTestContainer();
		$container->getControls()->add($this->source);

		$this->assertTrue($container->getVisible());
		$this->assertFalse($this->source->getVisible());
	}
}
