<?php

namespace Prado\Test\Unit\I18N\core;

use PHPUnit\Framework\TestCase;
use Prado\I18N\core\TIntlDateFormatterTrait;
use Prado\Test\Unit\PradoUnit;

/**
 * A user of the trait, with its own formatter cache.
 */
class TIntlDateFormatterTraitFixture
{
	use TIntlDateFormatterTrait;
}

class TIntlDateFormatterTraitTest extends TestCase
{
	private array $_snapshot;

	protected function setUp(): void
	{
		$this->_snapshot = PradoUnit::snapshotStatic(TIntlDateFormatterTraitFixture::class, ['formatters']);
	}

	protected function tearDown(): void
	{
		PradoUnit::restoreStatic(TIntlDateFormatterTraitFixture::class, $this->_snapshot);
	}

	private function formatter(...$args): \IntlDateFormatter
	{
		return PradoUnit::invoke(new TIntlDateFormatterTraitFixture(), 'getIntlDateFormatter', ...$args);
	}

	public function testFormatterIsCachedPerCultureAndTypes()
	{
		$formatter = $this->formatter('en_US', \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);

		$this->assertSame('en_US', $formatter->getLocale(\Locale::VALID_LOCALE));
		$this->assertSame(\IntlDateFormatter::SHORT, $formatter->getDateType());
		$this->assertSame(\IntlDateFormatter::NONE, $formatter->getTimeType());
		$this->assertSame($formatter, $this->formatter('en_US', \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE));
		$this->assertNotSame($formatter, $this->formatter('fr_FR', \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE));
		$this->assertNotSame($formatter, $this->formatter('en_US', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE));
	}

	public function testFormatterWithAPatternIsCachedPerPattern()
	{
		$formatter = $this->formatter('en_US', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, 'yyyy-MM-dd');

		$this->assertSame('yyyy-MM-dd', $formatter->getPattern());
		$this->assertSame($formatter, $this->formatter('en_US', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, 'yyyy-MM-dd'));
		$this->assertSame('dd.MM.yyyy', $this->formatter('en_US', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, 'dd.MM.yyyy')->getPattern());
		$this->assertNotSame($formatter, $this->formatter('en_US', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL));
	}
}
