<?php

/**
 * TFootnote class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;

/**
 * TFootnote class
 * TFootnote is a numbered footnote reference. It renders a superscript link to
 * its note, and its child controls are the note, which renders in a
 * {@see TReferenceList}.
 * ```html
 * <p>Prado is component based.<com:TFootnote>Like ASP.NET Web Forms.</com:TFootnote></p>
 * <com:TReferenceList />
 * ```
 * renders `<sup><a id="…_ref" href="#…_note1" aria-describedby="…_label">1</a></sup>`
 * in the paragraph and the note in the list.
 * - {@see setReferenceList() ReferenceList} is the ID path of the list, found
 *   from the footnote's naming container. When empty, the note goes to the first
 *   TReferenceList on the page.
 * - Footnotes with the same {@see setKey() Key} in a list are one note: the first
 *   with child controls gives its content, all share its number, and the note
 *   links back to each reference.
 * A footnote that is not visible has no number and no note.
 * Accessibility: the reference is a link whose name is the note number and whose
 * description is the heading of the list, so a screen reader announces
 * "1, link, Notes". Following it moves to the note; the note's back link returns.
 * The default tag is `sup`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFootnote extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'sup';
	}

	/**
	 * @return string the ID path of the TReferenceList holding the note. Defaults to '',
	 *   the first TReferenceList on the page.
	 */
	public function getReferenceList()
	{
		return $this->getViewState('ReferenceList', '');
	}

	/**
	 * @param string $value the ID path of the TReferenceList holding the note.
	 */
	public function setReferenceList($value)
	{
		$this->setViewState('ReferenceList', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the key that joins footnotes into one note. Defaults to ''; each
	 *   footnote without a key is its own note.
	 */
	public function getKey()
	{
		return $this->getViewState('Key', '');
	}

	/**
	 * @param string $value the key that joins footnotes into one note.
	 */
	public function setKey($value)
	{
		$this->setViewState('Key', TPropertyValue::ensureString($value), '');
	}

	/**
	 * Returns the list that holds the note: {@see getReferenceList() ReferenceList}, or
	 * the first TReferenceList on the page.
	 * @throws TConfigurationException if no TReferenceList is found.
	 * @return TReferenceList the list holding the note.
	 */
	public function getReferenceListControl()
	{
		if (($id = $this->getReferenceList()) !== '') {
			$list = $this->findControl($id);
		} else {
			$lists = ($page = $this->getPage()) !== null ? $page->findControlsByType(TReferenceList::class, false) : [];
			$list = $lists[0] ?? null;
		}
		if (!($list instanceof TReferenceList)) {
			throw new TConfigurationException('footnote_referencelist_missing', $this->getUniqueID(), $id);
		}
		return $list;
	}

	/**
	 * @return string the client ID of the reference link, the target of the back link.
	 */
	public function getReferenceClientID()
	{
		return $this->getClientID() . '_ref';
	}

	/**
	 * Renders the reference link to the note.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		$list = $this->getReferenceListControl();
		if (($number = $list->getNoteNumber($this)) === null) {
			return;
		}
		$writer->addAttribute('id', $this->getReferenceClientID());
		$writer->addAttribute('href', '#' . $list->getNoteClientID($number));
		if ($list->getHeaderText() !== '') {
			$writer->addAttribute('aria-describedby', $list->getHeaderClientID());
		}
		$writer->renderBeginTag('a');
		$writer->write((string) $number);
		$writer->renderEndTag();
	}

	/**
	 * Renders the child controls, the content of the note, in the TReferenceList.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderNote($writer)
	{
		$this->renderChildren($writer);
	}
}
