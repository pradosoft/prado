<?php

/**
 * TFontTest.php
 *
 * @author PRADO contributors
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Web\UI\WebControls\TFont;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive tests for {@see TFont}.
 *
 * TFont uses a single integer bitmask (_flags) with two independent layers:
 *
 *   - **Value bits** (IS_BOLD, IS_ITALIC, IS_OVERLINE, IS_STRIKEOUT, IS_UNDERLINE) —
 *     the actual on/off state of each boolean property.
 *   - **Set-state bits** (IS_SET_BOLD, IS_SET_ITALIC, …, IS_SET_SIZE, IS_SET_NAME) —
 *     whether the property has *ever* been explicitly assigned.
 *
 * Key asymmetry in rendering:
 *   - Bold / Italic — rendered as `normal` when IS_SET=true but value=false.
 *   - Underline / Overline / Strikeout — only rendered when the VALUE bit is true;
 *     explicitly setting them false produces no `text-decoration` output.
 *
 * Sections:
 *   1.  Constants — bit values and non-overlap
 *   2.  Constructor — default state
 *   3.  Bold property
 *   4.  Italic property
 *   5.  Overline property
 *   6.  Strikeout property
 *   7.  Underline property
 *   8.  Name property
 *   9.  Size property
 *   10. getIsEmpty()
 *   11. reset()
 *   12. mergeWith() — target wins
 *   13. copyFrom() — source wins
 *   14. toString()
 *   15. addAttributesToRender()
 *   16. Serialization (_getZappableSleepProps + roundtrip)
 */
class TFontTest extends TestCase
{
	// ================================================================================
	// Helpers
	// ================================================================================

	/**
	 * Renders $font through addAttributesToRender onto a <span> and returns the
	 * full HTML string produced by the writer.
	 */
	private function renderFont(TFont $font): string
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$font->addAttributesToRender($writer);
		$writer->renderBeginTag('span');
		$writer->renderEndTag();
		return $tw->flush();
	}

	// ================================================================================
	// 1. Constants
	// ================================================================================

	public function testValueBitConstants()
	{
		$this->assertSame(0x01, TFont::IS_BOLD);
		$this->assertSame(0x02, TFont::IS_ITALIC);
		$this->assertSame(0x04, TFont::IS_OVERLINE);
		$this->assertSame(0x08, TFont::IS_STRIKEOUT);
		$this->assertSame(0x10, TFont::IS_UNDERLINE);
	}

	public function testSetStateBitConstants()
	{
		$this->assertSame(0x01000, TFont::IS_SET_BOLD);
		$this->assertSame(0x02000, TFont::IS_SET_ITALIC);
		$this->assertSame(0x04000, TFont::IS_SET_OVERLINE);
		$this->assertSame(0x08000, TFont::IS_SET_STRIKEOUT);
		$this->assertSame(0x10000, TFont::IS_SET_UNDERLINE);
		$this->assertSame(0x20000, TFont::IS_SET_SIZE);
		$this->assertSame(0x40000, TFont::IS_SET_NAME);
	}

	public function testValueBitsDoNotOverlapSetStateBits()
	{
		$valueBits = TFont::IS_BOLD | TFont::IS_ITALIC | TFont::IS_OVERLINE
			| TFont::IS_STRIKEOUT | TFont::IS_UNDERLINE;
		$setBits = TFont::IS_SET_BOLD | TFont::IS_SET_ITALIC | TFont::IS_SET_OVERLINE
			| TFont::IS_SET_STRIKEOUT | TFont::IS_SET_UNDERLINE
			| TFont::IS_SET_SIZE | TFont::IS_SET_NAME;
		$this->assertSame(0, $valueBits & $setBits, 'Value bits and set-state bits must not overlap');
	}

	public function testValueBitsAreAllDistinct()
	{
		$bits = [TFont::IS_BOLD, TFont::IS_ITALIC, TFont::IS_OVERLINE, TFont::IS_STRIKEOUT, TFont::IS_UNDERLINE];
		$combined = 0;
		foreach ($bits as $bit) {
			$this->assertSame(0, $combined & $bit, sprintf('IS_* bit 0x%x must not overlap previous bits', $bit));
			$combined |= $bit;
		}
	}

	public function testSetStateBitsAreAllDistinct()
	{
		$bits = [
			TFont::IS_SET_BOLD,
			TFont::IS_SET_ITALIC,
			TFont::IS_SET_OVERLINE,
			TFont::IS_SET_STRIKEOUT,
			TFont::IS_SET_UNDERLINE,
			TFont::IS_SET_SIZE,
			TFont::IS_SET_NAME,
		];
		$combined = 0;
		foreach ($bits as $bit) {
			$this->assertSame(0, $combined & $bit, sprintf('IS_SET_* bit 0x%x must not overlap previous bits', $bit));
			$combined |= $bit;
		}
	}

	// ================================================================================
	// 2. Constructor
	// ================================================================================

	public function testConstructorDefaultBoldIsFalse()
	{
		$this->assertFalse((new TFont())->getBold());
	}

	public function testConstructorDefaultItalicIsFalse()
	{
		$this->assertFalse((new TFont())->getItalic());
	}

	public function testConstructorDefaultOverlineIsFalse()
	{
		$this->assertFalse((new TFont())->getOverline());
	}

	public function testConstructorDefaultStrikeoutIsFalse()
	{
		$this->assertFalse((new TFont())->getStrikeout());
	}

	public function testConstructorDefaultUnderlineIsFalse()
	{
		$this->assertFalse((new TFont())->getUnderline());
	}

	public function testConstructorDefaultNameIsEmptyString()
	{
		$this->assertSame('', (new TFont())->getName());
	}

	public function testConstructorDefaultSizeIsEmptyString()
	{
		$this->assertSame('', (new TFont())->getSize());
	}

	public function testConstructorDefaultIsEmptyIsTrue()
	{
		$this->assertTrue((new TFont())->getIsEmpty());
	}

	// ================================================================================
	// 3. Bold property
	// ================================================================================

	public function testSetBoldTrue()
	{
		$font = new TFont();
		$font->setBold(true);
		$this->assertTrue($font->getBold());
	}

	public function testSetBoldFalse()
	{
		$font = new TFont();
		$font->setBold(true);
		$font->setBold(false);
		$this->assertFalse($font->getBold());
	}

	public function testSetBoldTruthyStringValue()
	{
		$font = new TFont();
		$font->setBold('1');
		$this->assertTrue($font->getBold());
	}

	public function testSetBoldFalsyStringValue()
	{
		$font = new TFont();
		$font->setBold(true);
		$font->setBold('0');
		$this->assertFalse($font->getBold());
	}

	public function testSetBoldFalseMarksIsSetBit()
	{
		// Setting bold to false still sets IS_SET_BOLD — making the font non-empty
		$font = new TFont();
		$font->setBold(false);
		$this->assertFalse($font->getIsEmpty(), 'setBold(false) sets IS_SET_BOLD, making font non-empty');
	}

	public function testSetBoldTrueToFalse()
	{
		$font = new TFont();
		$font->setBold(true);
		$this->assertTrue($font->getBold());
		$font->setBold(false);
		$this->assertFalse($font->getBold());
		// IS_SET_BOLD still set — font is not empty
		$this->assertFalse($font->getIsEmpty());
	}

	public function testSetBoldDoesNotAffectItalic()
	{
		$font = new TFont();
		$font->setItalic(true);
		$font->setBold(true);
		$this->assertTrue($font->getItalic(), 'setBold(true) must not disturb IS_ITALIC');
		$font->setBold(false);
		$this->assertTrue($font->getItalic(), 'setBold(false) must not disturb IS_ITALIC');
	}

	public function testSetBoldDoesNotAffectDecorations()
	{
		$font = new TFont();
		$font->setUnderline(true);
		$font->setOverline(true);
		$font->setStrikeout(true);
		$font->setBold(true);
		$this->assertTrue($font->getUnderline(), 'setBold must not disturb IS_UNDERLINE');
		$this->assertTrue($font->getOverline(), 'setBold must not disturb IS_OVERLINE');
		$this->assertTrue($font->getStrikeout(), 'setBold must not disturb IS_STRIKEOUT');
	}

	// ================================================================================
	// 4. Italic property
	// ================================================================================

	public function testSetItalicTrue()
	{
		$font = new TFont();
		$font->setItalic(true);
		$this->assertTrue($font->getItalic());
	}

	public function testSetItalicFalse()
	{
		$font = new TFont();
		$font->setItalic(true);
		$font->setItalic(false);
		$this->assertFalse($font->getItalic());
	}

	public function testSetItalicFalseMarksIsSetBit()
	{
		$font = new TFont();
		$font->setItalic(false);
		$this->assertFalse($font->getIsEmpty(), 'setItalic(false) sets IS_SET_ITALIC, making font non-empty');
	}

	public function testSetItalicDoesNotAffectBold()
	{
		$font = new TFont();
		$font->setBold(true);
		$font->setItalic(true);
		$this->assertTrue($font->getBold(), 'setItalic must not disturb IS_BOLD');
	}

	// ================================================================================
	// 5. Overline property
	// ================================================================================

	public function testSetOverlineTrue()
	{
		$font = new TFont();
		$font->setOverline(true);
		$this->assertTrue($font->getOverline());
	}

	public function testSetOverlineFalse()
	{
		$font = new TFont();
		$font->setOverline(true);
		$font->setOverline(false);
		$this->assertFalse($font->getOverline());
	}

	public function testSetOverlineFalseMarksIsSetBit()
	{
		$font = new TFont();
		$font->setOverline(false);
		$this->assertFalse($font->getIsEmpty(), 'setOverline(false) sets IS_SET_OVERLINE, making font non-empty');
	}

	public function testSetOverlineDoesNotAffectUnderline()
	{
		$font = new TFont();
		$font->setUnderline(true);
		$font->setOverline(true);
		$this->assertTrue($font->getUnderline(), 'setOverline must not disturb IS_UNDERLINE');
	}

	public function testSetOverlineDoesNotAffectStrikeout()
	{
		$font = new TFont();
		$font->setStrikeout(true);
		$font->setOverline(false);
		$this->assertTrue($font->getStrikeout(), 'setOverline must not disturb IS_STRIKEOUT');
	}

	// ================================================================================
	// 6. Strikeout property
	// ================================================================================

	public function testSetStrikeoutTrue()
	{
		$font = new TFont();
		$font->setStrikeout(true);
		$this->assertTrue($font->getStrikeout());
	}

	public function testSetStrikeoutFalse()
	{
		$font = new TFont();
		$font->setStrikeout(true);
		$font->setStrikeout(false);
		$this->assertFalse($font->getStrikeout());
	}

	public function testSetStrikeoutFalseMarksIsSetBit()
	{
		$font = new TFont();
		$font->setStrikeout(false);
		$this->assertFalse($font->getIsEmpty(), 'setStrikeout(false) sets IS_SET_STRIKEOUT, making font non-empty');
	}

	public function testSetStrikeoutDoesNotAffectOverline()
	{
		$font = new TFont();
		$font->setOverline(true);
		$font->setStrikeout(true);
		$this->assertTrue($font->getOverline(), 'setStrikeout must not disturb IS_OVERLINE');
	}

	// ================================================================================
	// 7. Underline property
	// ================================================================================

	public function testSetUnderlineTrue()
	{
		$font = new TFont();
		$font->setUnderline(true);
		$this->assertTrue($font->getUnderline());
	}

	public function testSetUnderlineFalse()
	{
		$font = new TFont();
		$font->setUnderline(true);
		$font->setUnderline(false);
		$this->assertFalse($font->getUnderline());
	}

	public function testSetUnderlineFalseMarksIsSetBit()
	{
		$font = new TFont();
		$font->setUnderline(false);
		$this->assertFalse($font->getIsEmpty(), 'setUnderline(false) sets IS_SET_UNDERLINE, making font non-empty');
	}

	public function testSetUnderlineDoesNotAffectStrikeout()
	{
		$font = new TFont();
		$font->setStrikeout(true);
		$font->setUnderline(true);
		$this->assertTrue($font->getStrikeout(), 'setUnderline must not disturb IS_STRIKEOUT');
	}

	public function testSetUnderlineDoesNotAffectOverline()
	{
		$font = new TFont();
		$font->setOverline(true);
		$font->setUnderline(false);
		$this->assertTrue($font->getOverline(), 'setUnderline must not disturb IS_OVERLINE');
	}

	// ================================================================================
	// 8. Name property
	// ================================================================================

	public function testSetNameStoresValue()
	{
		$font = new TFont();
		$font->setName('Arial');
		$this->assertSame('Arial', $font->getName());
	}

	public function testSetNameDoesNotTrim()
	{
		$font = new TFont();
		$font->setName('  Arial  ');
		$this->assertSame('  Arial  ', $font->getName(), 'setName must not trim whitespace');
	}

	public function testSetNameEmptyString()
	{
		$font = new TFont();
		$font->setName('Arial');
		$font->setName('');
		$this->assertSame('', $font->getName());
	}

	public function testSetNameEmptyStringMarksIsSetBit()
	{
		// IS_SET_NAME is set even when name is empty — font is not empty
		$font = new TFont();
		$font->setName('');
		$this->assertFalse($font->getIsEmpty(), 'IS_SET_NAME must be set even for empty name');
	}

	public function testSetNameWithFontFamily()
	{
		$font = new TFont();
		$font->setName('Arial, Helvetica, sans-serif');
		$this->assertSame('Arial, Helvetica, sans-serif', $font->getName());
	}

	public function testSetNameOverwritesPreviousValue()
	{
		$font = new TFont();
		$font->setName('Arial');
		$font->setName('Georgia');
		$this->assertSame('Georgia', $font->getName());
	}

	// ================================================================================
	// 9. Size property
	// ================================================================================

	public function testSetSizeStoresValue()
	{
		$font = new TFont();
		$font->setSize('12px');
		$this->assertSame('12px', $font->getSize());
	}

	public function testSetSizeDoesNotTrim()
	{
		$font = new TFont();
		$font->setSize('  12px  ');
		$this->assertSame('  12px  ', $font->getSize(), 'setSize must not trim whitespace');
	}

	public function testSetSizeEmptyString()
	{
		$font = new TFont();
		$font->setSize('12px');
		$font->setSize('');
		$this->assertSame('', $font->getSize());
	}

	public function testSetSizeEmptyStringMarksIsSetBit()
	{
		$font = new TFont();
		$font->setSize('');
		$this->assertFalse($font->getIsEmpty(), 'IS_SET_SIZE must be set even for empty size');
	}

	public function testSetSizeVariousFormats()
	{
		$font = new TFont();
		foreach (['12px', '1em', '1.5rem', '120%', 'large', 'smaller'] as $size) {
			$font->setSize($size);
			$this->assertSame($size, $font->getSize(), "setSize must store '$size' verbatim");
		}
	}

	public function testSetSizeOverwritesPreviousValue()
	{
		$font = new TFont();
		$font->setSize('12px');
		$font->setSize('2em');
		$this->assertSame('2em', $font->getSize());
	}

	// ================================================================================
	// 10. getIsEmpty()
	// ================================================================================

	public function testIsEmptyTrueForDefaultFont()
	{
		$this->assertTrue((new TFont())->getIsEmpty());
	}

	public function testIsEmptyFalseAfterSetBoldTrue()
	{
		$font = new TFont();
		$font->setBold(true);
		$this->assertFalse($font->getIsEmpty());
	}

	public function testIsEmptyFalseAfterSetBoldFalse()
	{
		// IS_SET_BOLD is set even when value is false
		$font = new TFont();
		$font->setBold(false);
		$this->assertFalse($font->getIsEmpty(), 'setBold(false) sets IS_SET_BOLD — font is non-empty');
	}

	public function testIsEmptyFalseAfterSetItalicFalse()
	{
		$font = new TFont();
		$font->setItalic(false);
		$this->assertFalse($font->getIsEmpty());
	}

	public function testIsEmptyFalseAfterSetOverlineFalse()
	{
		$font = new TFont();
		$font->setOverline(false);
		$this->assertFalse($font->getIsEmpty());
	}

	public function testIsEmptyFalseAfterSetStrikeoutFalse()
	{
		$font = new TFont();
		$font->setStrikeout(false);
		$this->assertFalse($font->getIsEmpty());
	}

	public function testIsEmptyFalseAfterSetUnderlineFalse()
	{
		$font = new TFont();
		$font->setUnderline(false);
		$this->assertFalse($font->getIsEmpty());
	}

	public function testIsEmptyFalseAfterSetName()
	{
		$font = new TFont();
		$font->setName('Arial');
		$this->assertFalse($font->getIsEmpty());
	}

	public function testIsEmptyFalseAfterSetSize()
	{
		$font = new TFont();
		$font->setSize('12px');
		$this->assertFalse($font->getIsEmpty());
	}

	public function testIsEmptyFalseAfterSetNameEmpty()
	{
		// IS_SET_NAME bit alone makes the font non-empty
		$font = new TFont();
		$font->setName('');
		$this->assertFalse($font->getIsEmpty());
	}

	public function testIsEmptyFalseAfterSetSizeEmpty()
	{
		$font = new TFont();
		$font->setSize('');
		$this->assertFalse($font->getIsEmpty());
	}

	public function testIsEmptyTrueAfterReset()
	{
		$font = new TFont();
		$font->setBold(true);
		$font->setName('Arial');
		$font->setSize('12px');
		$font->reset();
		$this->assertTrue($font->getIsEmpty());
	}

	// ================================================================================
	// 11. reset()
	// ================================================================================

	public function testResetClearsBold()
	{
		$font = new TFont();
		$font->setBold(true);
		$font->reset();
		$this->assertFalse($font->getBold());
	}

	public function testResetClearsItalic()
	{
		$font = new TFont();
		$font->setItalic(true);
		$font->reset();
		$this->assertFalse($font->getItalic());
	}

	public function testResetClearsOverline()
	{
		$font = new TFont();
		$font->setOverline(true);
		$font->reset();
		$this->assertFalse($font->getOverline());
	}

	public function testResetClearsStrikeout()
	{
		$font = new TFont();
		$font->setStrikeout(true);
		$font->reset();
		$this->assertFalse($font->getStrikeout());
	}

	public function testResetClearsUnderline()
	{
		$font = new TFont();
		$font->setUnderline(true);
		$font->reset();
		$this->assertFalse($font->getUnderline());
	}

	public function testResetClearsName()
	{
		$font = new TFont();
		$font->setName('Arial');
		$font->reset();
		$this->assertSame('', $font->getName());
	}

	public function testResetClearsSize()
	{
		$font = new TFont();
		$font->setSize('12px');
		$font->reset();
		$this->assertSame('', $font->getSize());
	}

	public function testResetClearsIsSetBits()
	{
		// IS_SET_BOLD was set by setBold(false) — after reset it must be gone
		$font = new TFont();
		$font->setBold(false);
		$this->assertFalse($font->getIsEmpty(), 'Before reset: IS_SET_BOLD is set');
		$font->reset();
		$this->assertTrue($font->getIsEmpty(), 'After reset: all bits including IS_SET_BOLD must be cleared');
	}

	public function testResetMakesIsEmptyTrue()
	{
		$font = new TFont();
		$font->setBold(true);
		$font->setItalic(false);
		$font->setUnderline(true);
		$font->setName('Arial');
		$font->setSize('12px');
		$font->reset();
		$this->assertTrue($font->getIsEmpty());
	}

	public function testResetMakesToStringReturnEmpty()
	{
		$font = new TFont();
		$font->setBold(true);
		$font->setSize('14px');
		$font->reset();
		$this->assertSame('', $font->toString());
	}

	// ================================================================================
	// 12. mergeWith() — target wins
	// ================================================================================

	public function testMergeWithNullIsNoop()
	{
		$font = new TFont();
		$font->setBold(true);
		$font->mergeWith(null);
		$this->assertTrue($font->getBold(), 'mergeWith(null) must be a no-op');
	}

	public function testMergeWithEmptyFontIsNoop()
	{
		$font = new TFont();
		$font->setBold(true);
		$font->mergeWith(new TFont());
		$this->assertTrue($font->getBold(), 'mergeWith(empty font) must be a no-op');
	}

	public function testMergeWithAdoptsBoldFromSourceWhenTargetUnset()
	{
		$target = new TFont();
		$source = new TFont();
		$source->setBold(true);
		$target->mergeWith($source);
		$this->assertTrue($target->getBold(), 'Target adopts source bold when target bold is unset');
	}

	public function testMergeWithTargetBoldWinsOverSourceBold()
	{
		$target = new TFont();
		$target->setBold(true);
		$source = new TFont();
		$source->setBold(false);
		$target->mergeWith($source);
		$this->assertTrue($target->getBold(), 'Target true wins over source false');
	}

	public function testMergeWithTargetExplicitFalseWinsOverSourceTrue()
	{
		// Target explicitly set to false — that IS_SET_BOLD blocks source
		$target = new TFont();
		$target->setBold(false);
		$source = new TFont();
		$source->setBold(true);
		$target->mergeWith($source);
		$this->assertFalse($target->getBold(), 'Target explicit false wins over source true');
	}

	public function testMergeWithAdoptsItalicFromSource()
	{
		$target = new TFont();
		$source = new TFont();
		$source->setItalic(true);
		$target->mergeWith($source);
		$this->assertTrue($target->getItalic());
	}

	public function testMergeWithTargetItalicWins()
	{
		$target = new TFont();
		$target->setItalic(false);
		$source = new TFont();
		$source->setItalic(true);
		$target->mergeWith($source);
		$this->assertFalse($target->getItalic(), 'Target italic wins');
	}

	public function testMergeWithAdoptsOverlineFromSource()
	{
		$target = new TFont();
		$source = new TFont();
		$source->setOverline(true);
		$target->mergeWith($source);
		$this->assertTrue($target->getOverline());
	}

	public function testMergeWithTargetOverlineWins()
	{
		$target = new TFont();
		$target->setOverline(false);
		$source = new TFont();
		$source->setOverline(true);
		$target->mergeWith($source);
		$this->assertFalse($target->getOverline(), 'Target overline wins');
	}

	public function testMergeWithAdoptsStrikeoutFromSource()
	{
		$target = new TFont();
		$source = new TFont();
		$source->setStrikeout(true);
		$target->mergeWith($source);
		$this->assertTrue($target->getStrikeout());
	}

	public function testMergeWithTargetStrikeoutWins()
	{
		$target = new TFont();
		$target->setStrikeout(false);
		$source = new TFont();
		$source->setStrikeout(true);
		$target->mergeWith($source);
		$this->assertFalse($target->getStrikeout(), 'Target strikeout wins');
	}

	public function testMergeWithAdoptsUnderlineFromSource()
	{
		$target = new TFont();
		$source = new TFont();
		$source->setUnderline(true);
		$target->mergeWith($source);
		$this->assertTrue($target->getUnderline());
	}

	public function testMergeWithTargetUnderlineWins()
	{
		$target = new TFont();
		$target->setUnderline(false);
		$source = new TFont();
		$source->setUnderline(true);
		$target->mergeWith($source);
		$this->assertFalse($target->getUnderline(), 'Target underline wins');
	}

	public function testMergeWithAdoptsNameFromSource()
	{
		$target = new TFont();
		$source = new TFont();
		$source->setName('Arial');
		$target->mergeWith($source);
		$this->assertSame('Arial', $target->getName());
	}

	public function testMergeWithTargetNameWins()
	{
		$target = new TFont();
		$target->setName('Helvetica');
		$source = new TFont();
		$source->setName('Arial');
		$target->mergeWith($source);
		$this->assertSame('Helvetica', $target->getName(), 'Target name wins');
	}

	public function testMergeWithAdoptsSizeFromSource()
	{
		$target = new TFont();
		$source = new TFont();
		$source->setSize('12px');
		$target->mergeWith($source);
		$this->assertSame('12px', $target->getSize());
	}

	public function testMergeWithTargetSizeWins()
	{
		$target = new TFont();
		$target->setSize('14px');
		$source = new TFont();
		$source->setSize('12px');
		$target->mergeWith($source);
		$this->assertSame('14px', $target->getSize(), 'Target size wins');
	}

	public function testMergeWithLeavesUnsetPropertiesUnchanged()
	{
		$target = new TFont();
		$target->setBold(true);
		$source = new TFont();
		$source->setItalic(true);
		$target->mergeWith($source);
		// italic was unset in target — picked up from source
		$this->assertTrue($target->getItalic());
		// bold was set in target — unchanged
		$this->assertTrue($target->getBold());
		// name/size not set in either — still empty
		$this->assertSame('', $target->getName());
		$this->assertSame('', $target->getSize());
	}

	public function testMergeWithDoesNotMutateSource()
	{
		$target = new TFont();
		$source = new TFont();
		$source->setBold(true);
		$source->setName('Arial');
		$target->mergeWith($source);
		$this->assertTrue($source->getBold(), 'mergeWith must not mutate source');
		$this->assertSame('Arial', $source->getName(), 'mergeWith must not mutate source');
	}

	public function testMergeWithAllPropertiesAdoptedFromSource()
	{
		$target = new TFont(); // fully unset
		$source = new TFont();
		$source->setBold(true);
		$source->setItalic(false); // explicit false
		$source->setOverline(true);
		$source->setStrikeout(false); // explicit false
		$source->setUnderline(true);
		$source->setName('Georgia');
		$source->setSize('16px');
		$target->mergeWith($source);
		$this->assertTrue($target->getBold());
		$this->assertFalse($target->getItalic(), 'Explicit false adopted from source');
		$this->assertTrue($target->getOverline());
		$this->assertFalse($target->getStrikeout(), 'Explicit false adopted from source');
		$this->assertTrue($target->getUnderline());
		$this->assertSame('Georgia', $target->getName());
		$this->assertSame('16px', $target->getSize());
	}

	// ================================================================================
	// 13. copyFrom() — source wins
	// ================================================================================

	public function testCopyFromNullIsNoop()
	{
		$font = new TFont();
		$font->setBold(true);
		$font->copyFrom(null);
		$this->assertTrue($font->getBold(), 'copyFrom(null) must be a no-op');
	}

	public function testCopyFromEmptyFontIsNoop()
	{
		$font = new TFont();
		$font->setBold(true);
		$font->copyFrom(new TFont());
		$this->assertTrue($font->getBold(), 'copyFrom(empty font) must be a no-op');
	}

	public function testCopyFromSourceBoldOverwritesTarget()
	{
		$target = new TFont();
		$target->setBold(false);
		$source = new TFont();
		$source->setBold(true);
		$target->copyFrom($source);
		$this->assertTrue($target->getBold(), 'Source true overwrites target false');
	}

	public function testCopyFromSourceFalseOverwritesTargetTrue()
	{
		$target = new TFont();
		$target->setBold(true);
		$source = new TFont();
		$source->setBold(false);
		$target->copyFrom($source);
		$this->assertFalse($target->getBold(), 'Source explicit false overwrites target true');
	}

	public function testCopyFromSourceItalicOverwritesTarget()
	{
		$target = new TFont();
		$target->setItalic(true);
		$source = new TFont();
		$source->setItalic(false);
		$target->copyFrom($source);
		$this->assertFalse($target->getItalic(), 'Source italic false wins');
	}

	public function testCopyFromSourceOverlineOverwritesTarget()
	{
		$target = new TFont();
		$target->setOverline(true);
		$source = new TFont();
		$source->setOverline(false);
		$target->copyFrom($source);
		$this->assertFalse($target->getOverline(), 'Source overline false wins');
	}

	public function testCopyFromSourceStrikeoutOverwritesTarget()
	{
		$target = new TFont();
		$target->setStrikeout(true);
		$source = new TFont();
		$source->setStrikeout(false);
		$target->copyFrom($source);
		$this->assertFalse($target->getStrikeout(), 'Source strikeout false wins');
	}

	public function testCopyFromSourceUnderlineOverwritesTarget()
	{
		$target = new TFont();
		$target->setUnderline(true);
		$source = new TFont();
		$source->setUnderline(false);
		$target->copyFrom($source);
		$this->assertFalse($target->getUnderline(), 'Source underline false wins');
	}

	public function testCopyFromSourceNameOverwritesTarget()
	{
		$target = new TFont();
		$target->setName('Helvetica');
		$source = new TFont();
		$source->setName('Arial');
		$target->copyFrom($source);
		$this->assertSame('Arial', $target->getName(), 'Source name wins');
	}

	public function testCopyFromSourceSizeOverwritesTarget()
	{
		$target = new TFont();
		$target->setSize('14px');
		$source = new TFont();
		$source->setSize('12px');
		$target->copyFrom($source);
		$this->assertSame('12px', $target->getSize(), 'Source size wins');
	}

	public function testCopyFromDoesNotOverwriteUnsetSourceProperties()
	{
		$target = new TFont();
		$target->setBold(true);
		$target->setName('Helvetica');
		$source = new TFont();
		$source->setItalic(true);
		$target->copyFrom($source);
		$this->assertTrue($target->getBold(), 'Bold unset in source — target retains its value');
		$this->assertSame('Helvetica', $target->getName(), 'Name unset in source — target retains its value');
		$this->assertTrue($target->getItalic(), 'Italic set in source — target gets source value');
	}

	public function testCopyFromDoesNotMutateSource()
	{
		$target = new TFont();
		$source = new TFont();
		$source->setItalic(true);
		$source->setSize('16px');
		$target->copyFrom($source);
		$this->assertTrue($source->getItalic(), 'copyFrom must not mutate source');
		$this->assertSame('16px', $source->getSize(), 'copyFrom must not mutate source');
	}

	public function testCopyFromAllPropertiesOverwriteTarget()
	{
		$target = new TFont();
		$target->setBold(true);
		$target->setItalic(true);
		$target->setOverline(true);
		$target->setStrikeout(true);
		$target->setUnderline(true);
		$target->setName('Helvetica');
		$target->setSize('14px');

		$source = new TFont();
		$source->setBold(false);
		$source->setItalic(false);
		$source->setOverline(false);
		$source->setStrikeout(false);
		$source->setUnderline(false);
		$source->setName('Arial');
		$source->setSize('12px');

		$target->copyFrom($source);

		$this->assertFalse($target->getBold());
		$this->assertFalse($target->getItalic());
		$this->assertFalse($target->getOverline());
		$this->assertFalse($target->getStrikeout());
		$this->assertFalse($target->getUnderline());
		$this->assertSame('Arial', $target->getName());
		$this->assertSame('12px', $target->getSize());
	}

	// ================================================================================
	// 14. toString()
	// ================================================================================

	public function testToStringEmptyFont()
	{
		$this->assertSame('', (new TFont())->toString());
	}

	public function testToStringBoldTrue()
	{
		$font = new TFont();
		$font->setBold(true);
		$this->assertStringContainsString('font-weight:bold;', $font->toString());
	}

	public function testToStringBoldFalseRendersNormal()
	{
		// Asymmetry: bold explicitly false renders font-weight:normal
		$font = new TFont();
		$font->setBold(false);
		$this->assertStringContainsString('font-weight:normal;', $font->toString(),
			'Explicitly-set-false bold must render as font-weight:normal');
	}

	public function testToStringItalicTrue()
	{
		$font = new TFont();
		$font->setItalic(true);
		$this->assertStringContainsString('font-style:italic;', $font->toString());
	}

	public function testToStringItalicFalseRendersNormal()
	{
		// Asymmetry: italic explicitly false renders font-style:normal
		$font = new TFont();
		$font->setItalic(false);
		$this->assertStringContainsString('font-style:normal;', $font->toString(),
			'Explicitly-set-false italic must render as font-style:normal');
	}

	public function testToStringUnderlineOnly()
	{
		$font = new TFont();
		$font->setUnderline(true);
		$this->assertStringContainsString('text-decoration:underline;', $font->toString());
	}

	public function testToStringOverlineOnly()
	{
		$font = new TFont();
		$font->setOverline(true);
		$this->assertStringContainsString('text-decoration:overline;', $font->toString());
	}

	public function testToStringStrikeoutOnly()
	{
		$font = new TFont();
		$font->setStrikeout(true);
		$this->assertStringContainsString('text-decoration:line-through;', $font->toString());
	}

	public function testToStringUnderlineAndOverline()
	{
		$font = new TFont();
		$font->setUnderline(true);
		$font->setOverline(true);
		$this->assertStringContainsString('text-decoration:underline overline;', $font->toString());
	}

	public function testToStringUnderlineAndStrikeout()
	{
		$font = new TFont();
		$font->setUnderline(true);
		$font->setStrikeout(true);
		$this->assertStringContainsString('text-decoration:underline line-through;', $font->toString());
	}

	public function testToStringOverlineAndStrikeout()
	{
		$font = new TFont();
		$font->setOverline(true);
		$font->setStrikeout(true);
		$this->assertStringContainsString('text-decoration:overline line-through;', $font->toString());
	}

	public function testToStringAllThreeDecorations()
	{
		$font = new TFont();
		$font->setUnderline(true);
		$font->setOverline(true);
		$font->setStrikeout(true);
		$this->assertStringContainsString('text-decoration:underline overline line-through;', $font->toString());
	}

	public function testToStringTextDecorationOrderIsUnderlineOverlineLineThrough()
	{
		// Verify the exact order produced by the implementation
		$font = new TFont();
		$font->setUnderline(true);
		$font->setOverline(true);
		$font->setStrikeout(true);
		$str = $font->toString();
		$underlinePos = strpos($str, 'underline');
		$overlinePos = strpos($str, 'overline');
		$strikePos = strpos($str, 'line-through');
		$this->assertLessThan($overlinePos, $underlinePos, 'underline must precede overline');
		$this->assertLessThan($strikePos, $overlinePos, 'overline must precede line-through');
	}

	public function testToStringUnderlineFalseProducesNoTextDecoration()
	{
		// Asymmetry: unlike bold/italic, decoration false does NOT render fallback
		$font = new TFont();
		$font->setUnderline(false);
		$this->assertStringNotContainsString('text-decoration', $font->toString(),
			'Explicitly-set-false underline must not produce text-decoration');
	}

	public function testToStringOverlineFalseProducesNoTextDecoration()
	{
		$font = new TFont();
		$font->setOverline(false);
		$this->assertStringNotContainsString('text-decoration', $font->toString());
	}

	public function testToStringStrikeoutFalseProducesNoTextDecoration()
	{
		$font = new TFont();
		$font->setStrikeout(false);
		$this->assertStringNotContainsString('text-decoration', $font->toString());
	}

	public function testToStringAllDecorationsSetFalseProducesNoTextDecoration()
	{
		$font = new TFont();
		$font->setUnderline(false);
		$font->setOverline(false);
		$font->setStrikeout(false);
		$this->assertStringNotContainsString('text-decoration', $font->toString(),
			'No text-decoration when all decorations explicitly set to false');
	}

	public function testToStringSize()
	{
		$font = new TFont();
		$font->setSize('14px');
		$this->assertStringContainsString('font-size:14px;', $font->toString());
	}

	public function testToStringSizeEmptyStringNotRendered()
	{
		$font = new TFont();
		$font->setSize('');
		$this->assertStringNotContainsString('font-size', $font->toString(),
			'Empty size string must not produce font-size CSS');
	}

	public function testToStringName()
	{
		$font = new TFont();
		$font->setName('Arial');
		$this->assertStringContainsString('font-family:Arial;', $font->toString());
	}

	public function testToStringNameEmptyStringNotRendered()
	{
		$font = new TFont();
		$font->setName('');
		$this->assertStringNotContainsString('font-family', $font->toString(),
			'Empty name string must not produce font-family CSS');
	}

	public function testToStringFullCombination()
	{
		$font = new TFont();
		$font->setBold(true);
		$font->setItalic(true);
		$font->setUnderline(true);
		$font->setOverline(true);
		$font->setStrikeout(true);
		$font->setSize('16px');
		$font->setName('Georgia');
		$str = $font->toString();
		$this->assertStringContainsString('font-weight:bold;', $str);
		$this->assertStringContainsString('font-style:italic;', $str);
		$this->assertStringContainsString('text-decoration:underline overline line-through;', $str);
		$this->assertStringContainsString('font-size:16px;', $str);
		$this->assertStringContainsString('font-family:Georgia;', $str);
	}

	public function testToStringBoldFalseItalicFalseNoDecorations()
	{
		$font = new TFont();
		$font->setBold(false);
		$font->setItalic(false);
		$str = $font->toString();
		$this->assertStringContainsString('font-weight:normal;', $str);
		$this->assertStringContainsString('font-style:normal;', $str);
		$this->assertStringNotContainsString('text-decoration', $str);
	}

	public function testToStringBoldTrueItalicFalseWithUnderline()
	{
		$font = new TFont();
		$font->setBold(true);
		$font->setItalic(false);
		$font->setUnderline(true);
		$str = $font->toString();
		$this->assertStringContainsString('font-weight:bold;', $str);
		$this->assertStringContainsString('font-style:normal;', $str);
		$this->assertStringContainsString('text-decoration:underline;', $str);
	}

	// ================================================================================
	// 15. addAttributesToRender()
	// ================================================================================

	public function testAddAttributesToRenderEmptyFontProducesNoStyleAttr()
	{
		$output = $this->renderFont(new TFont());
		$this->assertStringNotContainsString('style=', $output,
			'Empty font must not produce a style attribute');
	}

	public function testAddAttributesToRenderBoldTrue()
	{
		$font = new TFont();
		$font->setBold(true);
		$this->assertStringContainsString('font-weight:bold', $this->renderFont($font));
	}

	public function testAddAttributesToRenderBoldFalseRendersNormal()
	{
		$font = new TFont();
		$font->setBold(false);
		$this->assertStringContainsString('font-weight:normal', $this->renderFont($font),
			'Explicitly-set-false bold must render font-weight:normal');
	}

	public function testAddAttributesToRenderItalicTrue()
	{
		$font = new TFont();
		$font->setItalic(true);
		$this->assertStringContainsString('font-style:italic', $this->renderFont($font));
	}

	public function testAddAttributesToRenderItalicFalseRendersNormal()
	{
		$font = new TFont();
		$font->setItalic(false);
		$this->assertStringContainsString('font-style:normal', $this->renderFont($font),
			'Explicitly-set-false italic must render font-style:normal');
	}

	public function testAddAttributesToRenderUnderlineTrue()
	{
		$font = new TFont();
		$font->setUnderline(true);
		$this->assertStringContainsString('text-decoration:underline', $this->renderFont($font));
	}

	public function testAddAttributesToRenderOverlineOnly()
	{
		$font = new TFont();
		$font->setOverline(true);
		$this->assertStringContainsString('text-decoration:overline', $this->renderFont($font));
	}

	public function testAddAttributesToRenderStrikeoutOnly()
	{
		$font = new TFont();
		$font->setStrikeout(true);
		$this->assertStringContainsString('text-decoration:line-through', $this->renderFont($font));
	}

	public function testAddAttributesToRenderAllDecorations()
	{
		$font = new TFont();
		$font->setUnderline(true);
		$font->setOverline(true);
		$font->setStrikeout(true);
		$this->assertStringContainsString(
			'text-decoration:underline overline line-through',
			$this->renderFont($font)
		);
	}

	public function testAddAttributesToRenderNoTextDecorationWhenDecorationsFalse()
	{
		$font = new TFont();
		$font->setUnderline(false);
		$font->setOverline(false);
		$font->setStrikeout(false);
		$this->assertStringNotContainsString('text-decoration', $this->renderFont($font),
			'No text-decoration rendered when all decorations set to false');
	}

	public function testAddAttributesToRenderSize()
	{
		$font = new TFont();
		$font->setSize('1.5em');
		$this->assertStringContainsString('font-size:1.5em', $this->renderFont($font));
	}

	public function testAddAttributesToRenderSizeEmptyNotRendered()
	{
		$font = new TFont();
		$font->setSize('');
		$this->assertStringNotContainsString('font-size', $this->renderFont($font));
	}

	public function testAddAttributesToRenderName()
	{
		$font = new TFont();
		$font->setName('Verdana');
		$this->assertStringContainsString('font-family:Verdana', $this->renderFont($font));
	}

	public function testAddAttributesToRenderNameEmptyNotRendered()
	{
		$font = new TFont();
		$font->setName('');
		$this->assertStringNotContainsString('font-family', $this->renderFont($font));
	}

	public function testAddAttributesToRenderFullCombination()
	{
		$font = new TFont();
		$font->setBold(true);
		$font->setItalic(false); // explicit false → normal
		$font->setUnderline(true);
		$font->setStrikeout(true);
		$font->setSize('12pt');
		$font->setName('Courier New');
		$output = $this->renderFont($font);
		$this->assertStringContainsString('font-weight:bold', $output);
		$this->assertStringContainsString('font-style:normal', $output);
		$this->assertStringContainsString('text-decoration:underline line-through', $output);
		$this->assertStringContainsString('font-size:12pt', $output);
		$this->assertStringContainsString('font-family:Courier New', $output);
	}

	public function testAddAttributesToRenderAgreesWithToString()
	{
		$font = new TFont();
		$font->setBold(true);
		$font->setItalic(false);
		$font->setUnderline(true);
		$font->setSize('14px');
		$font->setName('sans-serif');

		$str = $font->toString();
		$rendered = $this->renderFont($font);

		// Both outputs must carry the same properties
		$this->assertStringContainsString('font-weight:bold', $str);
		$this->assertStringContainsString('font-weight:bold', $rendered);
		$this->assertStringContainsString('font-style:normal', $str);
		$this->assertStringContainsString('font-style:normal', $rendered);
		$this->assertStringContainsString('text-decoration:underline', $str);
		$this->assertStringContainsString('text-decoration:underline', $rendered);
		$this->assertStringContainsString('font-size:14px', $str);
		$this->assertStringContainsString('font-size:14px', $rendered);
		$this->assertStringContainsString('font-family:sans-serif', $str);
		$this->assertStringContainsString('font-family:sans-serif', $rendered);
	}

	// ================================================================================
	// 16. Serialization (_getZappableSleepProps + roundtrip)
	// ================================================================================

	public function testSerializeDefaultFontZapsAllThreeProperties()
	{
		// Default font: _flags=0, _name='', _size='' — all should be zapped
		$font = new TFont();
		$data = serialize($font);
		$this->assertStringNotContainsString('_flags', $data, '_flags must be zapped when 0');
		$this->assertStringNotContainsString('_name', $data, '_name must be zapped when empty');
		$this->assertStringNotContainsString('_size', $data, '_size must be zapped when empty');
	}

	public function testSerializeWithNonZeroFlagsIncludesFlags()
	{
		$font = new TFont();
		$font->setBold(true);
		$data = serialize($font);
		$this->assertStringContainsString('_flags', $data, '_flags must be included when non-zero');
	}

	public function testSerializeWithExplicitFalseFlagIncludesFlags()
	{
		// setBold(false) sets IS_SET_BOLD — _flags != 0 — must be serialized
		$font = new TFont();
		$font->setBold(false);
		$data = serialize($font);
		$this->assertStringContainsString('_flags', $data,
			'_flags must be serialized when IS_SET bits are present even if value bits are false');
	}

	public function testSerializeWithNameSetIncludesName()
	{
		$font = new TFont();
		$font->setName('Arial');
		$data = serialize($font);
		$this->assertStringContainsString('_name', $data, '_name must be included when non-empty');
	}

	public function testSerializeWithSizeSetIncludesSize()
	{
		$font = new TFont();
		$font->setSize('12px');
		$data = serialize($font);
		$this->assertStringContainsString('_size', $data, '_size must be included when non-empty');
	}

	public function testSerializeZapsNameWhenEmptyEvenAfterSet()
	{
		// setName('') sets IS_SET_NAME in _flags but _name stays '' → zapped
		$font = new TFont();
		$font->setName('');
		$data = serialize($font);
		$this->assertStringNotContainsString('_name', $data,
			'_name must be zapped because its value is empty string, even though IS_SET_NAME is set');
	}

	public function testSerializeZapsSizeWhenEmptyEvenAfterSet()
	{
		$font = new TFont();
		$font->setSize('');
		$data = serialize($font);
		$this->assertStringNotContainsString('_size', $data,
			'_size must be zapped because its value is empty string, even though IS_SET_SIZE is set');
	}

	public function testSerializeRoundTripDefaultFont()
	{
		$original = new TFont();
		/** @var TFont $restored */
		$restored = unserialize(serialize($original));
		$this->assertTrue($restored->getIsEmpty());
		$this->assertFalse($restored->getBold());
		$this->assertFalse($restored->getItalic());
		$this->assertFalse($restored->getOverline());
		$this->assertFalse($restored->getStrikeout());
		$this->assertFalse($restored->getUnderline());
		$this->assertSame('', $restored->getName());
		$this->assertSame('', $restored->getSize());
	}

	public function testSerializeRoundTripPreservesAllSetProperties()
	{
		$original = new TFont();
		$original->setBold(true);
		$original->setItalic(false);   // explicit false
		$original->setOverline(true);
		$original->setStrikeout(false); // explicit false
		$original->setUnderline(true);
		$original->setSize('14px');
		$original->setName('Georgia');

		/** @var TFont $restored */
		$restored = unserialize(serialize($original));

		$this->assertTrue($restored->getBold());
		$this->assertFalse($restored->getItalic(), 'Explicit false must survive roundtrip');
		$this->assertTrue($restored->getOverline());
		$this->assertFalse($restored->getStrikeout(), 'Explicit false must survive roundtrip');
		$this->assertTrue($restored->getUnderline());
		$this->assertSame('14px', $restored->getSize());
		$this->assertSame('Georgia', $restored->getName());
		$this->assertFalse($restored->getIsEmpty());
	}

	public function testSerializeRoundTripPreservesExplicitFalseAsNonEmpty()
	{
		// setBold(false) — IS_SET_BOLD set, value clear. After roundtrip both must survive.
		$original = new TFont();
		$original->setBold(false);
		/** @var TFont $restored */
		$restored = unserialize(serialize($original));
		$this->assertFalse($restored->getBold(), 'Value false must be preserved');
		$this->assertFalse($restored->getIsEmpty(), 'IS_SET_BOLD must survive roundtrip — font remains non-empty');
	}

	public function testSerializeRoundTripSetNameEmptyPreservesIsSetBit()
	{
		// setName('') sets IS_SET_NAME in _flags (non-zero); _name is '' (zapped then restored from initializer)
		$original = new TFont();
		$original->setName('');
		/** @var TFont $restored */
		$restored = unserialize(serialize($original));
		$this->assertFalse($restored->getIsEmpty(), 'IS_SET_NAME bit in _flags must survive roundtrip');
		$this->assertSame('', $restored->getName(), '_name value must be empty after roundtrip');
	}

	public function testSerializeRoundTripSetSizeEmptyPreservesIsSetBit()
	{
		$original = new TFont();
		$original->setSize('');
		/** @var TFont $restored */
		$restored = unserialize(serialize($original));
		$this->assertFalse($restored->getIsEmpty(), 'IS_SET_SIZE bit in _flags must survive roundtrip');
		$this->assertSame('', $restored->getSize(), '_size value must be empty after roundtrip');
	}

	public function testSerializeRoundTripToStringConsistency()
	{
		$original = new TFont();
		$original->setBold(true);
		$original->setUnderline(true);
		$original->setSize('1rem');
		$original->setName('Verdana');
		/** @var TFont $restored */
		$restored = unserialize(serialize($original));
		$this->assertSame(
			$original->toString(),
			$restored->toString(),
			'toString output must be identical before and after serialization roundtrip'
		);
	}
}
