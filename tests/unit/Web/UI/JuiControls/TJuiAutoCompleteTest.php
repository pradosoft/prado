<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TPageLifecycleTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestLifecyclePage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActivePageAdapter;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\JuiControls\IJuiOptions;
use Prado\Web\UI\JuiControls\TJuiAutoComplete;
use Prado\Web\UI\JuiControls\TJuiAutoCompleteEventParameter;
use Prado\Web\UI\JuiControls\TJuiControlAdapter;
use Prado\Web\UI\JuiControls\TJuiControlOptions;
use Prado\Web\UI\TForm;
use Prado\Web\UI\WebControls\THead;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TRepeater;

/**
 * Unit tests for {@see TJuiAutoComplete}.
 */
class TJuiAutoCompleteTest extends TestCase
{
	use TPageLifecycleTrait;

	/** @var int the output buffer level before the test; the application response may start a buffer */
	private int $_obLevel = 0;

	protected function setUp(): void
	{
		$this->_obLevel = ob_get_level();
		$this->resetPageState();
	}

	protected function tearDown(): void
	{
		$this->resetPageState();
		while (ob_get_level() > $this->_obLevel) {
			ob_end_clean();
		}
	}

	private function newControl(): TJuiAutoComplete
	{
		$control = new TJuiAutoComplete();
		$control->setID('Auto');
		return $control;
	}

	/**
	 * @param ?callable $configure configures the control on every run, receives the control and the page
	 * @return TTestLifecyclePage a page with the control `Auto` in its form
	 */
	private function newPage(?callable $configure = null): TTestLifecyclePage
	{
		$page = new TTestLifecyclePage();
		$page->build = function (TTestLifecyclePage $page, TForm $form) use ($configure) {
			$page->getControls()->insertAt(0, new THead());
			$control = $this->newControl();
			$form->getControls()->add($control);
			if ($configure) {
				$configure($control, $page);
			}
		};
		return $page;
	}

	/**
	 * Runs the page for a GET, then a new page for a callback to the control.
	 * @param mixed $parameter
	 * @param callable $configure
	 */
	private function runAutoCallback(mixed $parameter, callable $configure): TTestLifecyclePage
	{
		$this->runPage($this->newPage($configure));
		$page = $this->newPage($configure);
		$this->runCallback($page, 'Auto', $parameter);
		return $page;
	}

	public function testWidgetAndAdapter(): void
	{
		$control = $this->newControl();
		$this->assertInstanceOf(IJuiOptions::class, $control);
		$this->assertInstanceOf(TJuiControlAdapter::class, $control->getAdapter());
		$this->assertSame('autocomplete', $control->getWidget());
		$this->assertSame('Auto', $control->getWidgetID());
		$this->assertSame(['appendTo', 'autoFocus', 'delay', 'disabled', 'minLength', 'position', 'source'], $control->getValidOptions());
		$this->assertSame([], $control->getValidEvents());
		$this->assertSame('Prado.WebUI.TJuiAutoComplete', PradoUnit::invoke($control, 'getClientClassName'));
	}

	public function testOptionsAreKeptInViewState(): void
	{
		$control = $this->newControl();
		$options = $control->getOptions();
		$this->assertInstanceOf(TJuiControlOptions::class, $options);
		$this->assertSame($options, $control->getOptions());
		$this->assertSame($options, $control->getViewState('JuiOptions'));
	}

	public function testTextCssClass(): void
	{
		$control = $this->newControl();
		$this->assertNull($control->getTextCssClass());
		$control->setTextCssClass('word');
		$this->assertSame('word', $control->getTextCssClass());
	}

	public function testSeparator(): void
	{
		$control = $this->newControl();
		$this->assertSame('', $control->getSeparator());
		$control->setSeparator(',;');
		$this->assertSame(',;', $control->getSeparator());
	}

	public function testFrequency(): void
	{
		$control = $this->newControl();
		$this->assertSame(0.4, $control->getFrequency());
		$control->setFrequency('1.5');
		$this->assertSame(1.5, $control->getFrequency());
	}

	public function testMinChars(): void
	{
		$control = $this->newControl();
		$control->setMinChars('3');
		$this->assertSame(3, $control->getMinChars());
	}

	public function testResultPanelIsAChildPanel(): void
	{
		$control = $this->newControl();
		$panel = $control->getResultPanel();
		$this->assertInstanceOf(TPanel::class, $panel);
		$this->assertSame($panel, $control->getResultPanel());
		$this->assertSame('result', $panel->getID());
		$this->assertSame($control, $panel->getParent());
	}

	public function testSuggestionsIsAChildRepeater(): void
	{
		$control = $this->newControl();
		$repeater = $control->getSuggestions();
		$this->assertInstanceOf(TRepeater::class, $repeater);
		$this->assertSame($repeater, $control->getSuggestions());
		$this->assertSame($control, $repeater->getParent());
	}

	/**
	 * @param mixed $parameter
	 * @param bool $autoPostBack
	 * @return array the events the control raised for a callback parameter, as [event, parameter]
	 */
	private function raiseCallback(mixed $parameter, bool $autoPostBack = false): array
	{
		$control = $this->newControl();
		$control->setAutoPostBack($autoPostBack);
		$raised = [];
		foreach (['OnSuggest', 'OnSuggestionSelected', 'OnCallback'] as $event) {
			$control->attachEventHandler($event, function ($sender, $param) use (&$raised, $event) {
				$raised[] = [$event, $param];
			});
		}
		$control->raiseCallbackEvent(new TCallbackEventParameter(null, $parameter));
		return $raised;
	}

	public function testRaiseCallbackEventRaisesOnSuggestWithTheToken(): void
	{
		$raised = $this->raiseCallback(['ab', '__TJuiAutoComplete_onSuggest__']);
		$this->assertCount(1, $raised);
		$this->assertSame('OnSuggest', $raised[0][0]);
		$this->assertInstanceOf(TJuiAutoCompleteEventParameter::class, $raised[0][1]);
		$this->assertSame('ab', $raised[0][1]->getToken());
		$this->assertSame(-1, $raised[0][1]->getSelectedIndex());
	}

	public function testRaiseCallbackEventRaisesOnSuggestionSelectedWithTheIndex(): void
	{
		$raised = $this->raiseCallback([2, '__TJuiAutoComplete_onSuggestionSelected__']);
		$this->assertCount(1, $raised);
		$this->assertSame('OnSuggestionSelected', $raised[0][0]);
		$this->assertSame(2, $raised[0][1]->getSelectedIndex());
		$this->assertNull($raised[0][1]->getToken());
	}

	public function testRaiseCallbackEventIgnoresAnUnknownPair(): void
	{
		$this->assertSame([], $this->raiseCallback(['ab', 'other'], true));
	}

	public function testRaiseCallbackEventRaisesOnCallbackOnlyWithAutoPostBack(): void
	{
		$this->assertSame([], $this->raiseCallback('text'));
		$this->assertSame([], $this->raiseCallback(['a', 'b', 'c']));
		foreach (['text', ['a', 'b', 'c']] as $parameter) {
			$raised = $this->raiseCallback($parameter, true);
			$this->assertCount(1, $raised);
			$this->assertSame('OnCallback', $raised[0][0]);
		}
	}

	public function testPostBackOptions(): void
	{
		$control = $this->newControl();
		$control->getOptions()->autoFocus = 'true';
		$control->setMinChars(2);
		$control->setFrequency(0.25);
		$this->assertSame([
			'autoFocus' => true,
			'minLength' => 2,
			'delay' => 250.0,
			'appendTo' => '#Auto_result',
			'ID' => 'Auto',
			'EventTarget' => 'Auto',
			'CausesValidation' => true,
			'ValidationGroup' => '',
		], PradoUnit::invoke($control, 'getPostBackOptions'));
	}

	/**
	 * Separator, AutoPostBack, and TextCssClass each add their options.
	 */
	public static function postBackOptionProvider(): array
	{
		$cases = [];
		foreach ([false, true] as $separator) {
			foreach ([false, true] as $autoPostBack) {
				foreach ([false, true] as $textCssClass) {
					$cases[sprintf('separator %d, autopostback %d, css %d', $separator, $autoPostBack, $textCssClass)] = [$separator, $autoPostBack, $textCssClass];
				}
			}
		}
		return $cases;
	}

	/**
	 * @dataProvider postBackOptionProvider
	 * @param bool $separator
	 * @param bool $autoPostBack
	 * @param bool $textCssClass
	 */
	public function testPostBackOptionsWithSeparatorAutoPostBackAndTextCssClass(bool $separator, bool $autoPostBack, bool $textCssClass): void
	{
		$control = $this->newControl();
		if ($separator) {
			$control->setSeparator(',');
		}
		$control->setAutoPostBack($autoPostBack);
		if ($textCssClass) {
			$control->setTextCssClass('word');
		}
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame($separator ? ',' : null, $options['Separators'] ?? null);
		$this->assertSame($textCssClass ? 'word' : null, $options['textCssClass'] ?? null);
		$this->assertSame($autoPostBack ? true : null, $options['AutoPostBack'] ?? null);
		$this->assertSame($autoPostBack ? 'SingleLine' : null, $options['TextMode'] ?? null);
	}

	public function testRenderAddsTheResultPanelAndTheClientScript(): void
	{
		$output = $this->runPage($this->newPage(function ($control) {
			$control->setMinChars(2);
		}));
		$this->assertMatchesRegularExpression('#<input [^>]*id="Auto"[^>]*/><div id="Auto_result">\s*</div>#', $output);
		$this->assertStringContainsString('new Prado.WebUI.TJuiAutoComplete({', $output);
		$this->assertStringContainsString("'appendTo':\"#Auto_result\"", $output);
		$this->assertStringNotContainsString("jQuery('#Auto').autocomplete(", $output);
	}

	public function testDataBindOutsideACallbackRendersNoSuggestions(): void
	{
		$output = $this->runPage($this->newPage(function ($control) {
			$control->setDataSource(['x']);
			$control->dataBind();
		}));
		$this->assertStringNotContainsString('>x<', $output);
		$this->assertArrayNotHasKey(TActivePageAdapter::CALLBACK_DATA_HEADER, $this->getCallbackParts());
	}

	/**
	 * Binding the suggestions in the OnSuggest handler sends each rendered item as the response data.
	 */
	public function testSuggestCallbackSendsTheRenderedSuggestions(): void
	{
		$this->runAutoCallback(['ab', '__TJuiAutoComplete_onSuggest__'], function ($control) {
			$control->attachEventHandler('OnSuggest', function ($sender, $param) {
				$sender->setDataSource([$param->getToken() . '1', $param->getToken() . '2']);
				$sender->dataBind();
			});
		});
		$this->assertSame(
			[['id' => 0, 'label' => 'ab1'], ['id' => 1, 'label' => 'ab2']],
			json_decode($this->getCallbackParts()[TActivePageAdapter::CALLBACK_DATA_HEADER], true)
		);
	}

	public function testSuggestCallbackWithOneSuggestion(): void
	{
		$this->runAutoCallback(['ab', '__TJuiAutoComplete_onSuggest__'], function ($control) {
			$control->attachEventHandler('OnSuggest', function ($sender) {
				$sender->setDataSource(['only']);
				$sender->dataBind();
			});
		});
		$this->assertSame(
			[['id' => 0, 'label' => 'only']],
			json_decode($this->getCallbackParts()[TActivePageAdapter::CALLBACK_DATA_HEADER], true)
		);
	}

	public function testSuggestCallbackWithoutSuggestionsSendsAnEmptyList(): void
	{
		$this->runAutoCallback(['ab', '__TJuiAutoComplete_onSuggest__'], function ($control) {
			$control->attachEventHandler('OnSuggest', function ($sender) {
				$sender->setDataSource([]);
				$sender->dataBind();
			});
		});
		$this->assertSame('[]', $this->getCallbackParts()[TActivePageAdapter::CALLBACK_DATA_HEADER]);
	}

	public function testRenderCallbackOutsideACallbackSendsNothing(): void
	{
		$this->runPage($this->newPage(function ($control, $page) {
			$page->load = function () use ($control) {
				$control->getSuggestions()->setDataSource(['z']);
				$control->getSuggestions()->dataBind();
				$control->renderCallback($control->getResponse()->createHtmlWriter());
			};
		}));
		$this->assertNull($this->getLifecycleResponse()->getAdapter());
	}

	public function testRenderCallbackSendsTheSuggestions(): void
	{
		$this->runAutoCallback(['a', '__TJuiAutoComplete_onSuggest__'], function ($control) {
			$control->attachEventHandler('OnSuggest', function ($sender, $param) {
				$sender->getSuggestions()->setDataSource(['z']);
				$sender->getSuggestions()->dataBind();
				$sender->renderCallback($param->getNewWriter());
			});
		});
		$this->assertSame(
			[['id' => 0, 'label' => 'z']],
			json_decode($this->getCallbackParts()[TActivePageAdapter::CALLBACK_DATA_HEADER], true)
		);
	}
}
