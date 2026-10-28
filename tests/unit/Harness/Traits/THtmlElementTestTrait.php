<?php

/**
 * THtmlElementTestTrait class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Traits;

use Prado\Web\UI\WebControls\THtmlElement;

/**
 * THtmlElementTestTrait tests the tag of a {@see THtmlElement} subclass.
 *
 * The using test case implements {@see createElement()} and {@see defaultTagName()}
 * and uses {@see TWebControlRenderTrait}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait THtmlElementTestTrait
{
	/**
	 * @return THtmlElement a new instance of the element under test.
	 */
	abstract protected function createElement(): THtmlElement;

	/**
	 * @return string the default tag of the element under test.
	 */
	abstract protected function defaultTagName(): string;

	public function testExtendsTHtmlElement()
	{
		$this->assertInstanceOf(THtmlElement::class, $this->createElement());
	}

	public function testDefaultTagName()
	{
		$control = $this->createElement();
		$this->assertSame($this->defaultTagName(), $control->getDefaultTagName());
		$this->assertSame($this->defaultTagName(), $control->getTagName());
		$this->assertFalse($control->getIsMutated());
	}

	public function testRendersTagWithChildContent()
	{
		$control = $this->createElement();
		$control->getControls()->add('content');
		$tag = $this->defaultTagName();
		$this->assertMatchesRegularExpression("#^<{$tag}(\\s[^>]*)?>content</{$tag}>$#", $this->render($control));
	}

	public function testTagNameOverridesDefault()
	{
		$control = $this->createElement();
		$control->setTagName('span');
		$this->assertTrue($control->getIsMutated());
		$this->assertMatchesRegularExpression('#^<span(\s[^>]*)?></span>$#', $this->render($control));
	}
}
