<?php

use Prado\Web\UI\ActiveControls\TCallbackPageStateTracker;
use PHPUnit\Framework\TestCase;

// ---------------------------------------------------------------------------
// Test doubles
// ---------------------------------------------------------------------------

/**
 * Records calls made by TCallbackPageStateTracker to the callback client.
 */
class FakeCallbackClient
{
	use TCallCollectorTrait;

	public function setAttribute($control, $name, $value): void
	{
		$this->collectCall();
	}

	public function removeAttribute($control, $name): void
	{
		$this->collectCall();
	}

	public function replaceContent($control, $content): void
	{
		$this->collectCall();
	}

	public function setStyle($control, $styles): void
	{
		$this->collectCall();
	}
}

/**
 * Minimal control double that supports view state get/set and a fixed client ID.
 */
class FakeTrackedControl
{
	private array $_viewState = [];

	public function getViewState(string $name, $default = null): mixed
	{
		return array_key_exists($name, $this->_viewState) ? $this->_viewState[$name] : $default;
	}

	public function setViewState(string $name, $value): void
	{
		$this->_viewState[$name] = $value;
	}

	public function getClientID(): string
	{
		return 'ctrl1';
	}
}

/**
 * Subclass that injects FakeCallbackClient and exposes protected methods.
 */
class TCallbackPageStateTrackerTestable extends TCallbackPageStateTracker
{
	private FakeCallbackClient $_fakeClient;

	public function __construct($control, FakeCallbackClient $fakeClient)
	{
		$this->_fakeClient = $fakeClient;
		parent::__construct($control);
	}

	protected function client(): FakeCallbackClient
	{
		return $this->_fakeClient;
	}

	public function exposedGetStatesToTrack(): \Prado\Collections\TMap
	{
		return $this->getStatesToTrack();
	}

	public function exposedGetChanges(): array
	{
		return $this->getChanges();
	}

	public function exposedUpdateAttribute(string $attrName, mixed $value): void
	{
		$this->updateAttribute($attrName, $value);
	}

	public function exposedUpdatePresenceAttribute(string $attrName, bool $isPresent): void
	{
		$this->updatePresenceAttribute($attrName, $isPresent);
	}

	public function exposedUpdateVisible(mixed $visible): void
	{
		$this->updateVisible($visible);
	}

	public function exposedUpdateStyle(array $style): void
	{
		$this->updateStyle($style);
	}

	public function exposedUpdateAttributes(array $attributes): void
	{
		$this->updateAttributes($attributes);
	}
}

// ---------------------------------------------------------------------------
// Test class
// ---------------------------------------------------------------------------

/**
 * Unit tests for {@see \Prado\Web\UI\ActiveControls\TCallbackPageStateTracker}.
 *
 * @covers \Prado\Web\UI\ActiveControls\TCallbackPageStateTracker
 */
class TCallbackPageStateTrackerTest extends TestCase
{
	private FakeTrackedControl $control;
	private FakeCallbackClient $client;
	private TCallbackPageStateTrackerTestable $tracker;

	protected function setUp(): void
	{
		$this->control = new FakeTrackedControl();
		$this->client = new FakeCallbackClient();
		$this->tracker = new TCallbackPageStateTrackerTestable($this->control, $this->client);
	}

	// -----------------------------------------------------------------------
	// getStatesToTrack
	// -----------------------------------------------------------------------

	public function testGetStatesToTrackContainsAllExpectedKeys()
	{
		$states = $this->tracker->exposedGetStatesToTrack();
		$expected = [
			'Visible', 'Enabled', 'Attributes', 'Style',
			'TabIndex', 'ToolTip', 'AccessKey',
			'Translate', 'Lang', 'Dir', 'Hidden', 'SpellCheck',
			'Draggable', 'ContentEditable', 'InputMode', 'EnterKeyHint',
			'Inert', 'Popover', 'Aria', 'Dataset',
		];
		foreach ($expected as $key) {
			$this->assertTrue($states->contains($key), "StatesToTrack must contain '$key'");
		}
	}

	public function testGetStatesToTrackHasCorrectEntryStructure()
	{
		$states = $this->tracker->exposedGetStatesToTrack();
		foreach ($states as $key => $entry) {
			$this->assertIsArray($entry, "Entry for '$key' must be an array");
			$this->assertCount(2, $entry, "Entry for '$key' must have exactly 2 elements");
			$this->assertIsString($entry[0], "First element (diff class) for '$key' must be a string");
			$this->assertTrue(is_array($entry[1]) || ($entry[1] instanceof \Closure), "Second element (handler) for '$key' must be a callable array or Closure");
		}
	}

	// -----------------------------------------------------------------------
	// trackChanges + getChanges (no actual diff classes needed)
	// -----------------------------------------------------------------------

	public function testTrackChangesSnapshotsCurrentViewState()
	{
		$this->control->setViewState('TabIndex', 3);
		$this->tracker->trackChanges();

		// Changing state after snapshot: getChanges should detect a difference.
		$this->control->setViewState('TabIndex', 5);
		$changes = $this->tracker->exposedGetChanges();

		$found = false;
		foreach ($changes as [$handler, $args]) {
			// The TabIndex handler is a closure. We look for any change detected.
			$found = true;
		}
		$this->assertTrue($found, 'getChanges must detect TabIndex change after trackChanges');
	}

	public function testTrackChangesNoChangeProducesNoChanges()
	{
		$this->control->setViewState('TabIndex', 3);
		$this->tracker->trackChanges();
		// No mutation after snapshot.
		$changes = $this->tracker->exposedGetChanges();
		$this->assertSame([], $changes);
	}

	public function testRespondToChangesInvokesHandlerForChangedState()
	{
		$this->control->setViewState('TabIndex', 3);
		$this->tracker->trackChanges();
		$this->control->setViewState('TabIndex', 7);

		$this->tracker->respondToChanges();

		$calls = $this->client->getCollectedCalls('setAttribute');
		$this->assertNotEmpty($calls, 'setAttribute must be called when TabIndex changes');
	}

	public function testRespondToChangesNoChangeCallsNoClientMethods()
	{
		$this->tracker->trackChanges();
		$this->tracker->respondToChanges();
		$this->assertSame(0, $this->client->getCollectedCallCount());
	}

	// -----------------------------------------------------------------------
	// updateAttribute
	// -----------------------------------------------------------------------

	public function testUpdateAttributeCallsSetAttributeWithCorrectArgs()
	{
		$this->tracker->exposedUpdateAttribute('tabindex', 5);

		$calls = $this->client->getCollectedCalls('setAttribute');
		$this->assertCount(1, $calls);
		$this->assertSame('tabindex', $calls[0][1]);
		$this->assertSame(5, $calls[0][2]);
	}

	public function testUpdateAttributePassesControlAsFirstArg()
	{
		$this->tracker->exposedUpdateAttribute('lang', 'en');
		$calls = $this->client->getCollectedCalls('setAttribute');
		$this->assertSame($this->control, $calls[0][0]);
	}

	// -----------------------------------------------------------------------
	// updatePresenceAttribute
	// -----------------------------------------------------------------------

	public function testUpdatePresenceAttributeTrueCallsSetAttribute()
	{
		$this->tracker->exposedUpdatePresenceAttribute('disabled', true);

		$calls = $this->client->getCollectedCalls('setAttribute');
		$this->assertCount(1, $calls);
		$this->assertSame('disabled', $calls[0][1]);
		$this->assertSame('disabled', $calls[0][2]);
	}

	public function testUpdatePresenceAttributeFalseCallsRemoveAttribute()
	{
		$this->tracker->exposedUpdatePresenceAttribute('disabled', false);

		$removes = $this->client->getCollectedCalls('removeAttribute');
		$this->assertCount(1, $removes);
		$this->assertSame('disabled', $removes[0][1]);
		$this->assertSame(0, $this->client->getCollectedCallCount('setAttribute'));
	}

	// -----------------------------------------------------------------------
	// updateVisible
	// -----------------------------------------------------------------------

	public function testUpdateVisibleFalseCallsReplaceContentWithPlaceholder()
	{
		$this->tracker->exposedUpdateVisible(false);

		$calls = $this->client->getCollectedCalls('replaceContent');
		$this->assertCount(1, $calls);
		$content = $calls[0][1];
		$this->assertStringContainsString('display:none', $content);
		$this->assertStringContainsString('ctrl1', $content);
	}

	public function testUpdateVisibleTrueCallsReplaceContentWithControl()
	{
		$this->tracker->exposedUpdateVisible(true);

		$calls = $this->client->getCollectedCalls('replaceContent');
		$this->assertCount(1, $calls);
		$this->assertSame($this->control, $calls[0][1]);
	}

	// -----------------------------------------------------------------------
	// updateStyle
	// -----------------------------------------------------------------------

	public function testUpdateStyleSetsClassWhenCssClassIsNotNull()
	{
		$this->tracker->exposedUpdateStyle(['CssClass' => 'my-class', 'Style' => []]);

		$calls = $this->client->getCollectedCalls('setAttribute');
		$this->assertCount(1, $calls);
		$this->assertSame('class', $calls[0][1]);
		$this->assertSame('my-class', $calls[0][2]);
	}

	public function testUpdateStyleSkipsCssClassWhenNull()
	{
		$this->tracker->exposedUpdateStyle(['CssClass' => null, 'Style' => ['color' => 'red']]);
		$this->assertSame(0, $this->client->getCollectedCallCount('setAttribute'));
	}

	public function testUpdateStyleCallsSetStyleWhenStyleArrayIsNonEmpty()
	{
		$this->tracker->exposedUpdateStyle(['CssClass' => null, 'Style' => ['color' => 'red']]);

		$calls = $this->client->getCollectedCalls('setStyle');
		$this->assertCount(1, $calls);
		$this->assertSame(['color' => 'red'], $calls[0][1]);
	}

	public function testUpdateStyleSkipsSetStyleWhenStyleArrayIsEmpty()
	{
		$this->tracker->exposedUpdateStyle(['CssClass' => null, 'Style' => []]);
		$this->assertSame(0, $this->client->getCollectedCallCount('setStyle'));
	}

	public function testUpdateStyleHandlesBothCssClassAndStyle()
	{
		$this->tracker->exposedUpdateStyle(['CssClass' => 'btn', 'Style' => ['display' => 'block']]);

		$this->assertSame(1, $this->client->getCollectedCallCount('setAttribute'));
		$this->assertSame(1, $this->client->getCollectedCallCount('setStyle'));
	}

	// -----------------------------------------------------------------------
	// updateAttributes
	// -----------------------------------------------------------------------

	public function testUpdateAttributesSetsEachAttribute()
	{
		$this->tracker->exposedUpdateAttributes(['aria-label' => 'Menu', 'data-id' => '42']);

		$calls = $this->client->getCollectedCalls('setAttribute');
		$this->assertCount(2, $calls);

		$names = array_column($calls, 1);
		$this->assertContains('aria-label', $names);
		$this->assertContains('data-id', $names);
	}

	public function testUpdateAttributesEmptyMapCallsNothing()
	{
		$this->tracker->exposedUpdateAttributes([]);
		$this->assertSame(0, $this->client->getCollectedCallCount('setAttribute'));
	}

	// -----------------------------------------------------------------------
	// Enabled state: diff value is the raw boolean
	// -----------------------------------------------------------------------

	public function testEnabledFalseCallsPresenceAttributeDisable()
	{
		$this->control->setViewState('Enabled', true);
		$this->tracker->trackChanges();
		$this->control->setViewState('Enabled', false);
		$this->tracker->respondToChanges();

		$removes = $this->client->getCollectedCalls('removeAttribute');
		$sets = $this->client->getCollectedCalls('setAttribute');
		// Enabled going true→false: diff=false, isPresent=(false===false)=true → setAttribute('disabled','disabled')
		$this->assertNotEmpty($sets, 'setAttribute must be called when control is disabled');
		$this->assertSame('disabled', $sets[0][1]);
		$this->assertSame('disabled', $sets[0][2]);
	}
}
