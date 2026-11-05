<?php

/**
 * TReferenceList class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;

/**
 * TReferenceList class
 * TReferenceList renders the notes of the {@see TFootnote} references on the page
 * as a numbered list, in a `<section>` headed by {@see setHeaderText() HeaderText}
 * in the `<h1>` through `<h6>` of {@see setHeaderLevel() HeaderLevel}.
 * ```html
 * <com:TReferenceList HeaderText="Notes" />
 * ```
 * renders
 * ```html
 * <section id="…" aria-labelledby="…_label">
 *     <h2 id="…_label">Notes</h2>
 *     <ol>
 *         <li id="…_note1">Like ASP.NET Web Forms. <a href="#…_ref" aria-label="Back to reference 1">↩</a></li>
 *     </ol>
 * </section>
 * ```
 * The notes are numbered in page order when the page renders, so the list may be
 * before or after its footnotes. A note with several references, joined by
 * {@see TFootnote::setKey() Key}, has a back link to each, lettered a, b, c.
 * {@see setBackLinkText() BackLinkText} is the back link text and
 * {@see setBackLinkLabel() BackLinkLabel} its accessible name, with `{0}` for the
 * reference. A list without notes renders nothing. {@see setEncode() Encode}
 * HTML-encodes HeaderText and BackLinkText.
 * A callback that renders footnotes again also renders their list again, so the
 * numbers and notes match.
 * Accessibility: the section is a region named by its heading, and the heading
 * is in the screen reader's heading list. Each back link has a name that says
 * which reference it returns to, since the arrow alone does not. A list without
 * HeaderText has no heading, and its references have no description.
 * The default tag is `section`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TReferenceList extends THtmlElement
{
	use TEncodeTrait;

	/**
	 * @var ?array the notes in page order, each `['note' => TFootnote, 'refs' => TFootnote[]]`.
	 */
	private ?array $_notes = null;

	/**
	 * @var array the note number of each footnote, by object ID.
	 */
	private array $_numbers = [];

	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'section';
	}

	/**
	 * @return string the heading of the list. Defaults to 'Notes'.
	 */
	public function getHeaderText()
	{
		return $this->getViewState('HeaderText', 'Notes');
	}

	/**
	 * @param string $value the heading of the list; '' renders no heading.
	 */
	public function setHeaderText($value)
	{
		$this->setViewState('HeaderText', TPropertyValue::ensureString($value), 'Notes');
	}

	/**
	 * @return int the level of the heading, 1 through 6. Defaults to 2.
	 */
	public function getHeaderLevel()
	{
		return $this->getViewState('HeaderLevel', 2);
	}

	/**
	 * @param int $value the level of the heading, 1 through 6.
	 * @throws TInvalidDataValueException if the level is not 1 through 6.
	 */
	public function setHeaderLevel($value)
	{
		$value = TPropertyValue::ensureInteger($value);
		if ($value < 1 || $value > 6) {
			throw new TInvalidDataValueException('referencelist_headerlevel_invalid', $value);
		}
		$this->setViewState('HeaderLevel', $value, 2);
	}

	/**
	 * @return string the text of a back link. Defaults to '↩'.
	 */
	public function getBackLinkText()
	{
		return $this->getViewState('BackLinkText', '↩');
	}

	/**
	 * @param string $value the text of a back link.
	 */
	public function setBackLinkText($value)
	{
		$this->setViewState('BackLinkText', TPropertyValue::ensureString($value), '↩');
	}

	/**
	 * @return string the accessible name of a back link, `{0}` being the reference.
	 *   Defaults to 'Back to reference {0}'.
	 */
	public function getBackLinkLabel()
	{
		return $this->getViewState('BackLinkLabel', 'Back to reference {0}');
	}

	/**
	 * @param string $value the accessible name of a back link, `{0}` being the reference.
	 */
	public function setBackLinkLabel($value)
	{
		$this->setViewState('BackLinkLabel', TPropertyValue::ensureString($value), 'Back to reference {0}');
	}

	/**
	 * @return string the client ID of the heading, which describes the references.
	 */
	public function getHeaderClientID()
	{
		return $this->getClientID() . '_label';
	}

	/**
	 * @param int $number the note number.
	 * @return string the client ID of the note, the target of its references.
	 */
	public function getNoteClientID($number)
	{
		return $this->getClientID() . '_note' . $number;
	}

	/**
	 * Clears the notes, so they are collected again for this render.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->_notes = null;
		$this->_numbers = [];
	}

	/**
	 * Returns the notes of the visible footnotes in this list, in page order. The
	 * first footnote with child controls gives a keyed note its content.
	 * @return array the notes, each `['note' => TFootnote, 'refs' => TFootnote[]]`.
	 */
	public function getNotes()
	{
		if ($this->_notes !== null) {
			return $this->_notes;
		}
		$this->_notes = [];
		$this->_numbers = [];
		$keys = [];
		$page = $this->getPage();
		foreach ($page !== null ? $page->findControlsByType(TFootnote::class, false) : [] as $footnote) {
			if (!$footnote->getVisible(true) || !$this->isListOf($footnote)) {
				continue;
			}
			$key = $footnote->getKey();
			if ($key !== '' && isset($keys[$key])) {
				$index = $keys[$key];
				$this->_notes[$index]['refs'][] = $footnote;
				if (!$this->_notes[$index]['note']->getHasControls() && $footnote->getHasControls()) {
					$this->_notes[$index]['note'] = $footnote;
				}
			} else {
				$index = count($this->_notes);
				$this->_notes[] = ['note' => $footnote, 'refs' => [$footnote]];
				if ($key !== '') {
					$keys[$key] = $index;
				}
			}
			$this->_numbers[spl_object_id($footnote)] = $index + 1;
		}
		return $this->_notes;
	}

	/**
	 * @param TFootnote $footnote a footnote.
	 * @return ?int the note number of the footnote, or null when it is not in this list.
	 */
	public function getNoteNumber($footnote)
	{
		$this->getNotes();
		return $this->_numbers[spl_object_id($footnote)] ?? null;
	}

	/**
	 * @param TFootnote $footnote a footnote.
	 * @return bool whether the footnote's note is in this list.
	 */
	protected function isListOf($footnote)
	{
		try {
			return $footnote->getReferenceListControl() === $this;
		} catch (TConfigurationException $e) {
			return false;
		}
	}

	/**
	 * Renders the list when it has notes.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		if ($this->getNotes() !== []) {
			parent::render($writer);
		}
	}

	/**
	 * Adds the `id` the references and back links use, and `aria-labelledby` of the
	 * heading when HeaderText is set.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$this->setEnsureId(true);
		parent::addAttributesToRender($writer);
		if ($this->getHeaderText() !== '') {
			$writer->addAttribute('aria-labelledby', $this->getHeaderClientID());
		}
	}

	/**
	 * Renders the heading, then the numbered notes with their back links.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		if (($text = $this->getHeaderText()) !== '') {
			$writer->addAttribute('id', $this->getHeaderClientID());
			$writer->renderBeginTag('h' . $this->getHeaderLevel());
			$writer->write($this->encodeText($text));
			$writer->renderEndTag();
		}
		$writer->renderBeginTag('ol');
		foreach ($this->getNotes() as $index => $entry) {
			$number = $index + 1;
			$writer->addAttribute('id', $this->getNoteClientID($number));
			$writer->renderBeginTag('li');
			$entry['note']->renderNote($writer);
			$this->renderBackLinks($writer, $number, $entry['refs']);
			$writer->renderEndTag();
		}
		$writer->renderEndTag();
	}

	/**
	 * Renders a back link to each reference of a note, lettered when there are several.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @param int $number the note number.
	 * @param TFootnote[] $refs the references of the note.
	 */
	protected function renderBackLinks($writer, $number, $refs)
	{
		$lettered = count($refs) > 1;
		foreach ($refs as $i => $ref) {
			$letter = $lettered ? $this->getReferenceLetter($i) : '';
			$writer->write(' ');
			$writer->addAttribute('href', '#' . $ref->getReferenceClientID());
			$writer->addAttribute('aria-label', strtr($this->getBackLinkLabel(), ['{0}' => $number . $letter]));
			$writer->renderBeginTag('a');
			$writer->write($this->encodeText($this->getBackLinkText()) . $letter);
			$writer->renderEndTag();
		}
	}

	/**
	 * @param int $index the zero-based index of a reference.
	 * @return string the letter of the reference: a through z, then aa, ab, ….
	 */
	protected function getReferenceLetter($index)
	{
		$letter = '';
		for ($index++; $index > 0; $index = intdiv($index - 1, 26)) {
			$letter = chr(97 + ($index - 1) % 26) . $letter;
		}
		return $letter;
	}
}
