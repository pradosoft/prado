<?php

namespace Prado\Test\Unit\I18N\core;

use PHPUnit\Framework\TestCase;
use Prado\I18N\core\TNumberFormatterTrait;
use Prado\Test\Unit\PradoUnit;

/**
 * A user of the trait, with its own formatter cache.
 */
class TNumberFormatterTraitFixture
{
	use TNumberFormatterTrait;
}

class TNumberFormatterTraitTest extends TestCase
{
	private array $_snapshot;

	protected function setUp(): void
	{
		$this->_snapshot = PradoUnit::snapshotStatic(TNumberFormatterTraitFixture::class, ['formatters']);
	}

	protected function tearDown(): void
	{
		PradoUnit::restoreStatic(TNumberFormatterTraitFixture::class, $this->_snapshot);
	}

	public function testFormatterIsCachedPerCultureAndFormat()
	{
		$fixture = new TNumberFormatterTraitFixture();

		$formatter = PradoUnit::invoke($fixture, 'getFormatter', 'de_DE', \NumberFormatter::DECIMAL);

		$this->assertSame('1.234,5', $formatter->format(1234.5));
		$this->assertSame($formatter, PradoUnit::invoke($fixture, 'getFormatter', 'de_DE', \NumberFormatter::DECIMAL));
		$percent = PradoUnit::invoke($fixture, 'getFormatter', 'de_DE', \NumberFormatter::PERCENT);
		$this->assertNotSame($formatter, $percent);
		$this->assertCount(2, PradoUnit::getProp(TNumberFormatterTraitFixture::class, 'formatters')['de_DE']);
	}
}
