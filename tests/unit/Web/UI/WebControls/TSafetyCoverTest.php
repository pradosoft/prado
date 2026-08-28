<?php

use Prado\Web\UI\ITemplate;
use Prado\Web\UI\WebControls\TContentDirection;
use Prado\Web\UI\WebControls\TSafetyCover;
use Prado\Web\UI\WebControls\TSafetyCoverDirection;
use Prado\Web\UI\WebControls\TSafetyCoverEffect;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TPanel;
use PHPUnit\Framework\TestCase;

/**
 * A minimal template that adds a marker string to its parent.
 */
class TSafetyCoverTestTemplate implements ITemplate
{
	public function instantiateIn($parent)
	{
		$parent->getControls()->add('OVERLAY-CONTENT');
	}

	public function getIncludedFiles()
	{
		return [];
	}
}

class TSafetyCoverTest extends TestCase
{
	use TWebControlRenderTrait;

	private function newControl(): TSafetyCover
	{
		$control = new TSafetyCover();
		$control->setID('guard');
		return $control;
	}

	public function testExtendsPanel()
	{
		$this->assertInstanceOf(TPanel::class, new TSafetyCover());
	}

	// --- properties ---

	public function testOverlayTemplateDefaultNull()
	{
		$this->assertNull($this->newControl()->getOverlayTemplate());
	}

	public function testSetOverlayTemplate()
	{
		$control = $this->newControl();
		$template = new TSafetyCoverTestTemplate();
		$control->setOverlayTemplate($template);
		$this->assertSame($template, $control->getOverlayTemplate());
	}

	public function testOverlayColorDefaultEmpty()
	{
		$this->assertSame('', $this->newControl()->getOverlayColor());
	}

	public function testSetOverlayColor()
	{
		$control = $this->newControl();
		$control->setOverlayColor('#c00');
		$this->assertSame('#c00', $control->getOverlayColor());
	}

	public function testOverlayCssClassDefaultEmpty()
	{
		$this->assertSame('', $this->newControl()->getOverlayCssClass());
	}

	public function testSetOverlayCssClass()
	{
		$control = $this->newControl();
		$control->setOverlayCssClass('danger-face');
		$this->assertSame('danger-face', $control->getOverlayCssClass());
	}

	public function testFaceClassPlainWhenNoOverlayCssClass()
	{
		$output = $this->render($this->newControl());
		$this->assertStringContainsString('id="guard_face" class="safety-cover-face"', $output);
	}

	public function testOverlayCssClassAddedToFaceClass()
	{
		$control = $this->newControl();
		$control->setOverlayCssClass('danger-face fancy');
		$output = $this->render($control);
		$this->assertStringContainsString('id="guard_face" class="safety-cover-face danger-face fancy"', $output);
	}

	public function testOpenDelayDefault()
	{
		$this->assertSame(800, $this->newControl()->getOpenDelay());
	}

	public function testSetOpenDelay()
	{
		$control = $this->newControl();
		$control->setOpenDelay('500');
		$this->assertSame(500, $control->getOpenDelay());
	}

	public function testAutoCloseDelayDefault()
	{
		$this->assertSame(6000, $this->newControl()->getAutoCloseDelay());
	}

	public function testSetAutoCloseDelay()
	{
		$control = $this->newControl();
		$control->setAutoCloseDelay('10000');
		$this->assertSame(10000, $control->getAutoCloseDelay());
	}

	public function testKeepOpenWhileActiveDefaultFalse()
	{
		$this->assertFalse($this->newControl()->getKeepOpenWhileActive());
	}

	public function testSetKeepOpenWhileActive()
	{
		$control = $this->newControl();
		$control->setKeepOpenWhileActive(true);
		$this->assertTrue($control->getKeepOpenWhileActive());
	}

	public function testMouseOutTimeoutDefault()
	{
		$this->assertSame(1000, $this->newControl()->getMouseOutTimeout());
	}

	public function testSetMouseOutTimeout()
	{
		$control = $this->newControl();
		$control->setMouseOutTimeout('2500');
		$this->assertSame(2500, $control->getMouseOutTimeout());
	}

	public function testAnimationDurationDefault()
	{
		$this->assertSame(250, $this->newControl()->getAnimationDuration());
	}

	public function testSetAnimationDuration()
	{
		$control = $this->newControl();
		$control->setAnimationDuration('400');
		$this->assertSame(400, $control->getAnimationDuration());
	}

	public function testCssUrlDefault()
	{
		$this->assertSame('default', $this->newControl()->getCssUrl());
	}

	public function testSetCssUrl()
	{
		$control = $this->newControl();
		$control->setCssUrl('');
		$this->assertSame('', $control->getCssUrl());
	}

	// --- rendering ---

	public function testRendersStructure()
	{
		$output = $this->render($this->newControl());
		$this->assertStringContainsString('id="guard"', $output);
		$this->assertMatchesRegularExpression('/id="guard"[^>]*class="safety-cover /', $output);
		$this->assertStringContainsString('id="guard_slider"', $output);
		$this->assertStringContainsString('class="safety-cover-slider"', $output);
		$this->assertStringContainsString('id="guard_overlay"', $output);
		$this->assertStringContainsString('class="safety-cover-overlay"', $output);
		$this->assertStringContainsString('id="guard_face"', $output);
		$this->assertStringContainsString('class="safety-cover-face"', $output);
		$this->assertStringContainsString('id="guard_content"', $output);
		$this->assertStringContainsString('class="safety-cover-content"', $output);
	}

	public function testAnimationDurationRendersCustomProperty()
	{
		$control = $this->newControl();
		$control->setAnimationDuration(400);
		$output = $this->render($control);
		$this->assertStringContainsString('--safety-cover-animation-duration:400ms', $output);
	}

	// --- accessibility ---

	public function testAccessibleLabelDefaultEmpty()
	{
		$this->assertSame('', $this->newControl()->getAccessibleLabel());
	}

	public function testGuardLabelledByFaceWhenNoAccessibleLabel()
	{
		// Default: the guard takes its accessible name from the visible face, so
		// the name matches the visible label (WCAG 2.5.3), not a fixed string.
		$output = $this->render($this->newControl());
		$this->assertMatchesRegularExpression('/id="guard_overlay"[^>]*aria-labelledby="guard_face"/', $output);
	}

	public function testAccessibleLabelOverridesWithAriaLabel()
	{
		$control = $this->newControl();
		$control->setAccessibleLabel('Unlock delete');
		$output = $this->render($control);
		$this->assertMatchesRegularExpression('/id="guard_overlay"[^>]*aria-label="Unlock delete"/', $output);
		$this->assertDoesNotMatchRegularExpression('/id="guard_overlay"[^>]*aria-labelledby=/', $output);
	}

	public function testSetAccessibleLabel()
	{
		$control = $this->newControl();
		$control->setAccessibleLabel('Unlock delete');
		$this->assertSame('Unlock delete', $control->getAccessibleLabel());
	}

	public function testGuardRendersAsAccessibleButton()
	{
		$output = $this->render($this->newControl());
		$this->assertMatchesRegularExpression('/id="guard_overlay"[^>]*role="button"/', $output);
		$this->assertMatchesRegularExpression('/id="guard_overlay"[^>]*tabindex="0"/', $output);
		$this->assertMatchesRegularExpression('/id="guard_overlay"[^>]*aria-expanded="false"/', $output);
		$this->assertMatchesRegularExpression('/id="guard_overlay"[^>]*aria-controls="guard_content"/', $output);
	}

	public function testAccessibleLabelRendersAsAriaLabel()
	{
		$control = $this->newControl();
		$control->setAccessibleLabel('Unlock delete');
		$output = $this->render($control);
		$this->assertMatchesRegularExpression('/id="guard_overlay"[^>]*aria-label="Unlock delete"/', $output);
	}

	public function testAccessibleLabelIsHtmlEncoded()
	{
		$control = $this->newControl();
		$control->setAccessibleLabel('a" onmouseover="alert(1)');
		$output = $this->render($control);
		$this->assertStringNotContainsString('onmouseover="alert(1)"', $output);
	}

	public function testCssClassMergedWithControlClass()
	{
		$control = $this->newControl();
		$control->setCssClass('custom');
		$output = $this->render($control);
		// Framework classes lead; the author class follows.
		$this->assertStringContainsString('class="safety-cover safety-cover-slide safety-cover-up custom"', $output);
	}

	public function testCssClassNotDuplicatedOnRepeatedRender()
	{
		$control = $this->newControl();
		$this->render($control);
		$output = $this->render($control);
		// The exact root class proves slide is present once; safety-cover-up is a
		// collision-free token (safety-cover-slide is a prefix of the slider class).
		$this->assertStringContainsString('class="safety-cover safety-cover-slide safety-cover-up"', $output);
		$this->assertSame(1, substr_count($output, 'safety-cover-up'));
	}

	public function testCssClassRefreshesWhenEffectChangesBetweenRenders()
	{
		$control = $this->newControl();
		$this->render($control);
		$control->setOverlayEffect(TSafetyCoverEffect::None);
		$output = $this->render($control);
		// The exact root class shows the stale slide/direction classes are stripped;
		// safety-cover-up is checked directly since it collides with nothing.
		$this->assertStringContainsString('class="safety-cover safety-cover-none"', $output);
		$this->assertStringNotContainsString('safety-cover-up', $output);
	}

	public function testAuthorClassStartingWithSafetyCoverIsPreserved()
	{
		// An author theme class that happens to share the framework prefix must
		// survive; only literal duplicates of the managed classes are dropped.
		$control = $this->newControl();
		$control->setCssClass('safety-cover-dark');
		$output = $this->render($control);
		$this->assertStringContainsString('class="safety-cover safety-cover-slide safety-cover-up safety-cover-dark"', $output);
	}

	public function testCssClassViewStateNotMutatedByRender()
	{
		// The framework classes are composed only for output; getCssClass() keeps
		// exactly what the author set, before and after rendering.
		$control = $this->newControl();
		$control->setCssClass('custom');
		$this->render($control);
		$this->assertSame('custom', $control->getCssClass());

		$blank = $this->newControl();
		$this->render($blank);
		$this->assertSame('', $blank->getCssClass());
	}

	// --- OverlayColor rendering ---

	public function testOverlayColorNotRenderedByDefault()
	{
		$output = $this->render($this->newControl());
		$this->assertStringNotContainsString('background-color', $output);
	}

	public function testOverlayColorRendersInlineStyleOnFace()
	{
		// The color renders on the visible face, not the transparent guard.
		$control = $this->newControl();
		$control->setOverlayColor('#c00');
		$output = $this->render($control);
		$this->assertMatchesRegularExpression(
			'/id="guard_face"[^>]*background-color:#c00/',
			$output
		);
	}

	public function testOverlayColorAcceptsRgba()
	{
		$control = $this->newControl();
		$control->setOverlayColor('rgba(0,0,255,0.5)');
		$output = $this->render($control);
		$this->assertStringContainsString('background-color:rgba(0,0,255,0.5)', $output);
	}

	public function testOverlayColorIsHtmlEncoded()
	{
		$control = $this->newControl();
		$control->setOverlayColor('red" onmouseover="alert(1)');
		$output = $this->render($control);
		$this->assertStringNotContainsString('onmouseover="alert(1)"', $output);
	}

	public function testOverlayColorStripsCssInjection()
	{
		// A value carrying extra declarations must not inject them into the
		// inline style; the ';' and following declaration are stripped.
		$control = $this->newControl();
		$control->setOverlayColor('red; position: fixed; inset: 0');
		$output = $this->render($control);
		$this->assertStringNotContainsString(';', substr($output, strpos($output, 'background-color'), 40));
		$this->assertStringNotContainsString('position: fixed', $output);
		$this->assertStringNotContainsString('position:fixed', $output);
	}

	public function testOverlayColorKeepsModernSyntax()
	{
		// The sanitizer must not mangle valid color syntaxes (slash, percent, var).
		$control = $this->newControl();
		$control->setOverlayColor('rgb(0 128 255 / 50%)');
		$output = $this->render($control);
		$this->assertStringContainsString('background-color:rgb(0 128 255 / 50%)', $output);
	}

	// --- content placement ---

	public function testBodyContentRendersInContentElement()
	{
		$control = $this->newControl();
		$label = new TLabel();
		$label->setText('BODY-CONTENT');
		$control->getControls()->add($label);
		$output = $this->render($control);
		$contentPos = strpos($output, 'id="guard_content"');
		$bodyPos = strpos($output, 'BODY-CONTENT');
		$this->assertNotFalse($contentPos);
		$this->assertNotFalse($bodyPos);
		$this->assertGreaterThan($contentPos, $bodyPos);
	}

	public function testOverlayTemplateRendersInFaceElement()
	{
		// The template renders inside the visible face, between the face's open tag
		// and the content element.
		$control = $this->newControl();
		$control->setOverlayTemplate(new TSafetyCoverTestTemplate());
		$output = $this->render($control);
		$facePos = strpos($output, 'id="guard_face"');
		$templatePos = strpos($output, 'OVERLAY-CONTENT');
		$contentPos = strpos($output, 'id="guard_content"');
		$this->assertNotFalse($templatePos);
		$this->assertGreaterThan($facePos, $templatePos);
		$this->assertLessThan($contentPos, $templatePos);
	}

	public function testOverlayTemplateNotRenderedInBodyContent()
	{
		$control = $this->newControl();
		$control->setOverlayTemplate(new TSafetyCoverTestTemplate());
		$output = $this->render($control);
		$this->assertSame(1, substr_count($output, 'OVERLAY-CONTENT'));
	}

	public function testOnInitInstantiatesTemplate()
	{
		$control = $this->newControl();
		$control->setOverlayTemplate(new TSafetyCoverTestTemplate());
		$control->onInit(null);
		$this->assertTrue($control->getHasControls());
	}

	public function testSetOverlayTemplateAfterInitReplacesOverlay()
	{
		$control = $this->newControl();
		$control->setOverlayTemplate(new TSafetyCoverTestTemplate());
		$control->onInit(null);
		$control->setOverlayTemplate(new TSafetyCoverTestTemplate());
		$output = $this->render($control);
		$this->assertSame(1, substr_count($output, 'OVERLAY-CONTENT'));
	}

	public function testSetOverlayTemplateNullAfterInitRemovesOverlay()
	{
		$control = $this->newControl();
		$control->setOverlayTemplate(new TSafetyCoverTestTemplate());
		$control->onInit(null);
		$control->setOverlayTemplate(null);
		$output = $this->render($control);
		$this->assertStringNotContainsString('OVERLAY-CONTENT', $output);
	}

	// --- open effect and direction ---

	public function testOverlayEffectDefaultSlide()
	{
		$this->assertSame(TSafetyCoverEffect::Slide, $this->newControl()->getOverlayEffect());
	}

	public function testSetOverlayEffect()
	{
		$control = $this->newControl();
		$control->setOverlayEffect(TSafetyCoverEffect::Collapse);
		$this->assertSame(TSafetyCoverEffect::Collapse, $control->getOverlayEffect());
	}

	public function testSetOverlayEffectInvalidThrows()
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$this->newControl()->setOverlayEffect('Dissolve');
	}

	public function testOverlayDirectionDefaultUp()
	{
		$this->assertSame(TSafetyCoverDirection::Up, $this->newControl()->getOverlayDirection());
	}

	public function testSetOverlayDirection()
	{
		$control = $this->newControl();
		$control->setOverlayDirection(TSafetyCoverDirection::Down);
		$this->assertSame(TSafetyCoverDirection::Down, $control->getOverlayDirection());
	}

	public function testSetOverlayDirectionInvalidThrows()
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$this->newControl()->setOverlayDirection('Sideways');
	}

	public function testDefaultRendersSlideUpClasses()
	{
		$output = $this->render($this->newControl());
		$this->assertStringContainsString('class="safety-cover safety-cover-slide safety-cover-up"', $output);
	}

	public function testCollapseDownRendersClasses()
	{
		$control = $this->newControl();
		$control->setOverlayEffect(TSafetyCoverEffect::Collapse);
		$control->setOverlayDirection(TSafetyCoverDirection::Down);
		$output = $this->render($control);
		$this->assertStringContainsString('class="safety-cover safety-cover-collapse safety-cover-down"', $output);
	}

	public function testNoneOmitsDirectionClass()
	{
		$control = $this->newControl();
		$control->setOverlayEffect(TSafetyCoverEffect::None);
		$output = $this->render($control);
		$this->assertStringContainsString('class="safety-cover safety-cover-none"', $output);
		$this->assertStringNotContainsString('safety-cover-up', $output);
	}

	// --- open fade (independent axis) ---

	public function testOverlayFadeDefaultFalse()
	{
		$this->assertFalse($this->newControl()->getOverlayFade());
	}

	public function testSetOverlayFade()
	{
		$control = $this->newControl();
		$control->setOverlayFade(true);
		$this->assertTrue($control->getOverlayFade());
	}

	public function testFadeClassAbsentByDefault()
	{
		$output = $this->render($this->newControl());
		$this->assertStringNotContainsString('safety-cover-fade', $output);
	}

	public function testFadeCombinesWithSlideDirection()
	{
		$control = $this->newControl();
		$control->setOverlayFade(true);
		$output = $this->render($control);
		// Fade layers on the default slide/up geometry.
		$this->assertStringContainsString('class="safety-cover safety-cover-slide safety-cover-up safety-cover-fade"', $output);
	}

	public function testFadeCombinesWithCollapse()
	{
		$control = $this->newControl();
		$control->setOverlayEffect(TSafetyCoverEffect::Collapse);
		$control->setOverlayDirection(TSafetyCoverDirection::Left);
		$control->setOverlayFade(true);
		$output = $this->render($control);
		$this->assertStringContainsString('class="safety-cover safety-cover-collapse safety-cover-left safety-cover-fade"', $output);
	}

	public function testNoneWithFadeIsPureFade()
	{
		$control = $this->newControl();
		$control->setOverlayEffect(TSafetyCoverEffect::None);
		$control->setOverlayFade(true);
		$output = $this->render($control);
		// No geometry class, no direction class, just none + fade.
		$this->assertStringContainsString('class="safety-cover safety-cover-none safety-cover-fade"', $output);
	}

	/**
	 * The full direction matrix: every direction, both content-direction settings
	 * for the logical values, and both fade states, asserting the exact rendered
	 * root class. Physical directions ignore content direction; the logical
	 * `Forward`/`Backward` resolve to `right`/`left` and flip under RightToLeft.
	 *
	 * @dataProvider directionMatrixProvider
	 */
	public function testDirectionMatrixRendersExpectedClass($direction, $contentDirection, $fade, $expected)
	{
		$control = $this->newControl();
		$control->setOverlayEffect(TSafetyCoverEffect::Slide);
		$control->setOverlayDirection($direction);
		if ($contentDirection !== null) {
			$control->setDirection($contentDirection);
		}
		$control->setOverlayFade($fade);
		$output = $this->render($control);
		$this->assertStringContainsString('class="' . $expected . '"', $output);
	}

	public static function directionMatrixProvider(): array
	{
		$base = 'safety-cover safety-cover-slide safety-cover-';
		$rows = [];
		// Physical directions: content direction is irrelevant, tested with default.
		foreach (['Up' => 'up', 'Down' => 'down', 'Left' => 'left', 'Right' => 'right'] as $dir => $cls) {
			$rows["$dir fade off"] = [constant(TSafetyCoverDirection::class . "::$dir"), null, false, $base . $cls];
			$rows["$dir fade on"] = [constant(TSafetyCoverDirection::class . "::$dir"), null, true, $base . $cls . ' safety-cover-fade'];
		}
		// Logical directions resolve through content direction (2x2).
		$logical = [
			['Forward', TContentDirection::LeftToRight, 'right'],
			['Backward', TContentDirection::LeftToRight, 'left'],
			['Forward', TContentDirection::RightToLeft, 'left'],
			['Backward', TContentDirection::RightToLeft, 'right'],
		];
		foreach ($logical as [$dir, $content, $cls]) {
			$hand = $content === TContentDirection::RightToLeft ? 'rtl' : 'ltr';
			$rows["$dir $hand fade off"] = [constant(TSafetyCoverDirection::class . "::$dir"), $content, false, $base . $cls];
			$rows["$dir $hand fade on"] = [constant(TSafetyCoverDirection::class . "::$dir"), $content, true, $base . $cls . ' safety-cover-fade'];
		}
		return $rows;
	}

	// --- pulse duration ---

	public function testPulseDurationVariableDefaultsToOpenDelay()
	{
		$output = $this->render($this->newControl());
		$this->assertStringContainsString('--safety-cover-open-delay:800ms', $output);
	}

	public function testPulseDurationVariableFollowsOpenDelay()
	{
		$control = $this->newControl();
		$control->setOpenDelay(500);
		$output = $this->render($control);
		$this->assertStringContainsString('--safety-cover-open-delay:500ms', $output);
	}

	// --- client options ---

	public function testClientOptions()
	{
		$control = $this->newControl();
		$control->setOpenDelay(400);
		$control->setAutoCloseDelay(9000);
		$control->setMouseOutTimeout(1500);
		$control->setKeepOpenWhileActive(true);
		$options = PradoUnit::invoke($control, 'getClientOptions');
		$this->assertSame('guard', $options['ID']);
		$this->assertSame(400, $options['OpenDelay']);
		$this->assertSame(9000, $options['AutoCloseDelay']);
		$this->assertSame(1500, $options['MouseOutTimeout']);
		$this->assertTrue($options['KeepOpenWhileActive']);
	}

	public function testClientClassName()
	{
		$this->assertSame('Prado.WebUI.TSafetyCover', PradoUnit::invoke($this->newControl(), 'getClientClassName'));
	}
}
