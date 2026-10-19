<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\TComponent;
use Prado\Web\UI\WebControls\TDataSourceSelectParameters;

class TDataSourceSelectParametersTest extends TestCase
{
	private TDataSourceSelectParameters $params;

	protected function setUp(): void
	{
		$this->params = new TDataSourceSelectParameters();
	}

	public function testIsComponent(): void
	{
		$this->assertInstanceOf(TComponent::class, $this->params);
	}

	public function testDefaults(): void
	{
		$this->assertSame(0, $this->params->getStartRowIndex());
		$this->assertSame(0, $this->params->getMaximumRows());
		$this->assertFalse($this->params->getRetrieveTotalRowCount());
		$this->assertSame(0, $this->params->getTotalRowCount());
	}

	public function testStartRowIndex(): void
	{
		$this->params->setStartRowIndex(5);
		$this->assertSame(5, $this->params->getStartRowIndex());
		$this->params->setStartRowIndex('12');
		$this->assertSame(12, $this->params->getStartRowIndex());
		$this->params->setStartRowIndex(0);
		$this->assertSame(0, $this->params->getStartRowIndex());
	}

	public function testStartRowIndexNegativeClampsToZero(): void
	{
		$this->params->setStartRowIndex(7);
		$this->params->setStartRowIndex(-3);
		$this->assertSame(0, $this->params->getStartRowIndex());
	}

	public function testMaximumRows(): void
	{
		$this->params->setMaximumRows(20);
		$this->assertSame(20, $this->params->getMaximumRows());
		$this->params->setMaximumRows('8');
		$this->assertSame(8, $this->params->getMaximumRows());
	}

	public function testMaximumRowsNegativeClampsToZero(): void
	{
		$this->params->setMaximumRows(10);
		$this->params->setMaximumRows(-1);
		$this->assertSame(0, $this->params->getMaximumRows());
	}

	public function testRetrieveTotalRowCount(): void
	{
		$this->params->setRetrieveTotalRowCount(true);
		$this->assertTrue($this->params->getRetrieveTotalRowCount());
		$this->params->setRetrieveTotalRowCount('false');
		$this->assertFalse($this->params->getRetrieveTotalRowCount());
		$this->params->setRetrieveTotalRowCount('true');
		$this->assertTrue($this->params->getRetrieveTotalRowCount());
	}

	public function testTotalRowCount(): void
	{
		$this->params->setTotalRowCount(100);
		$this->assertSame(100, $this->params->getTotalRowCount());
		$this->params->setTotalRowCount('42');
		$this->assertSame(42, $this->params->getTotalRowCount());
	}

	public function testTotalRowCountNegativeClampsToZero(): void
	{
		$this->params->setTotalRowCount(9);
		$this->params->setTotalRowCount(-50);
		$this->assertSame(0, $this->params->getTotalRowCount());
	}

	public function testPropertyAccess(): void
	{
		$this->params->StartRowIndex = 3;
		$this->params->MaximumRows = 4;
		$this->params->RetrieveTotalRowCount = true;
		$this->params->TotalRowCount = 5;
		$this->assertSame(3, $this->params->StartRowIndex);
		$this->assertSame(4, $this->params->MaximumRows);
		$this->assertTrue($this->params->RetrieveTotalRowCount);
		$this->assertSame(5, $this->params->TotalRowCount);
	}
}
