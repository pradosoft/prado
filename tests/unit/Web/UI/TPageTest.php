<?php

use Prado\Web\UI\TPage;
use Prado\Web\UI\TPageStatePersister;
use Prado\Web\UI\TSessionPageStatePersister;
use Prado\Web\UI\IPageStatePersister;
use Prado\Web\UI\TTemplateControl;
use Prado\Web\UI\TForm;
use Prado\Web\UI\WebControls\THead;
use Prado\Web\UI\TTheme;
use Prado\Collections\TList;
use Prado\Collections\TStack;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Web\UI\ActiveControls\TCallbackClientScript;

class TPageTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Construction and inheritance
	// -----------------------------------------------------------------------

	public function testConstructIsInstanceOfTPage(): void
	{
		$page = new TPage();
		$this->assertInstanceOf(TPage::class, $page);
	}

	public function testConstructExtendsTemplateControl(): void
	{
		$page = new TPage();
		$this->assertInstanceOf(TTemplateControl::class, $page);
	}

	// -----------------------------------------------------------------------
	// Callback client (adapter-safe method)
	// -----------------------------------------------------------------------

	public function testGetCallbackClientReturnsCallbackClientScriptWhenNoAdapter(): void
	{
		$page = new TPage();
		$client = $page->getCallbackClient();
		$this->assertInstanceOf(TCallbackClientScript::class, $client);
	}

	// -----------------------------------------------------------------------
	// Form registration
	// -----------------------------------------------------------------------

	public function testGetFormIsNullByDefault(): void
	{
		$page = new TPage();
		$this->assertNull($page->getForm());
	}

	public function testSetAndGetForm(): void
	{
		$page = new TPage();
		$form = $this->createMock(TForm::class);
		$page->setForm($form);
		$this->assertSame($form, $page->getForm());
	}

	public function testSetFormTwiceThrowsInvalidOperationException(): void
	{
		$page = new TPage();
		$form = $this->createMock(TForm::class);
		$page->setForm($form);

		$this->expectException(TInvalidOperationException::class);
		$page->setForm($this->createMock(TForm::class));
	}

	// -----------------------------------------------------------------------
	// Head registration
	// -----------------------------------------------------------------------

	public function testGetHeadIsNullByDefault(): void
	{
		$page = new TPage();
		$this->assertNull($page->getHead());
	}

	public function testSetAndGetHead(): void
	{
		$page = new TPage();
		$head = $this->createMock(THead::class);
		$page->setHead($head);
		$this->assertSame($head, $page->getHead());
	}

	public function testSetHeadTwiceThrowsInvalidOperationException(): void
	{
		$page = new TPage();
		$page->setHead($this->createMock(THead::class));

		$this->expectException(TInvalidOperationException::class);
		$page->setHead($this->createMock(THead::class));
	}

	// -----------------------------------------------------------------------
	// Title (no THead on page)
	// -----------------------------------------------------------------------

	public function testGetTitleIsEmptyStringByDefault(): void
	{
		$page = new TPage();
		$this->assertEquals('', $page->getTitle());
	}

	public function testSetAndGetTitleWithoutHead(): void
	{
		$page = new TPage();
		$page->setTitle('My Page');
		$this->assertEquals('My Page', $page->getTitle());
	}

	public function testSetTitleBeforeHeadIsAppliedWhenHeadSet(): void
	{
		$page = new TPage();
		$page->setTitle('Before Head');

		$head = $this->createMock(THead::class);
		$head->expects($this->once())->method('setTitle')->with('Before Head');
		$head->expects($this->once())->method('getTitle')->willReturn('Before Head');

		$page->setHead($head);
		$this->assertEquals('Before Head', $page->getTitle());
	}

	// -----------------------------------------------------------------------
	// Validators
	// -----------------------------------------------------------------------

	public function testGetValidatorsReturnsTList(): void
	{
		$page = new TPage();
		$this->assertInstanceOf(TList::class, $page->getValidators());
	}

	public function testGetValidatorsWithNullGroupReturnsSameListAllValidators(): void
	{
		$page = new TPage();
		$list1 = $page->getValidators();
		$list2 = $page->getValidators(null);
		$this->assertInstanceOf(TList::class, $list1);
		$this->assertInstanceOf(TList::class, $list2);
	}

	public function testGetValidatorsWithGroupReturnsFilteredTList(): void
	{
		$page = new TPage();
		$filtered = $page->getValidators('myGroup');
		$this->assertInstanceOf(TList::class, $filtered);
		$this->assertEquals(0, $filtered->getCount());
	}

	// -----------------------------------------------------------------------
	// Validate / getIsValid
	// -----------------------------------------------------------------------

	public function testValidateDoesNotThrow(): void
	{
		$page = new TPage();
		$page->validate();
		$this->assertTrue(true);
	}

	public function testGetIsValidAfterValidateReturnsTrue(): void
	{
		$page = new TPage();
		$page->validate();
		$this->assertTrue($page->getIsValid());
	}

	public function testGetIsValidBeforeValidateThrowsInvalidOperationException(): void
	{
		$page = new TPage();
		$this->expectException(TInvalidOperationException::class);
		$page->getIsValid();
	}

	// -----------------------------------------------------------------------
	// Theme / StyleSheetTheme (string and object variants)
	// -----------------------------------------------------------------------

	public function testGetThemeIsNullByDefault(): void
	{
		$page = new TPage();
		// setTheme has not been called → _theme is null; getTheme does not call service
		$reflection = new ReflectionProperty(TPage::class, '_theme');
		$reflection->setAccessible(true);
		$this->assertNull($reflection->getValue($page));
	}

	public function testSetThemeWithTThemeObject(): void
	{
		$page = new TPage();
		$theme = $this->createMock(TTheme::class);
		$page->setTheme($theme);

		// getTheme() only resolves a string; with an object it returns it directly
		$reflection = new ReflectionProperty(TPage::class, '_theme');
		$reflection->setAccessible(true);
		$this->assertSame($theme, $reflection->getValue($page));
	}

	public function testSetThemeToEmptyStringStoresNull(): void
	{
		$page = new TPage();
		$page->setTheme('');
		$reflection = new ReflectionProperty(TPage::class, '_theme');
		$reflection->setAccessible(true);
		$this->assertNull($reflection->getValue($page));
	}

	public function testSetThemeToNonEmptyStringStoresString(): void
	{
		$page = new TPage();
		$page->setTheme('SomeTheme');
		$reflection = new ReflectionProperty(TPage::class, '_theme');
		$reflection->setAccessible(true);
		$this->assertEquals('SomeTheme', $reflection->getValue($page));
	}

	public function testGetStyleSheetThemeIsNullByDefault(): void
	{
		$page = new TPage();
		$reflection = new ReflectionProperty(TPage::class, '_styleSheet');
		$reflection->setAccessible(true);
		$this->assertNull($reflection->getValue($page));
	}

	public function testSetStyleSheetThemeWithTThemeObject(): void
	{
		$page = new TPage();
		$theme = $this->createMock(TTheme::class);
		$page->setStyleSheetTheme($theme);
		$reflection = new ReflectionProperty(TPage::class, '_styleSheet');
		$reflection->setAccessible(true);
		$this->assertSame($theme, $reflection->getValue($page));
	}

	// -----------------------------------------------------------------------
	// applyControlSkin / applyControlStyleSheet (no theme → no-op)
	// -----------------------------------------------------------------------

	public function testApplyControlSkinWithNoThemeDoesNothing(): void
	{
		$page = new TPage();
		$control = $this->createMock(\Prado\Web\UI\TControl::class);
		$page->applyControlSkin($control);
		$this->assertTrue(true); // No exception, no interaction with control
	}

	public function testApplyControlStyleSheetWithNoThemeDoesNothing(): void
	{
		$page = new TPage();
		$control = $this->createMock(\Prado\Web\UI\TControl::class);
		$page->applyControlStyleSheet($control);
		$this->assertTrue(true);
	}

	// -----------------------------------------------------------------------
	// Event raisers — verify they can be called without throwing
	// -----------------------------------------------------------------------

	public function testOnPreInitDoesNotThrow(): void
	{
		$page = new TPage();
		$page->onPreInit(null);
		$this->assertTrue(true);
	}

	public function testOnInitCompleteDoesNotThrow(): void
	{
		$page = new TPage();
		$page->onInitComplete(null);
		$this->assertTrue(true);
	}

	public function testOnPreLoadDoesNotThrow(): void
	{
		$page = new TPage();
		$page->onPreLoad(null);
		$this->assertTrue(true);
	}

	public function testOnLoadCompleteDoesNotThrow(): void
	{
		$page = new TPage();
		$page->onLoadComplete(null);
		$this->assertTrue(true);
	}

	public function testOnSaveStateCompleteDoesNotThrow(): void
	{
		$page = new TPage();
		$page->onSaveStateComplete(null);
		$this->assertTrue(true);
	}

	// -----------------------------------------------------------------------
	// Postback / callback flags
	// -----------------------------------------------------------------------

	public function testGetIsPostBackReturnsFalseByDefault(): void
	{
		$page = new TPage();
		$this->assertFalse($page->getIsPostBack());
	}

	public function testGetIsCallbackReturnsFalseByDefault(): void
	{
		$page = new TPage();
		$this->assertFalse($page->getIsCallback());
	}

	// -----------------------------------------------------------------------
	// saveState / loadState
	// -----------------------------------------------------------------------

	public function testSaveStateDoesNotThrow(): void
	{
		$page = new TPage();
		$page->saveState();
		$this->assertTrue(true);
	}

	public function testLoadStateDoesNotThrow(): void
	{
		$page = new TPage();
		$page->loadState();
		$this->assertTrue(true);
	}

	// -----------------------------------------------------------------------
	// registerRequiresPostData
	// -----------------------------------------------------------------------

	public function testRegisterRequiresPostDataWithControlDoesNotThrow(): void
	{
		$page = new TPage();
		$control = $this->createMock(\Prado\Web\UI\TControl::class);
		$control->method('getUniqueID')->willReturn('control1');
		$page->registerRequiresPostData($control);
		$this->assertTrue(true);
	}

	public function testRegisterRequiresPostDataWithStringDoesNotThrow(): void
	{
		$page = new TPage();
		$page->registerRequiresPostData('someControlID');
		$this->assertTrue(true);
	}

	// -----------------------------------------------------------------------
	// PostBack event target / parameter
	// -----------------------------------------------------------------------

	public function testGetPostBackEventTargetIsNullByDefault(): void
	{
		$page = new TPage();
		$this->assertNull($page->getPostBackEventTarget());
	}

	public function testSetAndGetPostBackEventTarget(): void
	{
		$page = new TPage();
		$control = $this->createMock(\Prado\Web\UI\TControl::class);
		$page->setPostBackEventTarget($control);
		$this->assertSame($control, $page->getPostBackEventTarget());
	}

	public function testGetPostBackEventParameterIsNullByDefaultWithNoPostData(): void
	{
		$page = new TPage();
		// With no postData (_postData is null) → returns null
		$this->assertNull($page->getPostBackEventParameter());
	}

	public function testSetAndGetPostBackEventParameter(): void
	{
		$page = new TPage();
		$page->setPostBackEventParameter('test_param');
		$this->assertEquals('test_param', $page->getPostBackEventParameter());
	}

	// -----------------------------------------------------------------------
	// Loading post data flag
	// -----------------------------------------------------------------------

	public function testGetIsLoadingPostDataReturnsFalseByDefault(): void
	{
		$page = new TPage();
		$this->assertFalse($page->getIsLoadingPostData());
	}

	// -----------------------------------------------------------------------
	// Form render flags
	// -----------------------------------------------------------------------

	public function testGetInFormRenderReturnsFalseByDefault(): void
	{
		$page = new TPage();
		$this->assertFalse($page->getInFormRender());
	}

	// -----------------------------------------------------------------------
	// ensureRenderInForm — must throw when not in form render and not callback
	// -----------------------------------------------------------------------

	public function testEnsureRenderInFormThrowsWhenNotInForm(): void
	{
		$page = new TPage();
		$control = $this->createMock(\Prado\Web\UI\TControl::class);
		$control->method('getUniqueID')->willReturn('ctrl1');

		$this->expectException(TConfigurationException::class);
		$page->ensureRenderInForm($control);
	}

	// -----------------------------------------------------------------------
	// Client state
	// -----------------------------------------------------------------------

	public function testGetClientStateIsEmptyStringByDefault(): void
	{
		$page = new TPage();
		$this->assertEquals('', $page->getClientState());
	}

	public function testSetAndGetClientState(): void
	{
		$page = new TPage();
		$page->setClientState('abc123');
		$this->assertEquals('abc123', $page->getClientState());
	}

	public function testSetClientStateToEmptyString(): void
	{
		$page = new TPage();
		$page->setClientState('value');
		$page->setClientState('');
		$this->assertEquals('', $page->getClientState());
	}

	// -----------------------------------------------------------------------
	// StatePersisterClass / StatePersister
	// -----------------------------------------------------------------------

	public function testGetStatePersisterClassDefaultIsTPageStatePersister(): void
	{
		$page = new TPage();
		$this->assertEquals(TPageStatePersister::class, $page->getStatePersisterClass());
	}

	public function testSetStatePersisterClassToSessionPersister(): void
	{
		$page = new TPage();
		$page->setStatePersisterClass(TSessionPageStatePersister::class);
		$this->assertEquals(TSessionPageStatePersister::class, $page->getStatePersisterClass());
	}

	public function testGetStatePersisterReturnsIPageStatePersister(): void
	{
		$page = new TPage();
		$persister = $page->getStatePersister();
		$this->assertInstanceOf(IPageStatePersister::class, $persister);
	}

	public function testGetStatePersisterReturnsTPageStatePersisterByDefault(): void
	{
		$page = new TPage();
		$this->assertInstanceOf(TPageStatePersister::class, $page->getStatePersister());
	}

	public function testGetStatePersisterReturnsSameInstance(): void
	{
		$page = new TPage();
		$p1 = $page->getStatePersister();
		$p2 = $page->getStatePersister();
		$this->assertSame($p1, $p2);
	}

	public function testGetStatePersisterSetsItsPageToThis(): void
	{
		$page = new TPage();
		$persister = $page->getStatePersister();
		$this->assertSame($page, $persister->getPage());
	}

	// -----------------------------------------------------------------------
	// State feature flags
	// -----------------------------------------------------------------------

	public function testGetEnableStateValidationDefaultIsTrue(): void
	{
		$page = new TPage();
		$this->assertTrue($page->getEnableStateValidation());
	}

	public function testSetEnableStateValidationToFalse(): void
	{
		$page = new TPage();
		$page->setEnableStateValidation(false);
		$this->assertFalse($page->getEnableStateValidation());
	}

	public function testSetEnableStateValidationToTrue(): void
	{
		$page = new TPage();
		$page->setEnableStateValidation(false);
		$page->setEnableStateValidation(true);
		$this->assertTrue($page->getEnableStateValidation());
	}

	public function testGetEnableStateEncryptionDefaultIsFalse(): void
	{
		$page = new TPage();
		$this->assertFalse($page->getEnableStateEncryption());
	}

	public function testSetEnableStateEncryptionToTrue(): void
	{
		$page = new TPage();
		$page->setEnableStateEncryption(true);
		$this->assertTrue($page->getEnableStateEncryption());
	}

	public function testGetEnableStateCompressionDefaultIsTrue(): void
	{
		$page = new TPage();
		$this->assertTrue($page->getEnableStateCompression());
	}

	public function testSetEnableStateCompressionToFalse(): void
	{
		$page = new TPage();
		$page->setEnableStateCompression(false);
		$this->assertFalse($page->getEnableStateCompression());
	}

	public function testGetEnableStateIGBinaryDefaultIsTrue(): void
	{
		$page = new TPage();
		$this->assertTrue($page->getEnableStateIGBinary());
	}

	public function testSetEnableStateIGBinaryToFalse(): void
	{
		$page = new TPage();
		$page->setEnableStateIGBinary(false);
		$this->assertFalse($page->getEnableStateIGBinary());
	}

	// -----------------------------------------------------------------------
	// PagePath
	// -----------------------------------------------------------------------

	public function testGetPagePathIsEmptyStringByDefault(): void
	{
		$page = new TPage();
		$this->assertEquals('', $page->getPagePath());
	}

	public function testSetAndGetPagePath(): void
	{
		$page = new TPage();
		$page->setPagePath('Home/Index');
		$this->assertEquals('Home/Index', $page->getPagePath());
	}

	// -----------------------------------------------------------------------
	// CachingStack / registerCachingAction
	// -----------------------------------------------------------------------

	public function testGetCachingStackReturnsTStack(): void
	{
		$page = new TPage();
		$this->assertInstanceOf(TStack::class, $page->getCachingStack());
	}

	public function testGetCachingStackReturnsSameInstance(): void
	{
		$page = new TPage();
		$this->assertSame($page->getCachingStack(), $page->getCachingStack());
	}

	public function testRegisterCachingActionWithEmptyStackDoesNotThrow(): void
	{
		$page = new TPage();
		// getCachingStack() is called lazily; _cachingStack is null initially.
		// registerCachingAction only iterates if _cachingStack is non-null, so no exception.
		$page->registerCachingAction('Page', 'someMethod', ['arg1']);
		$this->assertTrue(true);
	}

	// -----------------------------------------------------------------------
	// ClientSupportsJavaScript
	// -----------------------------------------------------------------------

	public function testGetClientSupportsJavaScriptDefaultIsTrue(): void
	{
		$page = new TPage();
		$this->assertTrue($page->getClientSupportsJavaScript());
	}

	public function testSetClientSupportsJavaScriptToFalse(): void
	{
		$page = new TPage();
		$page->setClientSupportsJavaScript(false);
		$this->assertFalse($page->getClientSupportsJavaScript());
	}

	public function testSetClientSupportsJavaScriptToTrue(): void
	{
		$page = new TPage();
		$page->setClientSupportsJavaScript(false);
		$page->setClientSupportsJavaScript(true);
		$this->assertTrue($page->getClientSupportsJavaScript());
	}

	// -----------------------------------------------------------------------
	// setFocus — simple storage, no rendering side-effects
	// -----------------------------------------------------------------------

	public function testSetFocusStoresValue(): void
	{
		$page = new TPage();
		$page->setFocus('myElementId');

		$reflection = new ReflectionProperty(TPage::class, '_focus');
		$reflection->setAccessible(true);
		$this->assertEquals('myElementId', $reflection->getValue($page));
	}

	// -----------------------------------------------------------------------
	// System post field constants
	// -----------------------------------------------------------------------

	public function testFieldPostbackTargetConstant(): void
	{
		$this->assertEquals('PRADO_POSTBACK_TARGET', TPage::FIELD_POSTBACK_TARGET);
	}

	public function testFieldPostbackParameterConstant(): void
	{
		$this->assertEquals('PRADO_POSTBACK_PARAMETER', TPage::FIELD_POSTBACK_PARAMETER);
	}

	public function testFieldPageStateConstant(): void
	{
		$this->assertEquals('PRADO_PAGESTATE', TPage::FIELD_PAGESTATE);
	}

	public function testFieldCallbackTargetConstant(): void
	{
		$this->assertEquals('PRADO_CALLBACK_TARGET', TPage::FIELD_CALLBACK_TARGET);
	}

	public function testFieldCallbackParameterConstant(): void
	{
		$this->assertEquals('PRADO_CALLBACK_PARAMETER', TPage::FIELD_CALLBACK_PARAMETER);
	}
}
