<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\TStatements;

class TStatementsTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testStatementsDefaultToEmptyAndRenderNothing()
	{
		$control = new TStatements();
		$this->assertSame('', $control->getStatements());
		$this->assertSame('', $this->render($control));
	}

	public function testRendersTheOutputOfTheStatements()
	{
		$control = new TStatements();
		$control->setStatements('$a = 2; echo $a * 3, "!";');
		$this->assertSame('$a = 2; echo $a * 3, "!";', $control->getStatements());
		$this->assertSame('6!', $this->render($control));
	}
}
