<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Test\Unit\Harness\Web\TTestRecordingResponse;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActivePageAdapter;
use Prado\Web\UI\ActiveControls\TCallback;
use Prado\Web\UI\ActiveControls\TCallbackClientScript;
use Prado\Web\UI\ActiveControls\TCallbackResponseWriter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\TCheckBoxList;
use Prado\Web\UI\WebControls\TDropDownList;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TSuggestionList;

/**
 * The content methods of the callback client (setText() sends text and update() sends
 * HTML) and the browser methods (notify(), the app badge, the document title, vibrate()),
 * the element, selection, and effect methods, and the content boundaries of a callback response.
 */
class TCallbackClientScriptTest extends TestCase
{
	private array $snapshot = [];

	protected function setUp(): void
	{
		$this->snapshot = PradoUnit::snapshot(Prado::getApplication(), ['_response', '_errorHandler']);
	}

	protected function tearDown(): void
	{
		PradoUnit::restore(Prado::getApplication(), $this->snapshot);
	}

	/**
	 * @return array{TTestCallbackPage, TCallbackClientScript} a callback page with an active page adapter, so the
	 *   application response writes callback response parts, and its callback client
	 */
	private function createAdaptedPage(): array
	{
		Prado::getApplication()->setResponse(new TTestRecordingResponse());
		$page = new TTestCallbackPage();
		$page->setAdapter(new TActivePageAdapter($page));
		return [$page, $page->getCallbackClient()];
	}

	/**
	 * @return TLabel a label with the ID, in the page
	 */
	private function addLabel(TTestCallbackPage $page, string $id = 'Target'): TLabel
	{
		$label = new TLabel();
		$label->setID($id);
		$page->getControls()->add($label);
		return $label;
	}

	/**
	 * @return TCheckBox a check box with the ID, in the page; it surrounds its input with a span
	 */
	private function addCheckBox(TTestCallbackPage $page, string $id = 'Box'): TCheckBox
	{
		$box = new TCheckBox();
		$box->setID($id);
		$page->getControls()->add($box);
		return $box;
	}
	public function testSetTextSendsTheTextMethod()
	{
		$page = new TTestCallbackPage();
		$page->getCallbackClient()->setText('Target', '<b>a & b</b>');
		$this->assertSame(
			[['Prado.Element.j' => ['Target', 'text', ['<b>a & b</b>']]]],
			$page->getClientFunctions()
		);
	}

	public function testSetTextTakesAControlAndCastsTheText()
	{
		$page = new TTestCallbackPage();
		$label = new TLabel();
		$label->setID('Target');
		$page->getControls()->add($label);
		$page->getCallbackClient()->setText($label, 42);
		$this->assertSame(
			[['Prado.Element.j' => [$label->getClientID(), 'text', ['42']]]],
			$page->getClientFunctions()
		);
	}

	public function testUpdateSendsTheHtmlMethod()
	{
		$page = new TTestCallbackPage();
		$page->getCallbackClient()->update('Target', '<b>a</b>');
		$this->assertSame(
			[['Prado.Element.j' => ['Target', 'html', ['<b>a</b>']]]],
			$page->getClientFunctions()
		);
	}

	public function testReplaceContentSendsNoMorphArgumentByDefault()
	{
		$page = new TTestCallbackPage();
		$page->getCallbackClient()->replaceContent('Target', '<b>a</b>');
		$this->assertSame(
			[['Prado.Element.replace' => ['Target', '<b>a</b>', null, true]]],
			$page->getClientFunctions()
		);
	}

	public function testReplaceContentSendsTheMorphArgument()
	{
		$page = new TTestCallbackPage();
		$page->getCallbackClient()->replaceContent('Target', '<b>a</b>', false, true);
		$this->assertSame(
			[['Prado.Element.replace' => ['Target', '<b>a</b>', null, false, true]]],
			$page->getClientFunctions()
		);
	}

	public function testNotifySendsTheTitleAndOptions()
	{
		$page = new TTestCallbackPage();
		$page->getCallbackClient()->notify('Order shipped', ['body' => 'Today', 'tag' => 'order-1']);
		$this->assertSame(
			[['Prado.Notification.show' => ['Order shipped', ['body' => 'Today', 'tag' => 'order-1']]]],
			$page->getClientFunctions()
		);
	}

	public function testNotifySendsTheClickTargetAndParameter()
	{
		$page = new TTestCallbackPage();
		$callback = new TCallback();
		$callback->setID('onOpen');
		$page->getControls()->add($callback);
		$page->getCallbackClient()->notify('Hi', [], $callback, ['order' => 7]);
		$page->getCallbackClient()->notify('Hi', ['body' => 'b'], 'ctl0:other');
		$this->assertSame(
			[
				['Prado.Notification.show' => ['Hi', ['EventTarget' => $callback->getUniqueID(), 'CallbackParameter' => ['order' => 7]]]],
				['Prado.Notification.show' => ['Hi', ['body' => 'b', 'EventTarget' => 'ctl0:other']]],
			],
			$page->getClientFunctions()
		);
	}

	public function testAppBadgeSendsTheCountOrNullAndClears()
	{
		$page = new TTestCallbackPage();
		$client = $page->getCallbackClient();
		$client->setAppBadge('3');
		$client->setAppBadge();
		$client->setAppBadge(0);
		$client->clearAppBadge();
		$this->assertSame(
			[
				['Prado.Browser.setAppBadge' => [3]],
				['Prado.Browser.setAppBadge' => [null]],
				['Prado.Browser.setAppBadge' => [0]],
				['Prado.Browser.clearAppBadge' => []],
			],
			$page->getClientFunctions()
		);
	}

	public function testAppBadgeRejectsANegativeCount()
	{
		$page = new TTestCallbackPage();
		$this->expectException(\InvalidArgumentException::class);
		$page->getCallbackClient()->setAppBadge(-1);
	}

	public function testSetDocumentTitleSendsTheTitle()
	{
		$page = new TTestCallbackPage();
		$page->getCallbackClient()->setDocumentTitle(42);
		$this->assertSame([['Prado.Browser.setTitle' => ['42']]], $page->getClientFunctions());
	}

	public function testVibrateSendsAnIntegerOrAPattern()
	{
		$page = new TTestCallbackPage();
		$page->getCallbackClient()->vibrate('200');
		$page->getCallbackClient()->vibrate([100, '50', 100]);
		$page->getCallbackClient()->vibrate(0);
		$this->assertSame(
			[
				['Prado.Browser.vibrate' => [200]],
				['Prado.Browser.vibrate' => [[100, 50, 100]]],
				['Prado.Browser.vibrate' => [0]],
			],
			$page->getClientFunctions()
		);
	}

	public function testSetListItemsSendsGroupAndContent()
	{
		$page = new TTestCallbackPage();
		$list = new TDropDownList();
		$list->setID('Pets');
		$list->setPromptText('Choose');
		$plain = new TListItem('Fish', 'fish');
		$grouped = new TListItem('Dog', 'dog');
		$grouped->setAttribute('Group', 'Mammals');
		$attributed = new TListItem('Bird', 'bird');
		$attributed->setAttribute('class', 'wing');
		$rich = new TListItem('Cat', 'cat');
		$rich->setContent('<b>Cat</b>');
		$richGrouped = new TListItem('Cow', 'cow');
		$richGrouped->setAttribute('Group', 'Mammals');
		$richGrouped->setContent('<i>Cow</i>');
		$page->getCallbackClient()->setListItems($list, [$plain, $grouped, $attributed, $rich, $richGrouped]);
		$this->assertSame(
			[['Prado.Element.setOptions' => ['Pets', [
				['Choose', 'Choose'],
				['Fish', 'fish'],
				['Dog', 'dog', 'Mammals'],
				['Bird', 'bird'],
				['Cat', 'cat', null, '<b>Cat</b>'],
				['Cow', 'cow', 'Mammals', '<i>Cow</i>'],
			]]]],
			$page->getClientFunctions()
		);
	}

	public function testCallClientFunctionWrapsAScalarAndResolvesTheFirstControl()
	{
		$page = new TTestCallbackPage();
		$label = $this->addLabel($page);
		$box = $this->addCheckBox($page);
		$client = $page->getCallbackClient();
		$client->callClientFunction('f', 'x');
		$client->callClientFunction('g');
		$client->callClientFunction('h', [$label, $box]);
		$client->callClientFunction('i', [$box]);
		$this->assertSame([
			['f' => ['x']],
			['g' => []],
			['h' => [$label->getClientID(), $box]],
			['i' => [$box->getSurroundingTagID()]],
		], $page->getClientFunctions());
	}

	public function testViewTransitionDefaultsToFalseAndIsSet()
	{
		$client = new TCallbackClientScript();
		$this->assertFalse($client->getViewTransition());
		$client->setViewTransition('true');
		$this->assertTrue($client->getViewTransition());
	}

	public function testJQueryResolvesTheElementAndWrapsAScalar()
	{
		$page = new TTestCallbackPage();
		$label = $this->addLabel($page);
		$box = $this->addCheckBox($page);
		$client = $page->getCallbackClient();
		$client->jQuery($label, 'm', 'p');
		$client->jQuery($box, 'm', ['p', 'q']);
		$client->jQuery('Raw', 'm');
		$this->assertSame([
			['Prado.Element.j' => [$label->getClientID(), 'm', ['p']]],
			['Prado.Element.j' => [$box->getSurroundingTagID(), 'm', ['p', 'q']]],
			['Prado.Element.j' => ['Raw', 'm', []]],
		], $page->getClientFunctions());
	}

	public function testElementMethodsSendTheirJQueryMethods()
	{
		$page = new TTestCallbackPage();
		$client = $page->getCallbackClient();
		$client->setValue('E', 'v');
		$client->click('E');
		$client->raiseClientEvent('E', 'change');
		$client->show('E');
		$client->hide('E');
		$client->remove('E');
		$client->addCssClass('E', 'on');
		$client->removeCssClass('E', 'off');
		$client->focus('E');
		$client->setStyle('E', ['color' => 'red']);
		$client->appendContent('E', 'a');
		$client->prependContent('E', 'b');
		$client->insertContentAfter('E', 'c');
		$client->insertContentBefore('E', 'd');
		$client->scrollTo('E', ['behavior' => 'smooth']);
		$this->assertSame([
			['Prado.Element.j' => ['E', 'val', ['v']]],
			['Prado.Element.j' => ['E', 'trigger', ['click']]],
			['Prado.Element.j' => ['E', 'trigger', ['change']]],
			['Prado.Element.j' => ['E', 'show', []]],
			['Prado.Element.j' => ['E', 'hide', []]],
			['Prado.Element.j' => ['E', 'remove', []]],
			['Prado.Element.j' => ['E', 'addClass', ['on']]],
			['Prado.Element.j' => ['E', 'removeClass', ['off']]],
			['Prado.Element.j' => ['E', 'trigger', ['focus']]],
			['Prado.Element.j' => ['E', 'css', [['color' => 'red']]]],
			['Prado.Element.j' => ['E', 'append', ['a']]],
			['Prado.Element.j' => ['E', 'prepend', ['b']]],
			['Prado.Element.j' => ['E', 'after', ['c']]],
			['Prado.Element.j' => ['E', 'before', ['d']]],
			['Prado.Element.scrollTo' => ['E', ['behavior' => 'smooth']]],
		], $page->getClientFunctions());
	}

	public function testToggleChoosesTheEffectMethod()
	{
		$page = new TTestCallbackPage();
		$client = $page->getCallbackClient();
		$client->toggle('E', 'Fade', ['duration' => 5]);
		$client->toggle('E', 'slide');
		$client->toggle('E', 'none');
		$client->toggle('E', 'none', ['duration' => 7]);
		$this->assertSame([
			['Prado.Element.j' => ['E', 'fadeToggle', ['duration' => 5]]],
			['Prado.Element.j' => ['E', 'slideToggle', []]],
			['Prado.Element.j' => ['E', 'toggle', ['duration' => 0]]],
			['Prado.Element.j' => ['E', 'toggle', ['duration' => 7]]],
		], $page->getClientFunctions());
	}

	public function testSelectSendsTheControlTypeAndItemCount()
	{
		$page = new TTestCallbackPage();
		$list = new TDropDownList();
		$list->setID('List');
		$list->getItems()->add(new TListItem('A', 'a'));
		$list->getItems()->add(new TListItem('B', 'b'));
		$checks = new TCheckBoxList();
		$checks->setID('Checks');
		$checks->getItems()->add(new TListItem('A', 'a'));
		$box = $this->addCheckBox($page);
		$page->getControls()->add($list);
		$page->getControls()->add($checks);
		$client = $page->getCallbackClient();
		$client->select($list, 'Index', 1);
		$client->select($checks, 'Values', ['a']);
		$client->select($box, 'Clear');
		$client->select('Raw');
		$client->select('Raw', 'All', null, 'select');
		$client->check($box, false);
		$this->assertSame([
			['Prado.Element.select' => [$list->getClientID(), 'selectIndex', 1, 2]],
			['Prado.Element.select' => [$checks->getClientID(), 'checkValues', ['a'], 1]],
			['Prado.Element.select' => [$box->getClientID(), 'checkClear', null, 1]],
			['Prado.Element.select' => ['Raw', 'checkValue', null, 1]],
			['Prado.Element.select' => ['Raw', 'selectAll', null, 1]],
			['Prado.Element.select' => [$box->getClientID(), 'checkValue', false, 1]],
		], $page->getClientFunctions());
	}

	public function testSelectOfALabelIsASelectType()
	{
		$page = new TTestCallbackPage();
		$label = $this->addLabel($page);
		$page->getCallbackClient()->select($label);
		$this->assertSame([['Prado.Element.select' => [$label->getClientID(), 'selectValue', null, 1]]], $page->getClientFunctions());
	}

	public function testSelectRejectsAnUnknownMethod()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TCallbackClientScript())->select('Raw', 'Sideways');
	}

	public function testAttributesGoToTheSurroundingTagExceptDisabled()
	{
		$page = new TTestCallbackPage();
		$box = $this->addCheckBox($page);
		$client = $page->getCallbackClient();
		$client->setAttribute($box, 'title', 't');
		$client->setAttribute($box, 'Disabled', 'disabled');
		$client->setAttribute('Raw', 'title', 't');
		$client->removeAttribute($box, 'title');
		$client->removeAttribute($box, 'DISABLED');
		$client->removeAttribute('Raw', 'title');
		$this->assertSame([
			['Prado.Element.setAttribute' => [$box->getSurroundingTagID(), 'title', 't']],
			['Prado.Element.setAttribute' => [$box->getSurroundingTagID(), 'Disabled', 'disabled']],
			['Prado.Element.setAttribute' => ['Raw', 'title', 't']],
			['Prado.Element.removeAttribute' => [$box->getSurroundingTagID(), 'title']],
			['Prado.Element.removeAttribute' => [$box->getSurroundingTagID(), 'DISABLED']],
			['Prado.Element.removeAttribute' => ['Raw', 'title']],
		], $page->getClientFunctions());
	}

	public function testSetListItemsUsesThePromptValueAndSkipsAnEmptyPrompt()
	{
		$page = new TTestCallbackPage();
		$list = new TDropDownList();
		$list->setID('Pets');
		$list->setPromptText('Choose');
		$list->setPromptValue('-');
		$bare = new TDropDownList();
		$bare->setID('Bare');
		$client = $page->getCallbackClient();
		$client->setListItems($list, [new TListItem('Fish', 'fish')]);
		$client->setListItems($bare, []);
		$client->setListItems('Raw', [new TListItem('Fish', 'fish')]);
		$this->assertSame([
			['Prado.Element.setOptions' => ['Pets', [['Choose', '-'], ['Fish', 'fish']]]],
			['Prado.Element.setOptions' => ['Bare', []]],
			['Prado.Element.setOptions' => ['Raw', [['Fish', 'fish']]]],
		], $page->getClientFunctions());
	}

	public function testSetListItemsOfASuggestionListSendsTheDataListOptions()
	{
		$page = new TTestCallbackPage();
		$list = new TSuggestionList();
		$list->setID('Cities');
		$page->getControls()->add($list);
		$disabled = new TListItem('Gone', 'gone');
		$disabled->setEnabled(false);
		$labelled = new TListItem('Paris', 'paris');
		$labelled->setAttribute('Group', 'Europe');
		$labelled->setAttribute('title', 'Capital');
		$page->getCallbackClient()->setListItems($list, [new TListItem('Rome', 'Rome'), $disabled, $labelled]);
		$this->assertSame([
			['Prado.Element.setDataListOptions' => [$list->getClientID(), [
				['Rome', '', []],
				['paris', 'Paris', ['title' => 'Capital']],
			]]],
		], $page->getClientFunctions());
	}

	public function testEffectsSendTheirJQueryMethods()
	{
		$page = new TTestCallbackPage();
		$client = $page->getCallbackClient();
		$client->visualEffect('pulse', 'E', ['times' => 2]);
		$client->fadeIn('E');
		$client->fadeOut('E', ['duration' => 1]);
		$client->fadeTo('E', '0.5');
		$client->fadeTo('E', 1, 200);
		$client->slideDown('E');
		$client->slideUp('E');
		$client->appear('E');
		$client->fade('E');
		$client->setOpacity('E', 0.25);
		$this->assertSame([
			['Prado.Element.j' => ['E', 'pulse', ['times' => 2]]],
			['Prado.Element.j' => ['E', 'fadeIn', []]],
			['Prado.Element.j' => ['E', 'fadeOut', ['duration' => 1]]],
			['Prado.Element.j' => ['E', 'fadeTo', [500, 0.5]]],
			['Prado.Element.j' => ['E', 'fadeTo', [200, 1.0]]],
			['Prado.Element.j' => ['E', 'slideDown', []]],
			['Prado.Element.j' => ['E', 'slideUp', []]],
			['Prado.Element.j' => ['E', 'fadeIn', []]],
			['Prado.Element.j' => ['E', 'fadeOut', []]],
			['Prado.Element.j' => ['E', 'fadeTo', [500, 0.25]]],
		], $page->getClientFunctions());
	}

	public function testJQueryUiEffectsSendTheEffectOption()
	{
		$page = new TTestCallbackPage();
		$client = $page->getCallbackClient();
		$client->juiEffect('E', 'bounce', ['times' => 3]);
		$methods = ['blind', 'drop', 'fold', 'size', 'puff', 'pulsate', 'shake', 'scale', 'highlight'];
		foreach ($methods as $method) {
			$client->$method('E');
		}
		$expected = [['Prado.Element.j' => ['E', 'effect', [['times' => 3, 'effect' => 'bounce']]]]];
		foreach ($methods as $method) {
			$expected[] = ['Prado.Element.j' => ['E', 'effect', [['effect' => $method]]]];
		}
		$this->assertSame($expected, $page->getClientFunctions());
	}

	public function testDeprecatedEffectsSendTheirJQueryUiEffects()
	{
		$page = new TTestCallbackPage();
		$client = $page->getCallbackClient();
		$client->blindDown('E');
		$client->blindUp('E');
		$client->dropOut('E');
		$client->grow('E');
		$client->shrink('E');
		$client->squish('E');
		$client->switchOff('E');
		$effect = fn (array $options) => ['Prado.Element.j' => ['E', 'effect', [$options]]];
		$this->assertSame([
			$effect(['direction' => 'down', 'effect' => 'blind']),
			$effect(['direction' => 'up', 'effect' => 'blind']),
			$effect(['effect' => 'drop']),
			$effect(['effect' => 'size']),
			$effect(['percent' => 0, 'effect' => 'scale']),
			$effect(['origin' => ['top', 'left'], 'percent' => 0, 'effect' => 'scale']),
			$effect(['direction' => 'vertical', 'percent' => 0, 'effect' => 'scale']),
		], $page->getClientFunctions());
	}

	public function testReplaceWithAStringOrAnOrdinaryWriterSendsNoBoundary()
	{
		$page = new TTestCallbackPage();
		$client = $page->getCallbackClient();
		$client->replaceContent('E', 'html', false);
		$client->replaceContent('E', new THtmlWriter(new TTextWriter()));
		$client->evaluateScript('alert(1)');
		$client->evaluateScript(new THtmlWriter(new TTextWriter()));
		$client->appendScriptBlock('ignored');
		$client->appendScriptBlock(new THtmlWriter(new TTextWriter()));
		$this->assertSame([
			['Prado.Element.replace' => ['E', 'html', null, false]],
			['Prado.Element.replace' => ['E', null, null, true]],
			['Prado.Element.evaluateScript' => ['alert(1)', null]],
			['Prado.Element.evaluateScript' => [null, null]],
			['Prado.Element.appendScriptBlock' => [null]],
			['Prado.Element.appendScriptBlock' => [null]],
		], $page->getClientFunctions());
	}

	public function testReplaceWithAResponseWriterSendsItsBoundary()
	{
		[$page, $client] = $this->createAdaptedPage();
		$writer = $page->getResponse()->createHtmlWriter();
		$this->assertInstanceOf(TCallbackResponseWriter::class, $writer->getWriter());
		$boundary = $writer->getWriter()->getBoundary();
		$client->replaceContent('E', $writer, true, true);
		$client->evaluateScript($writer);
		$client->appendScriptBlock($writer);
		$this->assertSame([
			['Prado.Element.replace' => ['E', null, $boundary, true, true]],
			['Prado.Element.evaluateScript' => [null, $boundary]],
			['Prado.Element.appendScriptBlock' => [$boundary]],
		], $page->getClientFunctions());
	}

	public function testCallClientFunctionResolvesAScalarControl()
	{
		$page = new TTestCallbackPage();
		$label = $this->addLabel($page);
		$box = $this->addCheckBox($page);
		$client = $page->getCallbackClient();
		$client->callClientFunction('f', $label);
		$client->callClientFunction('g', $box);
		$client->jQuery($label, 'm', ['p']);
		$client->jQuery($box, 'm', 'p');
		$this->assertSame([
			['f' => [$label->getClientID()]],
			['g' => [$box->getSurroundingTagID()]],
			['Prado.Element.j' => [$label->getClientID(), 'm', ['p']]],
			['Prado.Element.j' => [$box->getSurroundingTagID(), 'm', ['p']]],
		], $page->getClientFunctions());
	}

	public function testNotifyTakesAClickTargetID()
	{
		$page = new TTestCallbackPage();
		$client = $page->getCallbackClient();
		$client->notify('t', [], 'Target');
		$client->notify('t', ['body' => 'b'], 'Target', 'p');
		$label = $this->addLabel($page);
		$client->notify('t', [], $label);
		$this->assertSame([
			['Prado.Notification.show' => ['t', ['EventTarget' => 'Target']]],
			['Prado.Notification.show' => ['t', ['body' => 'b', 'EventTarget' => 'Target', 'CallbackParameter' => 'p']]],
			['Prado.Notification.show' => ['t', ['EventTarget' => $label->getUniqueID()]]],
		], $page->getClientFunctions());
	}

	public function testSelectWithAnExplicitType()
	{
		$page = new TTestCallbackPage();
		$list = new TDropDownList();
		$list->setID('List');
		$page->getControls()->add($list);
		$box = $this->addCheckBox($page);
		$client = $page->getCallbackClient();
		$client->select($list, 'Value', 'a', 'check');
		$client->select($box, 'Value', true, 'select');
		$client->select('Raw', 'Value', 'v', 'select');
		$this->assertSame([
			['Prado.Element.select' => [$list->getClientID(), 'checkValue', 'a', 0]],
			['Prado.Element.select' => [$box->getClientID(), 'selectValue', true, 1]],
			['Prado.Element.select' => ['Raw', 'selectValue', 'v', 1]],
		], $page->getClientFunctions());
	}

	public function testSetSuggestionListItemsSkipsItemsWithoutAnOption()
	{
		$page = new TTestCallbackPage();
		$list = new TSuggestionList();
		$list->setID('Cities');
		$page->getControls()->add($list);
		$disabled = new TListItem('Gone', 'gone');
		$disabled->setEnabled(false);
		$client = $page->getCallbackClient();
		$client->setSuggestionListItems($list, []);
		$client->setSuggestionListItems($list, [$disabled]);
		$this->assertSame([
			['Prado.Element.setDataListOptions' => [$list->getClientID(), []]],
			['Prado.Element.setDataListOptions' => [$list->getClientID(), []]],
		], $page->getClientFunctions());
	}

	public function testReplaceWithAControlMorphs()
	{
		[$page, $client] = $this->createAdaptedPage();
		$label = $this->addLabel($page);
		$client->replaceContent('E', $label, true, true);
		$functions = $page->getClientFunctions();
		$this->assertTrue($functions[0]['Prado.Element.replace'][4]);
		$this->assertNotNull($functions[0]['Prado.Element.replace'][2]);
	}

	public function testToggleWithoutAnEffectTogglesAtOnce()
	{
		$page = new TTestCallbackPage();
		$page->getCallbackClient()->toggle('E');
		$this->assertSame([['Prado.Element.j' => ['E', 'toggle', ['duration' => 0]]]], $page->getClientFunctions());
	}
}
