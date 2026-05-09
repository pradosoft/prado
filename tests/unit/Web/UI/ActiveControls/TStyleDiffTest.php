<?php

use Prado\Web\UI\ActiveControls\TStyleDiff;
use Prado\Web\UI\WebControls\TFont;
use Prado\Web\UI\WebControls\TStyle;
use PHPUnit\Framework\TestCase;

/**
 * Subclass that makes every protected method directly callable.
 */
class TStyleDiffExposed extends TStyleDiff
{
	public function exposedGetStyleFromString(string $s): array
	{
		return $this->getStyleFromString($s);
	}

	public function exposedGetCombinedStyle(mixed $obj): array
	{
		return $this->getCombinedStyle($obj);
	}

	public function exposedGetCssClassDiff(): ?string
	{
		return $this->getCssClassDiff();
	}

	public function exposedGetStyleDiff(): ?array
	{
		return $this->getStyleDiff();
	}
}

/**
 * Unit tests for {@see \Prado\Web\UI\ActiveControls\TStyleDiff}.
 *
 * Section organisation:
 *   1  getStyleFromString — CSS-string parsing
 *   2  getCombinedStyle   — merging _fields / customStyle / font
 *   3  getCssClassDiff    — CSS-class change detection
 *   4  getStyleDiff       — property additions and changes   (pass with old code)
 *   5  getStyleDiff       — property REMOVALS                (FAIL with old code)
 *   6  getDifference      — full integration (includes removal regressions)
 *
 * Tests tagged @group regression_ticket622 specifically cover the removal bug
 * that caused Ticket 622: the old implementation used only array_diff_assoc()
 * which detects additions but ignores removals, so clearing display:none after
 * a callback left the DOM attribute intact.
 *
 * @covers \Prado\Web\UI\ActiveControls\TStyleDiff
 */
class TStyleDiffTest extends TestCase
{
	// =========================================================================
	// Helpers
	// =========================================================================

	/** Build a TStyleDiffExposed with the given new/old values. */
	private static function diff(mixed $new, mixed $old): TStyleDiffExposed
	{
		return new TStyleDiffExposed($new, $old, new stdClass());
	}

	/**
	 * Build a TStyle with explicit named fields, an optional CSS class, and an
	 * optional raw custom-style string.
	 *
	 * @param array<string,string> $fields
	 */
	private static function style(
		array $fields = [],
		?string $cssClass = null,
		?string $customStyle = null
	): TStyle {
		$s = new TStyle();
		foreach ($fields as $name => $value) {
			$s->setStyleField($name, $value);
		}
		if ($cssClass !== null) {
			$s->setCssClass($cssClass);
		}
		if ($customStyle !== null) {
			$s->setCustomStyle($customStyle);
		}
		return $s;
	}

	/** Build a TStyle whose font has the given properties set. */
	private static function styleWithFont(
		array $fields = [],
		bool $bold = false,
		string $size = '',
		string $name = ''
	): TStyle {
		$s = self::style($fields);
		$font = $s->getFont();
		if ($bold) {
			$font->setBold(true);
		}
		if ($size !== '') {
			$font->setSize($size);
		}
		if ($name !== '') {
			$font->setName($name);
		}
		return $s;
	}

	/** The sentinel stdClass used as the null-object in getDifference(). */
	private function nullObject(): stdClass
	{
		return new stdClass();
	}

	// =========================================================================
	// Section 1 — getStyleFromString
	// =========================================================================

	public function testGetStyleFromStringEmptyStringReturnsEmptyArray()
	{
		$d = self::diff(null, null);
		$this->assertSame([], $d->exposedGetStyleFromString(''));
	}

	public function testGetStyleFromStringWhitespaceOnlyReturnsEmptyArray()
	{
		$d = self::diff(null, null);
		$this->assertSame([], $d->exposedGetStyleFromString('   '));
	}

	public function testGetStyleFromStringSemicolonsOnlyReturnsEmptyArray()
	{
		$d = self::diff(null, null);
		$this->assertSame([], $d->exposedGetStyleFromString(';;;'));
	}

	public function testGetStyleFromStringSinglePropertyNoSemicolon()
	{
		$d = self::diff(null, null);
		$this->assertSame(['color' => 'red'], $d->exposedGetStyleFromString('color:red'));
	}

	public function testGetStyleFromStringSinglePropertyWithTrailingSemicolon()
	{
		$d = self::diff(null, null);
		$this->assertSame(['color' => 'red'], $d->exposedGetStyleFromString('color:red;'));
	}

	public function testGetStyleFromStringMultipleProperties()
	{
		$d = self::diff(null, null);
		$result = $d->exposedGetStyleFromString('color:red;font-size:12px');
		$this->assertSame(['color' => 'red', 'font-size' => '12px'], $result);
	}

	public function testGetStyleFromStringTrimsWhitespaceAroundKeyAndValue()
	{
		$d = self::diff(null, null);
		$result = $d->exposedGetStyleFromString('  color  :  red  ');
		$this->assertSame(['color' => 'red'], $result);
	}

	public function testGetStyleFromStringSkipsEntryWithNoColon()
	{
		$d = self::diff(null, null);
		$result = $d->exposedGetStyleFromString('color:red;bogus;width:50px');
		$this->assertArrayNotHasKey('bogus', $result);
		$this->assertArrayHasKey('color', $result);
		$this->assertArrayHasKey('width', $result);
	}

	public function testGetStyleFromStringSkipsEntryWithWhitespaceOnlyKey()
	{
		$d = self::diff(null, null);
		$result = $d->exposedGetStyleFromString('color:red;   :value;width:50px');
		$this->assertCount(2, $result);
		$this->assertArrayHasKey('color', $result);
		$this->assertArrayHasKey('width', $result);
	}

	public function testGetStyleFromStringIncludesEntryWithEmptyValue()
	{
		// 'display:' has a colon and non-empty key → included with empty value.
		$d = self::diff(null, null);
		$result = $d->exposedGetStyleFromString('display:');
		$this->assertArrayHasKey('display', $result);
		$this->assertSame('', $result['display']);
	}

	public function testGetStyleFromStringUrlValueTruncatedAtFirstColon()
	{
		// Known limitation: values containing colons (URLs) are truncated.
		// The method only reads arr[1], so 'url(https' is the captured value.
		$d = self::diff(null, null);
		$result = $d->exposedGetStyleFromString('background-image:url(https://example.com)');
		$this->assertArrayHasKey('background-image', $result);
		$this->assertSame('url(https', $result['background-image']);
	}

	public function testGetStyleFromStringDuplicateKeyLastValueWins()
	{
		$d = self::diff(null, null);
		$result = $d->exposedGetStyleFromString('color:red;color:blue');
		$this->assertSame('blue', $result['color']);
	}

	// =========================================================================
	// Section 2 — getCombinedStyle
	// =========================================================================

	public function testGetCombinedStyleNullReturnsEmpty()
	{
		$d = self::diff(null, null);
		$this->assertSame([], $d->exposedGetCombinedStyle(null));
	}

	public function testGetCombinedStyleStdClassReturnsEmpty()
	{
		$d = self::diff(null, null);
		$this->assertSame([], $d->exposedGetCombinedStyle(new stdClass()));
	}

	public function testGetCombinedStyleIntegerReturnsEmpty()
	{
		$d = self::diff(null, null);
		$this->assertSame([], $d->exposedGetCombinedStyle(42));
	}

	public function testGetCombinedStyleStringReturnsEmpty()
	{
		$d = self::diff(null, null);
		$this->assertSame([], $d->exposedGetCombinedStyle('color:red'));
	}

	public function testGetCombinedStyleEmptyTStyleReturnsEmpty()
	{
		$d = self::diff(null, null);
		$this->assertSame([], $d->exposedGetCombinedStyle(new TStyle()));
	}

	public function testGetCombinedStyleReturnsNamedFields()
	{
		$s = self::style(['color' => 'blue', 'width' => '100px']);
		$d = self::diff($s, null);
		$combined = $d->exposedGetCombinedStyle($s);
		$this->assertSame('blue', $combined['color']);
		$this->assertSame('100px', $combined['width']);
	}

	public function testGetCombinedStyleCustomStyleOnlyReturnsParsedValues()
	{
		$s = self::style([], null, 'margin:0;padding:5px');
		$d = self::diff($s, null);
		$combined = $d->exposedGetCombinedStyle($s);
		$this->assertSame('0', $combined['margin']);
		$this->assertSame('5px', $combined['padding']);
	}

	public function testGetCombinedStyleCustomStyleOverridesNamedFields()
	{
		// array_merge(fields, parsedCustom): custom wins on same key.
		$s = self::style(['color' => 'blue'], null, 'color:red');
		$d = self::diff($s, null);
		$this->assertSame('red', $d->exposedGetCombinedStyle($s)['color']);
	}

	public function testGetCombinedStyleFontPropertiesIncluded()
	{
		$s = self::styleWithFont([], true);
		$d = self::diff($s, null);
		$combined = $d->exposedGetCombinedStyle($s);
		$this->assertArrayHasKey('font-weight', $combined);
		$this->assertSame('bold', $combined['font-weight']);
	}

	public function testGetCombinedStyleFontOverridesCustomAndFields()
	{
		// Font is merged last, so it wins over both named fields and custom style.
		$s = self::style(['font-weight' => 'normal'], null, 'font-weight:lighter');
		$s->getFont()->setBold(true);
		$d = self::diff($s, null);
		$this->assertSame('bold', $d->exposedGetCombinedStyle($s)['font-weight']);
	}

	public function testGetCombinedStyleNoFontWhenFontNotSet()
	{
		$s = self::style(['color' => 'red']);
		// No font set → getHasFont() is false → no font keys in result.
		$d = self::diff($s, null);
		$combined = $d->exposedGetCombinedStyle($s);
		$this->assertArrayNotHasKey('font-weight', $combined);
		$this->assertArrayNotHasKey('font-size', $combined);
	}

	public function testGetCombinedStyleAllThreeSourcesMerged()
	{
		$s = self::style(['width' => '100px'], null, 'margin:0');
		$s->getFont()->setSize('14px');
		$d = self::diff($s, null);
		$combined = $d->exposedGetCombinedStyle($s);
		$this->assertArrayHasKey('width', $combined);
		$this->assertArrayHasKey('margin', $combined);
		$this->assertArrayHasKey('font-size', $combined);
	}

	// =========================================================================
	// Section 3 — getCssClassDiff
	// =========================================================================

	public function testGetCssClassDiffOldNullNewHasNamedClass()
	{
		$d = self::diff(self::style([], 'btn'), null);
		$this->assertSame('btn', $d->exposedGetCssClassDiff());
	}

	public function testGetCssClassDiffOldNullNewHasExplicitlyEmptyClass()
	{
		// setCssClass('') sets _class='', hasCssClass() returns true → diff returns ''.
		$d = self::diff(self::style([], ''), null);
		$this->assertSame('', $d->exposedGetCssClassDiff());
	}

	public function testGetCssClassDiffOldNullNewClassNeverSet()
	{
		// hasCssClass() is false when _class is null → no diff.
		$d = self::diff(self::style(), null);
		$this->assertNull($d->exposedGetCssClassDiff());
	}

	public function testGetCssClassDiffBothSameClass()
	{
		$d = self::diff(self::style([], 'btn'), self::style([], 'btn'));
		$this->assertNull($d->exposedGetCssClassDiff());
	}

	public function testGetCssClassDiffBothClassNeverSet()
	{
		// Both getCssClass() return '' → no change.
		$d = self::diff(self::style(), self::style());
		$this->assertNull($d->exposedGetCssClassDiff());
	}

	public function testGetCssClassDiffClassChanged()
	{
		$d = self::diff(self::style([], 'new'), self::style([], 'old'));
		$this->assertSame('new', $d->exposedGetCssClassDiff());
	}

	public function testGetCssClassDiffClassAddedWhereNoneExisted()
	{
		// Old style has no class; new style has class.
		$old = self::style(); // _class = null, getCssClass() = ''
		$new = self::style([], 'btn');
		$d = self::diff($new, $old);
		$this->assertSame('btn', $d->exposedGetCssClassDiff());
	}

	public function testGetCssClassDiffClassClearedToNull()
	{
		// Old had 'btn'; new never set a class → getCssClass() changes from 'btn' to ''.
		$old = self::style([], 'btn');
		$new = self::style(); // _class = null, getCssClass() = ''
		$d = self::diff($new, $old);
		$this->assertSame('', $d->exposedGetCssClassDiff());
	}

	public function testGetCssClassDiffClassExplicitlySetToEmpty()
	{
		// Old had 'btn'; new explicitly sets '' → still a change.
		$old = self::style([], 'btn');
		$new = self::style([], '');
		$d = self::diff($new, $old);
		$this->assertSame('', $d->exposedGetCssClassDiff());
	}

	// =========================================================================
	// Section 4 — getStyleDiff: additions and changes
	// (These tests pass with both the old and the new implementation.)
	// =========================================================================

	public function testGetStyleDiffNullWhenBothEmpty()
	{
		$d = self::diff(self::style(), self::style());
		$this->assertNull($d->exposedGetStyleDiff());
	}

	public function testGetStyleDiffNullWhenBothOldAndNewAreNull()
	{
		$d = self::diff(null, null);
		$this->assertNull($d->exposedGetStyleDiff());
	}

	public function testGetStyleDiffNullWhenOldIsNull()
	{
		// No old state means no removals; new is empty → nothing to report.
		$d = self::diff(self::style(), null);
		$this->assertNull($d->exposedGetStyleDiff());
	}

	public function testGetStyleDiffNullWhenPropertiesUnchanged()
	{
		$s = self::style(['color' => 'red', 'width' => '100px']);
		$d = self::diff(clone $s, clone $s);
		$this->assertNull($d->exposedGetStyleDiff());
	}

	public function testGetStyleDiffDetectsSingleAddedProperty()
	{
		$old = self::style([]);
		$new = self::style(['color' => 'red']);
		$d = self::diff($new, $old);
		$result = $d->exposedGetStyleDiff();
		$this->assertIsArray($result);
		$this->assertSame('red', $result['color']);
		$this->assertCount(1, $result);
	}

	public function testGetStyleDiffDetectsMultipleAddedProperties()
	{
		$old = self::style([]);
		$new = self::style(['color' => 'red', 'width' => '100px', 'margin' => '0']);
		$d = self::diff($new, $old);
		$result = $d->exposedGetStyleDiff();
		$this->assertIsArray($result);
		$this->assertCount(3, $result);
	}

	public function testGetStyleDiffDetectsChangedProperty()
	{
		$old = self::style(['color' => 'red']);
		$new = self::style(['color' => 'blue']);
		$d = self::diff($new, $old);
		$result = $d->exposedGetStyleDiff();
		$this->assertIsArray($result);
		$this->assertSame('blue', $result['color']);
	}

	public function testGetStyleDiffOnlyReportsActuallyChangedProperties()
	{
		$old = self::style(['color' => 'red', 'width' => '100px']);
		$new = self::style(['color' => 'blue', 'width' => '100px']); // width unchanged
		$d = self::diff($new, $old);
		$result = $d->exposedGetStyleDiff();
		$this->assertIsArray($result);
		$this->assertArrayHasKey('color', $result);
		$this->assertArrayNotHasKey('width', $result);
	}

	public function testGetStyleDiffDetectsPropertyAddedViaCustomStyle()
	{
		$old = self::style([]);
		$new = self::style([], null, 'border:1px solid black');
		$d = self::diff($new, $old);
		$result = $d->exposedGetStyleDiff();
		$this->assertIsArray($result);
		$this->assertArrayHasKey('border', $result);
	}

	public function testGetStyleDiffDetectsPropertyAddedViaFont()
	{
		$old = self::style([]);
		$new = self::styleWithFont([], true); // adds font-weight:bold
		$d = self::diff($new, $old);
		$result = $d->exposedGetStyleDiff();
		$this->assertIsArray($result);
		$this->assertArrayHasKey('font-weight', $result);
		$this->assertSame('bold', $result['font-weight']);
	}

	// =========================================================================
	// Section 5 — getStyleDiff: property REMOVALS
	//
	// Every test in this section FAILS with the old implementation that used
	// only array_diff_assoc($new, $old), because that function only returns
	// keys present in $new but absent/different in $old.  When a property is
	// removed entirely from $new the diff is empty and the test either fails on
	// assertIsArray($result) or on the expected '' value.
	//
	// @group regression_ticket622
	// =========================================================================

	/**
	 * Core Ticket 622 regression: clearing display:none via setDisplayStyle('Dynamic')
	 * removes 'display' from _fields.  getStyleDiff() must return ['display' => '']
	 * so jQuery .css({'display':''}) removes the inline property from the DOM.
	 *
	 * Old code: array_diff_assoc([], ['display'=>'none']) === [] → returns null → FAIL
	 * New code: detects removal → returns ['display' => ''] → PASS
	 *
	 * @group regression_ticket622
	 */
	public function testGetStyleDiffRemovedDisplayNoneReturnsEmptyString()
	{
		$old = self::style(['display' => 'none']);
		$new = self::style([]); // setDisplayStyle('Dynamic') cleared _fields
		$d = self::diff($new, $old);
		$result = $d->exposedGetStyleDiff();
		$this->assertIsArray($result, 'old code returns null; new code must return array containing removed properties as empty strings (Ticket 622)');
		$this->assertArrayHasKey('display', $result, 'removed property "display" must appear in diff array');
		$this->assertSame('', $result['display'], 'removed property must be empty string so jQuery .css() removes it from the DOM');
	}

	/**
	 * @group regression_ticket622
	 */
	public function testGetStyleDiffRemovedVisibilityHiddenReturnsEmptyString()
	{
		$old = self::style(['visibility' => 'hidden']);
		$new = self::style([]);
		$d = self::diff($new, $old);
		$result = $d->exposedGetStyleDiff();
		$this->assertIsArray($result, 'old code returns null; new code must return array containing removed properties as empty strings');
		$this->assertArrayHasKey('visibility', $result, 'removed property "visibility" must appear in diff array');
		$this->assertSame('', $result['visibility'], 'removed property must be empty string (jQuery removal signal)');
	}

	/**
	 * @group regression_ticket622
	 */
	public function testGetStyleDiffRemovedVisibilityVisibleReturnsEmptyString()
	{
		$old = self::style(['visibility' => 'visible']);
		$new = self::style([]);
		$d = self::diff($new, $old);
		$result = $d->exposedGetStyleDiff();
		$this->assertIsArray($result, 'old code returns null; new code must return array containing removed properties as empty strings');
		$this->assertArrayHasKey('visibility', $result, 'removed property "visibility" must appear in diff array');
		$this->assertSame('', $result['visibility'], 'removed property must be empty string (jQuery removal signal)');
	}

	/**
	 * @group regression_ticket622
	 */
	public function testGetStyleDiffAllPropertiesRemovedReturnsAllAsEmpty()
	{
		$old = self::style(['display' => 'none', 'color' => 'red', 'margin' => '0']);
		$new = self::style([]);
		$d = self::diff($new, $old);
		$result = $d->exposedGetStyleDiff();
		$this->assertIsArray($result, 'old code returns null; new code must return array containing all removed properties as empty strings');
		$this->assertArrayHasKey('display', $result, 'removed property "display" must appear in diff array');
		$this->assertSame('', $result['display'], 'removed "display" must be empty string (jQuery removal signal)');
		$this->assertArrayHasKey('color', $result, 'removed property "color" must appear in diff array');
		$this->assertSame('', $result['color'], 'removed "color" must be empty string (jQuery removal signal)');
		$this->assertArrayHasKey('margin', $result, 'removed property "margin" must appear in diff array');
		$this->assertSame('', $result['margin'], 'removed "margin" must be empty string (jQuery removal signal)');
		$this->assertCount(3, $result, 'diff must contain exactly the 3 removed properties');
	}

	/**
	 * @group regression_ticket622
	 */
	public function testGetStyleDiffMixedAdditionAndRemoval()
	{
		// Switching display style: drop display:none, add color:blue.
		$old = self::style(['display' => 'none']);
		$new = self::style(['color' => 'blue']);
		$d = self::diff($new, $old);
		$result = $d->exposedGetStyleDiff();
		$this->assertIsArray($result, 'old code misses removals; new code must return array with both the added and removed properties');
		$this->assertArrayHasKey('color', $result, 'added property "color" must appear in diff array');
		$this->assertSame('blue', $result['color'], 'added property must carry its new value');
		$this->assertArrayHasKey('display', $result, 'removed property "display" must appear in diff array alongside the addition');
		$this->assertSame('', $result['display'], 'removed property must be empty string (jQuery removal signal)');
		$this->assertCount(2, $result, 'diff must contain exactly the 1 addition and 1 removal');
	}

	/**
	 * @group regression_ticket622
	 */
	public function testGetStyleDiffUnchangedPropertiesNotPresentInResult()
	{
		// 'width' is unchanged; 'display' is removed; 'color' is added.
		$old = self::style(['display' => 'none', 'width' => '100px']);
		$new = self::style(['color' => 'blue', 'width' => '100px']);
		$d = self::diff($new, $old);
		$result = $d->exposedGetStyleDiff();
		$this->assertIsArray($result, 'old code misses removals; new code must return array with changed properties only');
		$this->assertArrayNotHasKey('width', $result, 'unchanged property "width" must not appear in diff');
		$this->assertArrayHasKey('display', $result, 'removed property "display" must appear in diff array');
		$this->assertSame('', $result['display'], 'removed property must be empty string (jQuery removal signal)');
		$this->assertArrayHasKey('color', $result, 'added property "color" must appear in diff array');
		$this->assertSame('blue', $result['color'], 'added property must carry its new value');
	}

	/**
	 * @group regression_ticket622
	 */
	public function testGetStyleDiffRemovedPropertyHasEmptyStringNotOldValue()
	{
		$old = self::style(['color' => 'red']);
		$new = self::style([]);
		$d = self::diff($new, $old);
		$result = $d->exposedGetStyleDiff();
		// The value must be '' (jQuery remove signal), never the old value 'red'.
		$this->assertIsArray($result, 'old code returns null; new code must return array containing removed properties as empty strings');
		$this->assertArrayHasKey('color', $result, 'removed property "color" must appear in diff array');
		$this->assertSame('', $result['color'], 'removed property must be empty string (jQuery removal signal), not the old value');
		$this->assertNotSame('red', $result['color'], 'removed property must never carry the old value "red"');
	}

	/**
	 * Property present in old via customStyle but absent from new.
	 *
	 * @group regression_ticket622
	 */
	public function testGetStyleDiffRemovedCustomStylePropertyReturnsEmptyString()
	{
		$old = self::style([], null, 'border:1px solid black');
		$new = self::style([]); // customStyle cleared
		$d = self::diff($new, $old);
		$result = $d->exposedGetStyleDiff();
		$this->assertIsArray($result, 'old code returns null; new code must detect removal of customStyle properties');
		$this->assertArrayHasKey('border', $result, 'removed customStyle property "border" must appear in diff array');
		$this->assertSame('', $result['border'], 'removed customStyle property must be empty string (jQuery removal signal)');
	}

	/**
	 * Property present in old via font but absent from new (font cleared).
	 *
	 * @group regression_ticket622
	 */
	public function testGetStyleDiffRemovedFontPropertyReturnsEmptyString()
	{
		$old = self::styleWithFont([], true); // font-weight:bold present
		$new = self::style([]); // no font
		$d = self::diff($new, $old);
		$result = $d->exposedGetStyleDiff();
		$this->assertIsArray($result, 'old code returns null; new code must detect removal of font-derived properties');
		$this->assertArrayHasKey('font-weight', $result, 'removed font property "font-weight" must appear in diff array');
		$this->assertSame('', $result['font-weight'], 'removed font property must be empty string (jQuery removal signal)');
	}

	/**
	 * When $new is not a TStyle, getCombinedStyle returns [].
	 * All old properties count as removals.
	 *
	 * @group regression_ticket622
	 */
	public function testGetStyleDiffNewIsNonTStyleAllOldPropertiesAreRemovals()
	{
		$old = self::style(['display' => 'none', 'color' => 'red']);
		$d = self::diff(new stdClass(), $old);
		$result = $d->exposedGetStyleDiff();
		$this->assertIsArray($result, 'old code returns null; new code must return array treating all old properties as removals when new is not a TStyle');
		$this->assertArrayHasKey('display', $result, 'old property "display" must appear in diff as a removal');
		$this->assertSame('', $result['display'], 'removal of "display" must be empty string (jQuery removal signal)');
		$this->assertArrayHasKey('color', $result, 'old property "color" must appear in diff as a removal');
		$this->assertSame('', $result['color'], 'removal of "color" must be empty string (jQuery removal signal)');
	}

	// =========================================================================
	// Section 6 — getDifference: full integration
	// =========================================================================

	public function testGetDifferenceNewNullReturnsNullObject()
	{
		$null = $this->nullObject();
		$d = new TStyleDiff(null, self::style(['color' => 'red']), $null);
		$this->assertSame($null, $d->getDifference());
	}

	public function testGetDifferenceNoChangesReturnsNullObject()
	{
		$null = $this->nullObject();
		$s = self::style(['color' => 'red'], 'btn');
		$d = new TStyleDiff(clone $s, clone $s, $null);
		$this->assertSame($null, $d->getDifference());
	}

	public function testGetDifferenceBothNullReturnsNullObject()
	{
		$null = $this->nullObject();
		// _new is null → always returns _null regardless of _old.
		$d = new TStyleDiff(null, null, $null);
		$this->assertSame($null, $d->getDifference());
	}

	public function testGetDifferenceStylePropertyChangedReturnsResult()
	{
		$null = $this->nullObject();
		$old = self::style(['color' => 'red']);
		$new = self::style(['color' => 'blue']);
		$d = new TStyleDiff($new, $old, $null);
		$result = $d->getDifference();
		$this->assertNotSame($null, $result);
		$this->assertIsArray($result['Style']);
		$this->assertSame('blue', $result['Style']['color']);
	}

	public function testGetDifferenceCssClassChangedReturnsResult()
	{
		$null = $this->nullObject();
		$old = self::style([], 'old');
		$new = self::style([], 'new');
		$d = new TStyleDiff($new, $old, $null);
		$result = $d->getDifference();
		$this->assertNotSame($null, $result);
		$this->assertSame('new', $result['CssClass']);
	}

	public function testGetDifferenceBothStyleAndCssClassChangedReturnsResult()
	{
		$null = $this->nullObject();
		$old = self::style(['color' => 'red'], 'old');
		$new = self::style(['color' => 'blue'], 'new');
		$d = new TStyleDiff($new, $old, $null);
		$result = $d->getDifference();
		$this->assertNotSame($null, $result);
		$this->assertSame('new', $result['CssClass']);
		$this->assertSame('blue', $result['Style']['color']);
	}

	public function testGetDifferenceCssClassNullInResultWhenOnlyStyleChanged()
	{
		$null = $this->nullObject();
		$old = self::style(['color' => 'red'], 'btn');
		$new = self::style(['color' => 'blue'], 'btn'); // class unchanged
		$d = new TStyleDiff($new, $old, $null);
		$result = $d->getDifference();
		$this->assertNotSame($null, $result);
		$this->assertNull($result['CssClass']);
		$this->assertIsArray($result['Style']);
	}

	public function testGetDifferenceStyleNullInResultWhenOnlyCssClassChanged()
	{
		$null = $this->nullObject();
		$old = self::style(['color' => 'red'], 'old');
		$new = self::style(['color' => 'red'], 'new'); // style unchanged
		$d = new TStyleDiff($new, $old, $null);
		$result = $d->getDifference();
		$this->assertNotSame($null, $result);
		$this->assertSame('new', $result['CssClass']);
		$this->assertNull($result['Style']);
	}

	public function testGetDifferenceOldNullNewHasClassReturnsResult()
	{
		$null = $this->nullObject();
		$d = new TStyleDiff(self::style([], 'btn'), null, $null);
		$result = $d->getDifference();
		$this->assertNotSame($null, $result);
		$this->assertSame('btn', $result['CssClass']);
	}

	public function testGetDifferenceOldNullNewEmptyReturnsNullObject()
	{
		// No old state and no changes in new → nothing to report.
		$null = $this->nullObject();
		$d = new TStyleDiff(self::style(), null, $null);
		$this->assertSame($null, $d->getDifference());
	}

	public function testGetDifferenceOldNullNewHasFieldsReturnsResult()
	{
		// Starting fresh: every field in new counts as an addition.
		$null = $this->nullObject();
		$new = self::style(['color' => 'red']);
		$d = new TStyleDiff($new, null, $null);
		$result = $d->getDifference();
		$this->assertNotSame($null, $result);
		$this->assertIsArray($result['Style']);
		$this->assertSame('red', $result['Style']['color']);
	}

	/**
	 * Core Ticket 622 integration regression.
	 *
	 * Old code: getStyleDiff returns null → getDifference returns _null →
	 * updateStyle is never called → DOM retains display:none.
	 *
	 * New code: getStyleDiff returns ['display' => ''] → getDifference returns
	 * a non-null array → updateStyle passes '' to jQuery → DOM cleared.
	 *
	 * @group regression_ticket622
	 */
	public function testGetDifferenceStylePropertyRemovedReturnsNonNullObject()
	{
		$null = $this->nullObject();
		$old = self::style(['display' => 'none']);
		$new = self::style([]);
		$d = new TStyleDiff($new, $old, $null);
		$this->assertNotSame($null, $d->getDifference(), 'old code returns _null when only removals exist; new code must return a non-null result so the DOM update is sent');
	}

	/**
	 * @group regression_ticket622
	 */
	public function testGetDifferenceStyleArrayContainsEmptyStringForRemovedProperty()
	{
		$null = $this->nullObject();
		$old = self::style(['display' => 'none']);
		$new = self::style([]);
		$d = new TStyleDiff($new, $old, $null);
		$result = $d->getDifference();
		$this->assertIsArray($result, 'old code returns _null object; new code must return an array result when style properties are removed');
		$this->assertIsArray($result['Style'], 'Style key must be an array containing the removed properties');
		$this->assertArrayHasKey('display', $result['Style'], 'removed property "display" must appear in Style diff array');
		$this->assertSame('', $result['Style']['display'], 'removed property must be empty string so jQuery .css() removes it from the DOM');
	}

	/**
	 * @group regression_ticket622
	 */
	public function testGetDifferenceMultiplePropertiesRemovedAllPresentAsEmpty()
	{
		$null = $this->nullObject();
		$old = self::style(['display' => 'none', 'visibility' => 'hidden', 'color' => 'red']);
		$new = self::style([]);
		$d = new TStyleDiff($new, $old, $null);
		$result = $d->getDifference();
		$this->assertNotSame($null, $result, 'old code returns _null object; new code must return a non-null result when multiple style properties are removed');
		$this->assertIsArray($result, 'getDifference must return an array result, not a null-object, when style properties are removed');
		$this->assertIsArray($result['Style'], 'Style key must be an array containing all removed properties');
		$this->assertArrayHasKey('display', $result['Style'], 'removed property "display" must appear in Style diff array');
		$this->assertSame('', $result['Style']['display'], 'removed "display" must be empty string (jQuery removal signal)');
		$this->assertArrayHasKey('visibility', $result['Style'], 'removed property "visibility" must appear in Style diff array');
		$this->assertSame('', $result['Style']['visibility'], 'removed "visibility" must be empty string (jQuery removal signal)');
		$this->assertArrayHasKey('color', $result['Style'], 'removed property "color" must appear in Style diff array');
		$this->assertSame('', $result['Style']['color'], 'removed "color" must be empty string (jQuery removal signal)');
	}

	/**
	 * @group regression_ticket622
	 */
	public function testGetDifferenceMixedRemovalAndAdditionCapturesBoth()
	{
		$null = $this->nullObject();
		$old = self::style(['display' => 'none']);
		$new = self::style(['color' => 'blue']);
		$d = new TStyleDiff($new, $old, $null);
		$result = $d->getDifference();
		$this->assertNotSame($null, $result, 'getDifference must return a non-null result when properties are both removed and added');
		$this->assertIsArray($result, 'getDifference must return an array result for mixed removal and addition');
		$this->assertIsArray($result['Style'], 'Style key must be an array containing both the removal and the addition');
		$this->assertArrayHasKey('display', $result['Style'], 'removed property "display" must appear in Style diff array');
		$this->assertSame('', $result['Style']['display'], 'removed "display" must be empty string (jQuery removal signal)');
		$this->assertArrayHasKey('color', $result['Style'], 'added property "color" must appear in Style diff array');
		$this->assertSame('blue', $result['Style']['color'], 'added property must carry its new value');
	}
}
