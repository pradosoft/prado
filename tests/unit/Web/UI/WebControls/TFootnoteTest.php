<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TFootnote;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TReferenceList;
use Prado\Web\UI\WebControls\TSup;

class TFootnoteTest extends TestCase
{
	use TWebControlRenderTrait;

	private TPage $page;

	protected function setUp(): void
	{
		$this->page = new TPage();
	}

	private function footnote(string $id, string $note = '', string $key = '', $parent = null): TFootnote
	{
		$footnote = new TFootnote();
		$footnote->setID($id);
		$footnote->setKey($key);
		if ($note !== '') {
			$footnote->getControls()->add($note);
		}
		($parent ?? $this->page)->getControls()->add($footnote);
		return $footnote;
	}

	private function list(string $id = 'notes', $parent = null): TReferenceList
	{
		$list = new TReferenceList();
		$list->setID($id);
		($parent ?? $this->page)->getControls()->add($list);
		return $list;
	}

	public function testDefaults()
	{
		$footnote = new TFootnote();
		$this->assertInstanceOf(TSup::class, $footnote);
		$this->assertInstanceOf(THtmlElement::class, $footnote);
		$this->assertSame('sup', $footnote->getTagName());
		$this->assertSame('', $footnote->getReferenceList());
		$this->assertSame('', $footnote->getKey());
		$list = new TReferenceList();
		$this->assertSame('section', $list->getTagName());
		$this->assertSame('Notes', $list->getHeaderText());
		$this->assertSame(2, $list->getHeaderLevel());
		$this->assertSame('↩', $list->getBackLinkText());
		$this->assertSame('Back to reference {0}', $list->getBackLinkLabel());
		$this->assertFalse($list->getEncode());
	}

	public function testReferenceRendersLinkToNote()
	{
		$footnote = $this->footnote('fn', 'First note.');
		$this->list();
		$this->assertSame('<sup id="fn"><a id="fn_ref" href="#notes_note1" aria-describedby="notes_label">1</a></sup>', $this->render($footnote));
	}

	public function testListRendersNotesWithBackLinks()
	{
		$this->footnote('a', 'First.');
		$this->footnote('b', 'Second.');
		$list = $this->list();
		$this->assertSame(
			'<section id="notes" aria-labelledby="notes_label"><h2 id="notes_label">Notes</h2><ol>'
			. '<li id="notes_note1">First. <a href="#a_ref" aria-label="Back to reference 1">↩</a></li>'
			. '<li id="notes_note2">Second. <a href="#b_ref" aria-label="Back to reference 2">↩</a></li>'
			. '</ol></section>',
			$this->render($list)
		);
	}

	public function testListBeforeFootnotesNumbersInPageOrder()
	{
		$list = $this->list();
		$a = $this->footnote('a', 'First.');
		$b = $this->footnote('b', 'Second.');
		$this->assertSame(1, $list->getNoteNumber($a));
		$this->assertSame(2, $list->getNoteNumber($b));
		$this->assertStringContainsString('<li id="notes_note2">Second.', $this->render($list));
	}

	public function testKeyedFootnotesShareOneNote()
	{
		$a = $this->footnote('a', '', 'src');
		$b = $this->footnote('b', 'Shared source.', 'src');
		$c = $this->footnote('c', 'Other.');
		$list = $this->list();
		$this->assertSame(1, $list->getNoteNumber($a));
		$this->assertSame(1, $list->getNoteNumber($b));
		$this->assertSame(2, $list->getNoteNumber($c));
		$this->assertCount(2, $list->getNotes());
		$this->assertStringContainsString(
			'<li id="notes_note1">Shared source. <a href="#a_ref" aria-label="Back to reference 1a">↩a</a> <a href="#b_ref" aria-label="Back to reference 1b">↩b</a></li>',
			$this->render($list)
		);
	}

	public function testInvisibleFootnoteHasNoNote()
	{
		$a = $this->footnote('a', 'Hidden.');
		$a->setVisible(false);
		$panel = new TPanel();
		$panel->setVisible(false);
		$this->page->getControls()->add($panel);
		$this->footnote('b', 'In hidden panel.', '', $panel);
		$c = $this->footnote('c', 'Shown.');
		$list = $this->list();
		$this->assertNull($list->getNoteNumber($a));
		$this->assertSame(1, $list->getNoteNumber($c));
	}

	public function testReferenceListSelectsList()
	{
		$first = $this->list('first');
		$second = $this->list('second');
		$a = $this->footnote('a', 'To first.');
		$b = $this->footnote('b', 'To second.');
		$b->setReferenceList('second');
		$this->assertSame($first, $a->getReferenceListControl());
		$this->assertSame($second, $b->getReferenceListControl());
		$this->assertSame(1, $second->getNoteNumber($b));
		$this->assertNull($first->getNoteNumber($b));
		$this->assertStringContainsString('href="#second_note1"', $this->render($b));
	}

	public function testMissingListThrows()
	{
		$footnote = $this->footnote('a', 'Lost.');
		$this->expectException(TConfigurationException::class);
		$this->render($footnote);
	}

	public function testReferenceListNotAListThrows()
	{
		$this->list();
		$footnote = $this->footnote('a', 'Lost.');
		$footnote->setReferenceList('a');
		$this->expectException(TConfigurationException::class);
		$footnote->getReferenceListControl();
	}

	public function testEmptyListRendersNothing()
	{
		$this->assertSame('', $this->render($this->list()));
	}

	public function testNoHeaderText()
	{
		$footnote = $this->footnote('a', 'Note.');
		$list = $this->list();
		$list->setHeaderText('');
		$this->assertSame('<sup id="a"><a id="a_ref" href="#notes_note1">1</a></sup>', $this->render($footnote));
		$this->assertSame('<section id="notes"><ol><li id="notes_note1">Note. <a href="#a_ref" aria-label="Back to reference 1">↩</a></li></ol></section>', $this->render($list));
	}

	public function testHeaderAndBackLinkProperties()
	{
		$this->footnote('a', 'Note.');
		$list = $this->list();
		$list->setHeaderText('<References>');
		$list->setHeaderLevel('3');
		$list->setBackLinkText('Back');
		$list->setBackLinkLabel('Return to {0}');
		$list->setEncode(true);
		$this->assertSame(
			'<section id="notes" aria-labelledby="notes_label"><h3 id="notes_label">&lt;References&gt;</h3><ol>'
			. '<li id="notes_note1">Note. <a href="#a_ref" aria-label="Return to 1">Back</a></li></ol></section>',
			$this->render($list)
		);
	}

	public function testHeaderLevelOutOfRangeThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TReferenceList())->setHeaderLevel(7);
	}

	public function testOnPreRenderCollectsNotesAgain()
	{
		$list = $this->list();
		$a = $this->footnote('a', 'First.');
		$this->assertSame(1, $list->getNoteNumber($a));
		$b = new TFootnote();
		$b->setID('b');
		$this->page->getControls()->insertAt(0, $b);
		$this->assertSame(1, $list->getNoteNumber($a));
		$list->onPreRender(null);
		$this->assertSame(1, $list->getNoteNumber($b));
		$this->assertSame(2, $list->getNoteNumber($a));
	}

	public function testReferenceLetters()
	{
		$list = new TReferenceList();
		$letters = array_map(fn ($i) => \Prado\Test\Unit\PradoUnit::invoke($list, 'getReferenceLetter', $i), [0, 1, 25, 26, 27, 51, 52]);
		$this->assertSame(['a', 'b', 'z', 'aa', 'ab', 'az', 'ba'], $letters);
	}
}
