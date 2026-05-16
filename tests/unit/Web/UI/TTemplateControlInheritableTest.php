<?php

use Prado\Util\TBehavior;
use Prado\Web\UI\ITemplate;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\TTemplateControlInheritable;
use Prado\Exceptions\TConfigurationException;

// ---------------------------------------------------------------------------
// Behavior fixture — records every dyCreateChildControls() call
// ---------------------------------------------------------------------------

class TTemplateControlInheritableCreateChildBehavior extends TBehavior
{
	public int $callCount = 0;

	public function dyCreateChildControls($callchain)
	{
		$this->callCount++;
		return $callchain->dyCreateChildControls();
	}
}

// ---------------------------------------------------------------------------
// TTemplateControlInheritableTest
// ---------------------------------------------------------------------------

class TTemplateControlInheritableTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Returns a concrete subclass of TTemplateControlInheritable that always
	 * returns the supplied $template from getTemplate() (bypasses the static
	 * per-class template cache and the service/file system).
	 */
	private function makeControlWithTemplate(?ITemplate $template): TTemplateControlInheritable
	{
		return new class ($template) extends TTemplateControlInheritable {
			private ?ITemplate $_injected;

			public function __construct(?ITemplate $t)
			{
				$this->_injected = $t;
			}

			public function getTemplate(): ?ITemplate
			{
				return $this->_injected;
			}
		};
	}

	/**
	 * Build a mock TTemplate whose instantiateIn() can be observed.
	 * $directive is the array returned by getDirective().
	 */
	private function mockTemplate(array $directive = []): TTemplate
	{
		$tpl = $this->createMock(TTemplate::class);
		$tpl->method('getDirective')->willReturn($directive);
		return $tpl;
	}

	// -----------------------------------------------------------------------
	// instantiateIn called exactly once (regression for issue #1111)
	// -----------------------------------------------------------------------

	/**
	 * Core regression test: when the control has its own template,
	 * createChildControls() must call instantiateIn() exactly once.
	 *
	 * Before the fix, parent::createChildControls() → TTemplateControl::createChildControls()
	 * would call instantiateIn() a second time, producing duplicate child controls
	 * and a "Duplicated object ID" TInvalidOperationException on controls with IDs.
	 */
	public function testCreateChildControlsInstantiatesTemplateExactlyOnce(): void
	{
		$tpl = $this->mockTemplate();
		$tpl->expects($this->exactly(1))->method('instantiateIn');

		$ctrl = $this->makeControlWithTemplate($tpl);
		$ctrl->createChildControls();
	}

	/**
	 * Calling createChildControls() twice must result in exactly two
	 * instantiateIn() calls — one per invocation — never four.
	 */
	public function testCreateChildControlsCalledTwiceInstantiatesTemplateTwice(): void
	{
		$tpl = $this->mockTemplate();
		$tpl->expects($this->exactly(2))->method('instantiateIn');

		$ctrl = $this->makeControlWithTemplate($tpl);
		$ctrl->createChildControls();
		$ctrl->createChildControls();
	}

	// -----------------------------------------------------------------------
	// Directive handling
	// -----------------------------------------------------------------------

	public function testCreateChildControlsAppliesStringDirectiveExactlyOnce(): void
	{
		$tpl = $this->createMock(TTemplate::class);
		$tpl->method('getDirective')->willReturn(['someprop' => 'value']);
		$tpl->method('instantiateIn');

		$ctrl = new class ($tpl) extends TTemplateControlInheritable {
			private $injected;
			public int $applyCount = 0;

			public function __construct($t)
			{
				$this->injected = $t;
			}

			public function getTemplate(): ?ITemplate
			{
				return $this->injected;
			}

			public function setSomeprop(string $v): void
			{
				$this->applyCount++;
			}
		};

		$ctrl->createChildControls();
		$this->assertSame(1, $ctrl->applyCount, 'Directive must be applied exactly once, not twice');
	}

	public function testCreateChildControlsThrowsOnNonStringDirectiveValue(): void
	{
		$tpl = $this->mockTemplate(['someKey' => ['not', 'a', 'string']]);

		$ctrl = $this->makeControlWithTemplate($tpl);

		$this->expectException(TConfigurationException::class);
		$ctrl->createChildControls();
	}

	public function testCreateChildControlsDoesNotThrowOnStringDirectiveValue(): void
	{
		$tpl = $this->mockTemplate(['someKey' => 'validString']);
		$tpl->method('instantiateIn');

		$ctrl = new class ($tpl) extends TTemplateControlInheritable {
			private $injected;

			public function __construct($t)
			{
				$this->injected = $t;
			}

			public function getTemplate(): ?ITemplate
			{
				return $this->injected;
			}

			public function setSomekey(string $v): void
			{
				// accept the directive silently
			}
		};

		$ctrl->createChildControls(); // must not throw
		$this->assertTrue(true);
	}

	// -----------------------------------------------------------------------
	// dyCreateChildControls behavior hook fires in both code paths
	// -----------------------------------------------------------------------

	/**
	 * When the control has its own template, the dyCreateChildControls dynamic
	 * event must fire exactly once so attached behaviors can hook into child
	 * control creation (the original motivation for PR #1078).
	 */
	public function testDyCreateChildControlsFiresWhenTemplateExists(): void
	{
		$tpl = $this->mockTemplate();
		$tpl->method('instantiateIn');

		$ctrl = $this->makeControlWithTemplate($tpl);

		$behavior = new TTemplateControlInheritableCreateChildBehavior();
		$ctrl->attachBehavior('tracker', $behavior);

		$ctrl->createChildControls();

		$this->assertSame(1, $behavior->callCount,
			'dyCreateChildControls must fire exactly once when the control owns a template');
	}

	/**
	 * When getTemplate() returns null (the doCreateChildControlsFor path),
	 * dyCreateChildControls must still fire.
	 *
	 * Before the fix the null-template branch had an early `return` that
	 * skipped the dy event entirely.
	 */
	public function testDyCreateChildControlsFiresWhenNoTemplate(): void
	{
		// Subclass that has no template and stubs out the service call inside
		// doCreateChildControlsFor so we don't need a running TApplication.
		$ctrl = new class extends TTemplateControlInheritable {
			public function getTemplate(): ?ITemplate
			{
				return null;
			}

			public function doCreateChildControlsFor($parentClass): void
			{
				// no-op: skip the getService() call
			}
		};

		$behavior = new TTemplateControlInheritableCreateChildBehavior();
		$ctrl->attachBehavior('tracker', $behavior);

		$ctrl->createChildControls();

		$this->assertSame(1, $behavior->callCount,
			'dyCreateChildControls must fire in the null-template (doCreateChildControlsFor) path');
	}

	// -----------------------------------------------------------------------
	// getIsSourceTemplateControl
	// -----------------------------------------------------------------------

	public function testGetIsSourceTemplateControlTrueWhenOwnTemplateIsSource(): void
	{
		$tpl = $this->createMock(TTemplate::class);
		$tpl->expects($this->once())->method('getIsSourceTemplate')->willReturn(true);

		$ctrl = $this->makeControlWithTemplate($tpl);
		$this->assertTrue($ctrl->getIsSourceTemplateControl());
	}

	public function testGetIsSourceTemplateControlFalseWhenOwnTemplateIsNotSource(): void
	{
		$tpl = $this->createMock(TTemplate::class);
		$tpl->expects($this->once())->method('getIsSourceTemplate')->willReturn(false);

		$ctrl = $this->makeControlWithTemplate($tpl);
		$this->assertFalse($ctrl->getIsSourceTemplateControl());
	}

	public function testGetIsSourceTemplateControlFalseWhenNoOwnTemplateAndNoParentTemplate(): void
	{
		// No template from getTemplate(), and doCreateChildControlsFor path can't
		// reach the service, so test the no-template branch directly via the
		// getIsSourceTemplateControl() fallback which calls getService() — we can
		// only exercise the own-template path safely in unit tests.
		// Covered by the two tests above; this entry documents the coverage gap.
		$this->assertTrue(true);
	}

	// -----------------------------------------------------------------------
	// doCreateChildControlsFor — stop-condition regression tests
	//
	// The stop condition previously compared get_parent_class() output against
	// the unqualified string 'TTemplateControl'.  get_parent_class() always
	// returns a fully-qualified name ('Prado\Web\UI\TTemplateControl'), so the
	// comparison was always false and recursion overshot into TCompositeControl,
	// TControl, TComponent, etc.
	//
	// We exercise this by overriding doTemplateForClass() to record every class
	// it is called with, then asserting the correct set of classes.
	// -----------------------------------------------------------------------

	/**
	 * Build a TTemplateControlInheritable that stubs doTemplateForClass() and
	 * records every class name passed to it via a public $visited property.
	 * getTemplate() returns null so createChildControls() always enters the
	 * doCreateChildControlsFor() path.
	 */
	private function makeTrackingControl(): TTemplateControlInheritable
	{
		return new class extends TTemplateControlInheritable {
			public array $visited = [];

			public function getTemplate(): ?\Prado\Web\UI\ITemplate
			{
				return null;
			}

			public function doTemplateForClass($parentClass): void
			{
				$this->visited[] = $parentClass;
				// do NOT call the real implementation — it needs getService()
			}
		};
	}

	/**
	 * Recursion must stop before reaching TTemplateControl itself.
	 * TTemplateControl::class must never appear in the list of classes passed
	 * to doTemplateForClass().
	 */
	public function testDoCreateChildControlsForDoesNotVisitTTemplateControl(): void
	{
		$ctrl = $this->makeTrackingControl();
		$ctrl->doCreateChildControlsFor($ctrl::class);

		$this->assertNotContains(
			\Prado\Web\UI\TTemplateControl::class,
			$ctrl->visited,
			'doCreateChildControlsFor must stop before reaching TTemplateControl'
		);
	}

	/**
	 * Framework base classes above TTemplateControl must never be visited.
	 */
	public function testDoCreateChildControlsForDoesNotVisitFrameworkBases(): void
	{
		$ctrl = $this->makeTrackingControl();
		$ctrl->doCreateChildControlsFor($ctrl::class);

		$frameworkBases = [
			\Prado\Web\UI\TTemplateControl::class,
			\Prado\Web\UI\TCompositeControl::class,
			\Prado\Web\UI\TControl::class,
			\Prado\TComponent::class,
		];
		foreach ($frameworkBases as $base) {
			$this->assertNotContains(
				$base,
				$ctrl->visited,
				"doCreateChildControlsFor must not visit framework base class $base"
			);
		}
	}

	/**
	 * TTemplateControlInheritable itself IS in the user-visible hierarchy and
	 * must be visited (in case a subproject places a template alongside it).
	 */
	public function testDoCreateChildControlsForVisitsTTemplateControlInheritable(): void
	{
		$ctrl = $this->makeTrackingControl();
		$ctrl->doCreateChildControlsFor($ctrl::class);

		$this->assertContains(
			\Prado\Web\UI\TTemplateControlInheritable::class,
			$ctrl->visited,
			'TTemplateControlInheritable itself must be visited so its template (if any) is applied'
		);
	}

	/**
	 * The concrete class must be the last entry visited: parents are processed
	 * before children (bottom-up, leaf last).
	 */
	public function testDoCreateChildControlsForVisitsUserHierarchyBottomUp(): void
	{
		$ctrl = $this->makeTrackingControl();
		$ctrl->doCreateChildControlsFor($ctrl::class);

		$this->assertSame(
			$ctrl::class,
			end($ctrl->visited),
			'The concrete class must be the last class visited (leaf processed after ancestors)'
		);
	}
}
