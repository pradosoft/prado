<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\TMultibyteString;
use Prado\Web\UI\WebControls\TKeyShortcuts;

class TKeyShortcutsTest extends TestCase
{
	public function testNormalizeOrdersModifiersAndUppercasesLetters()
	{
		$this->assertSame('Control+S Meta+S', TKeyShortcuts::normalize('ctrl+s cmd+s'));
		$this->assertSame('Alt+Shift+P', TKeyShortcuts::normalize('shift+alt+p'));
		$this->assertSame('Control+Alt+Shift+Meta+K', TKeyShortcuts::normalize('Meta+Shift+Option+Control+k'));
		$this->assertSame('Alt+Meta+K', TKeyShortcuts::normalize('Command+Alt+K'));
		$this->assertSame('?', TKeyShortcuts::normalize("  ?\t"));
	}

	public function testNormalizeNamedKeysAndAliases()
	{
		$this->assertSame(
			'Escape Enter Space Plus Delete Insert ArrowUp ArrowDown ArrowLeft ArrowRight',
			TKeyShortcuts::normalize('esc return spacebar plus del ins up down left right')
		);
		$this->assertSame(
			'F1 F24 PageUp PageDown Home End Tab Backspace',
			TKeyShortcuts::normalize('f1 F24 pageup PAGEDOWN home end tab backspace')
		);
	}

	public function testNormalizeRemovesRepeatsAndKeepsOrder()
	{
		$this->assertSame('Control+S Meta+S', TKeyShortcuts::normalize('Control+S meta+s control+s'));
	}

	public function testNormalizeKeepsLetterWithMultiCharacterUppercase()
	{
		$this->assertSame('ß É', TKeyShortcuts::normalize('ß é'));
	}

	public function testNormalizeWithoutMbstring()
	{
		$newline = PradoUnit::invoke(TKeyShortcuts::class, 'normalizeKey', "a\n");
		$mbstring = PradoUnit::getStaticProp(TMultibyteString::class, '_mbstring');
		PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', false);
		try {
			$this->assertSame('Control+S Meta+S', TKeyShortcuts::normalize('Control+S meta+s control+s'));
			$this->assertSame('ß É', TKeyShortcuts::normalize('ß é'));
			$this->assertSame('A Escape', TKeyShortcuts::normalize('a esc'));
			$this->assertSame($newline, PradoUnit::invoke(TKeyShortcuts::class, 'normalizeKey', "a\n"));
		} finally {
			PradoUnit::setStaticProp(TMultibyteString::class, '_mbstring', $mbstring);
		}
	}

	public function testNormalizeEmpty()
	{
		$this->assertSame('', TKeyShortcuts::normalize(''));
		$this->assertSame('', TKeyShortcuts::normalize('   '));
		$this->assertSame('', TKeyShortcuts::normalize(null));
	}

	public static function invalidShortcuts(): array
	{
		return [
			'modifier only' => ['Control'],
			'two modifiers' => ['Control+Shift'],
			'two keys' => ['A+B'],
			'empty token' => ['Control++'],
			'plus alone' => ['+'],
			'F25' => ['F25'],
			'F0' => ['F0'],
			'unknown modifier' => ['Hyper+A'],
			'unknown key in list' => ['Control+S Bogus'],
		];
	}

	/**
	 * @dataProvider invalidShortcuts
	 */
	public function testNormalizeRejectsInvalid(string $value)
	{
		$this->assertNull(TKeyShortcuts::normalizeShortcut(explode(' ', $value)[1] ?? $value));
		$this->expectException(TInvalidDataValueException::class);
		TKeyShortcuts::normalize($value);
	}

	public function testNormalizeExceptionNamesTheShortcut()
	{
		try {
			TKeyShortcuts::normalize('Control+S Control+');
			$this->fail('Expected TInvalidDataValueException');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('keyshortcuts_invalid', $e->getErrorCode());
			$this->assertStringContainsString("'Control+'", $e->getMessage());
		}
	}

	public function testIsReserved()
	{
		foreach (['Tab', 'Shift+Tab', 'Control+Tab', 'Enter', 'Space', 'ArrowUp', 'PageDown', 'Control+C',
			'Meta+V', 'Control+Shift+Z', 'Shift+Meta+T', 'Meta+Plus', 'Control+-', 'Control+0', 'F5', 'F12',
			'Alt+ArrowLeft'] as $shortcut) {
			$this->assertTrue(TKeyShortcuts::isReserved($shortcut), $shortcut);
		}
		foreach (['Control+S', 'Meta+S', 'Control+P', 'Escape', '?', 'B', 'Alt+Shift+P', 'F2'] as $shortcut) {
			$this->assertFalse(TKeyShortcuts::isReserved($shortcut), $shortcut);
		}
	}

	public function testReservedShortcutsAreNormalized()
	{
		foreach (array_keys(TKeyShortcuts::getReservedShortcuts()) as $shortcut) {
			$this->assertSame($shortcut, TKeyShortcuts::normalizeShortcut($shortcut));
		}
	}
}
