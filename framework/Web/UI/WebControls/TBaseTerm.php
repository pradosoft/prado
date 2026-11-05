<?php

/**
 * TBaseTerm class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;
use Prado\Web\THttpUtility;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TRenderFilterParameter;

/**
 * TBaseTerm class
 *
 * TBaseTerm is the base class of the controls that render a term element, such
 * as `<abbr>`, and mark the other occurrences of their terms in a target control.
 * It renders its element, and an `onRenderFilter` handler on the target wraps each
 * occurrence of a term in the rendered output of the target in the same element.
 *
 * Properties:
 * - <b>Text</b>, string: the term, HTML-encoded for rendering. When it is empty,
 *   the body content of the component tag renders instead.
 * - <b>Title</b>, string: the `title` attribute of the element and of each mark of
 *   the term. It is the {@see TWebControl::getToolTip ToolTip}.
 * - <b>Terms</b>, array: more terms to mark, each mapped to its title. A list
 *   item is a term without a title. A template sets it as
 *   `Terms="CSS => Cascading Style Sheets, DOM => Document Object Model"`.
 * - <b>TargetControl</b>, string: the ID of the control whose output is marked.
 *   It is located via {@see \Prado\Web\UI\TControl::findControl}. Empty marks the
 *   output of the parent.
 * - <b>MarkOccurrences</b>, bool: whether the control marks its target. Defaults to true.
 * - <b>MarkLimit</b>, int: the number of occurrences of each term to mark; 0
 *   marks every occurrence. The elements of a term in the target count toward its limit.
 * - <b>DepthLimit</b>, int: the levels of controls below the target whose output
 *   is marked; 0 marks all output. Each child control of the target is one level.
 * - <b>CaseSensitive</b>, bool: whether the terms match case-sensitively. Defaults
 *   to true. A mark keeps the text as written.
 *
 * The term of the control is its Text, or the text of its body when the body holds
 * only text. A control without Text or body renders nothing, so it marks its Terms
 * without an element of its own. The term of the control replaces the same key of Terms.
 * ```html
 * <com:TPanel>
 *   <p><com:TAbbr Text="HTML" Title="HyperText Markup Language" /> pages use HTML and CSS.</p>
 *   <com:TAbbr Terms="CSS => Cascading Style Sheets" />
 * </com:TPanel>
 * ```
 *
 * A term matches whole words in the text of the target. The longest term matches
 * first, and the whitespace in a term matches any whitespace. Text in these places
 * is not marked:
 * - an element with a tag of {@see SKIP_TAGS}, such as `<abbr>`, `<code>`, `<pre>`,
 *   or `<script>`;
 * - attribute values;
 * - markup exempt from render filters ({@see TRenderFilterParameter::EXEMPT_ATTRIBUTE}
 *   and {@see \Prado\Web\UI\TControl::getRenderFilterExempt}).
 *
 * An element with `data-prado-filter-exempt="false"` re-enables marking inside an
 * element of {@see SKIP_TAGS}. A target output without an unmarked term does not
 * parse, so the output of the target is unchanged. An invisible control marks nothing.
 *
 * The handler attaches in {@see onPreRender}. During a callback, a control that
 * re-renders alone, such as a {@see \Prado\Web\UI\ActiveControls\TActivePanel}, is
 * marked when it is the target or inside it: the target filters fragments
 * ({@see \Prado\Web\UI\TControl::getRenderFilterFragments}). A fragment is not
 * marked when MarkLimit is set, since the marks elsewhere in the target are unknown.
 *
 * A DepthLimit raises the {@see \Prado\Web\UI\TControl::getRenderFilterDepth} of
 * the target to at least the limit, so the other render filters of the target see
 * the same levels or more. Each control marks the levels of its own DepthLimit
 * ({@see TRenderFilterParameter::setDepthLimit}).
 *
 * Accessibility: a mark keeps the text, so the names of links and buttons that
 * hold a term are unchanged. Do not add a `role` or `aria-label` to the control.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TBaseTerm extends THtmlElement implements \Prado\IDataRenderer
{
	/**
	 * The tags whose text the control does not mark: term, code, and raw text
	 * elements, form options, and foreign content.
	 */
	public const SKIP_TAGS = ['abbr', 'dfn', 'var', 'code', 'pre', 'kbd', 'samp', 'script', 'style',
		'textarea', 'title', 'option', 'optgroup', 'select', 'datalist', 'svg', 'math', 'noscript', 'iframe'];

	/**
	 * The raw text elements, whose content holds no markup.
	 */
	private const RAW_TEXT_TAGS = 'script|style|textarea|title';

	/**
	 * A character that continues a word.
	 */
	private const WORD_CHARACTER = '[\p{L}\p{N}\p{M}_]';

	/**
	 * @var bool whether the handler is on the target
	 */
	private bool $_targetFiltered = false;

	/**
	 * Attaches the handler that marks the terms to the target, and has the target
	 * filter the callback re-renders of its descendants
	 * ({@see \Prado\Web\UI\TControl::getRenderFilterFragments}) to at least the
	 * DepthLimit ({@see \Prado\Web\UI\TControl::getRenderFilterDepth}).
	 * @param \Prado\TEventParameter $param event parameter
	 * @throws TInvalidDataValueException if the TargetControl control cannot be found
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if (!$this->_targetFiltered && $this->getMarkOccurrences() && ($target = $this->getMarkTarget()) !== null) {
			$target->attachEventHandler('onRenderFilter', [$this, 'markTerms']);
			$target->setRenderFilterFragments(true);
			if (($depth = $this->getDepthLimit()) > $target->getRenderFilterDepth()) {
				$target->setRenderFilterDepth($depth);
			}
			$this->_targetFiltered = true;
		}
	}

	/**
	 * Renders the element, or nothing when the control has no Text or body.
	 * @param \Prado\Web\UI\THtmlWriter $writer the renderer
	 */
	public function render($writer)
	{
		if ($this->getHasContent()) {
			parent::render($writer);
		}
	}

	/**
	 * Renders the HTML-encoded Text, or the body content when Text is empty.
	 * @param \Prado\Web\UI\THtmlWriter $writer the renderer
	 */
	public function renderContents($writer)
	{
		if (($text = $this->getText()) === '') {
			parent::renderContents($writer);
		} else {
			$writer->write(THttpUtility::htmlEncode($text));
		}
	}

	/**
	 * @return bool whether the control has Text or body content to render
	 */
	public function getHasContent(): bool
	{
		return $this->getText() !== '' || $this->getHasControls();
	}

	/**
	 * Resolves {@see getTargetControl TargetControl} to the control whose output is marked.
	 * @throws TInvalidDataValueException if the TargetControl control cannot be found
	 * @return ?TControl the target control, or the parent when TargetControl is empty
	 */
	public function getMarkTarget(): ?TControl
	{
		if (($id = $this->getTargetControl()) === '') {
			return $this->getParent();
		}
		if (!($control = $this->findControl($id))) {
			throw new TInvalidDataValueException('baseterm_targetcontrol_invalid', get_class($this), $id);
		}
		return $control;
	}

	/**
	 * Returns the term of the control: its Text, or the text of a body that holds only text.
	 * @return string the term, '' when there is none
	 */
	public function getTerm(): string
	{
		if (($text = $this->getText()) !== '' || !$this->getHasControls()) {
			return trim($text);
		}
		$body = '';
		foreach ($this->getControls() as $control) {
			if (!is_string($control)) {
				return '';
			}
			$body .= $control;
		}
		return trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
	}

	/**
	 * Returns the terms to mark, each mapped to its title: the Terms, and the term
	 * of the control with its Title.
	 * @return array<string, string> the titles, by term
	 */
	public function getTermTitles(): array
	{
		$titles = [];
		foreach ($this->getTerms() as $key => $value) {
			$term = trim(is_int($key) ? (string) $value : (string) $key);
			if ($term !== '') {
				$titles[$term] = is_int($key) ? '' : (string) $value;
			}
		}
		if (($term = $this->getTerm()) !== '') {
			$titles[$term] = $this->getTitle();
		}
		return $titles;
	}

	/**
	 * Marks the occurrences of the terms in the rendered output of the target,
	 * to the DepthLimit. This is the `onRenderFilter` handler on the target. The
	 * handlers that follow see the depth of output they saw before.
	 * @param mixed $sender the target
	 * @param TRenderFilterParameter $param the rendered output of the target
	 */
	public function markTerms($sender, $param)
	{
		$depth = $param->getDepthLimit();
		$param->setDepthLimit($this->getDepthLimit());
		try {
			$this->markOutput($param);
		} finally {
			$param->setDepthLimit($depth);
		}
	}

	/**
	 * Marks the occurrences of the terms in the output that the handler sees.
	 * Output without an unmarked term does not parse. A fragment of the output of
	 * the target ({@see TRenderFilterParameter::getIsFragment}) is not marked when
	 * MarkLimit is set, since the marks elsewhere in the target are unknown.
	 * @param TRenderFilterParameter $param the rendered output of the target
	 */
	protected function markOutput(TRenderFilterParameter $param): void
	{
		if (($param->getIsFragment() && $this->getMarkLimit() > 0) || ($titles = $this->getMarkedTermTitles()) === []) {
			return;
		}
		$html = $param->getFilterText();
		$expression = $this->getTermExpression(array_keys($titles));
		if (preg_match($this->getUnmarkedTermPattern($expression), $html) === 0 || !($dom = $param->getFilterDOM())) {
			return;
		}
		$pattern = '/' . $expression . '/u' . ($this->getCaseSensitive() ? '' : 'i');
		$keyTitles = array_combine(array_map([$this, 'getTermKey'], array_keys($titles)), $titles);
		$counts = $this->getMarkCounts($dom, $keyTitles);
		$marked = false;
		foreach ((new DOMXPath($dom))->query('//text()') as $node) {
			if ($node instanceof DOMText && preg_match($pattern, $node->data) === 1 && $this->getIsMarkable($node)) {
				$marked = $this->markText($node, $pattern, $keyTitles, $counts) || $marked;
			}
		}
		if (!$marked) {
			$param->setFilterText($html);
		}
	}

	/**
	 * Returns the terms to mark with their titles, without a term whose limit the
	 * element of the control fills.
	 * @return array<string, string> the titles, by term
	 */
	protected function getMarkedTermTitles(): array
	{
		$titles = $this->getTermTitles();
		$term = $this->getTerm();
		if ($term !== '' && $this->getMarkLimit() === 1 && $this->getIsInTarget()) {
			unset($titles[$term]);
		}
		return $titles;
	}

	/**
	 * @return bool whether the element of the control renders inside its target
	 */
	protected function getIsInTarget(): bool
	{
		if ($this->getTargetControl() === '') {
			return true;
		}
		$target = $this->getMarkTarget();
		for ($control = $this->getParent(); $control !== null; $control = $control->getParent()) {
			if ($control === $target) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the expression matching each term as a whole word, longest first.
	 * The whitespace in a term matches any whitespace.
	 * @param string[] $terms the terms
	 * @return string the regular expression, without delimiters
	 */
	protected function getTermExpression(array $terms): string
	{
		usort($terms, fn ($a, $b) => strlen($b) <=> strlen($a));
		$alternatives = array_map(fn ($term) => preg_replace('/\s+/', '\\s+', preg_quote($term, '/')), $terms);
		return '(?<!' . self::WORD_CHARACTER . ')(?:' . implode('|', $alternatives) . ')(?!' . self::WORD_CHARACTER . ')';
	}

	/**
	 * Returns the pattern matching a term in text outside tags, comments, raw text
	 * elements, and elements of the skipped tags that hold only text.
	 * @param string $expression the term expression ({@see getTermExpression})
	 * @return string the pattern for the rendered HTML
	 */
	protected function getUnmarkedTermPattern(string $expression): string
	{
		$tags = implode('|', array_map(fn ($tag) => preg_quote($tag, '/'), $this->getSkipTags()));
		$skip = '<!--.*?-->|<(' . self::RAW_TEXT_TAGS . ')\b[^>]*+>.*?<\/\1\s*>|<(' . $tags . ')\b[^>]*+>[^<]*+<\/\2\s*>|<[a-z\/!?][^>]*+>';
		return '/(?:' . $skip . ')(*SKIP)(*FAIL)|(?' . ($this->getCaseSensitive() ? '-i' : 'i') . ':' . $expression . ')/isu';
	}

	/**
	 * @return string[] the lowercase tags whose text the control does not mark, {@see SKIP_TAGS}
	 */
	protected function getSkipTags(): array
	{
		return self::SKIP_TAGS;
	}

	/**
	 * Returns whether text is markable. The nearest element with
	 * {@see TRenderFilterParameter::EXEMPT_ATTRIBUTE} or a skipped tag decides.
	 * Exempt markup is a placeholder, so the attribute on an element in the
	 * DOM re-enables it.
	 * @param DOMNode $node the text
	 * @return bool whether the text is markable
	 */
	protected function getIsMarkable(DOMNode $node): bool
	{
		$skip = array_flip($this->getSkipTags());
		for ($element = $node->parentNode; $element instanceof DOMElement; $element = $element->parentNode) {
			if ($element->hasAttribute(TRenderFilterParameter::EXEMPT_ATTRIBUTE)) {
				return true;
			}
			if (isset($skip[strtolower($element->localName)])) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Counts the elements of the control's tag in the target that hold each term.
	 * @param DOMDocument $dom the rendered output of the target
	 * @param array<string, string> $keyTitles the titles, by term key ({@see getTermKey})
	 * @return array<string, int> the counts, by term key
	 */
	protected function getMarkCounts(DOMDocument $dom, array $keyTitles): array
	{
		$counts = array_fill_keys(array_keys($keyTitles), 0);
		if ($this->getMarkLimit() === 0) {
			return $counts;
		}
		foreach ($dom->getElementsByTagName(strtolower($this->getTagName())) as $element) {
			$key = $this->getTermKey(trim($element->textContent));
			if (isset($counts[$key])) {
				$counts[$key]++;
			}
		}
		return $counts;
	}

	/**
	 * Wraps each occurrence of a term in text in an element of the control's tag,
	 * up to the limit of the term.
	 * @param DOMText $node the text
	 * @param string $pattern the term pattern
	 * @param array<string, string> $keyTitles the titles, by term key ({@see getTermKey})
	 * @param array<string, int> $counts the marks of each term, by term key, passed by reference
	 * @return bool whether a term is marked
	 */
	protected function markText(DOMText $node, string $pattern, array $keyTitles, array &$counts): bool
	{
		$limit = $this->getMarkLimit();
		$text = $node->data;
		$document = $node->ownerDocument;
		$fragment = $document->createDocumentFragment();
		$offset = 0;
		preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as [$match, $position]) {
			$key = $this->getTermKey($match);
			$counts[$key] ??= 0;
			if ($limit > 0 && $counts[$key] >= $limit) {
				continue;
			}
			$counts[$key]++;
			$fragment->appendChild($document->createTextNode(substr($text, $offset, $position - $offset)));
			$element = $document->createElement(strtolower($this->getTagName()));
			$element->appendChild($document->createTextNode($match));
			if (($title = $keyTitles[$key] ?? '') !== '') {
				$element->setAttribute('title', $title);
			}
			$fragment->appendChild($element);
			$offset = $position + strlen($match);
		}
		if ($offset === 0) {
			return false;
		}
		$fragment->appendChild($document->createTextNode(substr($text, $offset)));
		$node->parentNode->replaceChild($fragment, $node);
		return true;
	}

	/**
	 * @param string $term a term or matched text
	 * @return string the key of the term: the term with single spaces, lowercase
	 *   when the match is case-insensitive
	 */
	public function getTermKey(string $term): string
	{
		$term = preg_replace('/\s+/u', ' ', $term);
		return $this->getCaseSensitive() ? $term : mb_strtolower($term, 'UTF-8');
	}

	/**
	 * @return string the term text
	 */
	public function getText()
	{
		return $this->getViewState('Text', '');
	}

	/**
	 * @param string $value the term text
	 */
	public function setText($value)
	{
		$this->setViewState('Text', TPropertyValue::ensureString($value), '');
	}

	/**
	 * Returns the term text. This method is required by {@see \Prado\IDataRenderer}.
	 * @return string the term text
	 */
	public function getData()
	{
		return $this->getText();
	}

	/**
	 * Sets the term text. This method is required by {@see \Prado\IDataRenderer}.
	 * @param string $value the term text
	 */
	public function setData($value)
	{
		$this->setText($value);
	}

	/**
	 * @return string the title of the term, the {@see getToolTip ToolTip}
	 */
	public function getTitle(): string
	{
		return $this->getToolTip();
	}

	/**
	 * @param string $value the title of the term
	 */
	public function setTitle($value)
	{
		$this->setToolTip($value);
	}

	/**
	 * @return array the more terms to mark, each mapped to its title; a list item has no title
	 */
	public function getTerms(): array
	{
		return $this->getViewState('Terms', []);
	}

	/**
	 * @param array|string $value the more terms to mark, each mapped to its title;
	 *   a string parses with {@see TPropertyValue::ensureArray}
	 */
	public function setTerms($value)
	{
		$this->setViewState('Terms', TPropertyValue::ensureArray($value), []);
	}

	/**
	 * @return string the ID of the control whose output is marked; empty for the parent
	 */
	public function getTargetControl(): string
	{
		return $this->getViewState('TargetControl', '');
	}

	/**
	 * @param string $value the ID of the control whose output is marked; empty for the parent
	 */
	public function setTargetControl($value)
	{
		$this->setViewState('TargetControl', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @return bool whether the control marks the terms in its target. Defaults to true.
	 */
	public function getMarkOccurrences(): bool
	{
		return $this->getViewState('MarkOccurrences', true);
	}

	/**
	 * @param bool|string $value whether the control marks the terms in its target
	 */
	public function setMarkOccurrences($value)
	{
		$this->setViewState('MarkOccurrences', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return int the number of occurrences of each term to mark; 0 marks every
	 *   occurrence. Defaults to {@see getDefaultMarkLimit}.
	 */
	public function getMarkLimit(): int
	{
		return $this->getViewState('MarkLimit', $this->getDefaultMarkLimit());
	}

	/**
	 * @param int|string $value the number of occurrences of each term to mark; 0 marks every occurrence
	 * @throws TInvalidDataValueException if the value is negative
	 */
	public function setMarkLimit($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			throw new TInvalidDataValueException('baseterm_marklimit_invalid', get_class($this), $value);
		}
		$this->setViewState('MarkLimit', $value, $this->getDefaultMarkLimit());
	}

	/**
	 * @return int the levels of controls below the target whose output is marked;
	 *   0 marks all output. Defaults to 0.
	 */
	public function getDepthLimit(): int
	{
		return $this->getViewState('DepthLimit', 0);
	}

	/**
	 * @param int|string $value the levels of controls below the target whose output is marked; 0 marks all output
	 * @throws TInvalidDataValueException if the value is negative
	 */
	public function setDepthLimit($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			throw new TInvalidDataValueException('baseterm_depthlimit_invalid', get_class($this), $value);
		}
		$this->setViewState('DepthLimit', $value, 0);
	}

	/**
	 * @return int the default MarkLimit, 0
	 */
	protected function getDefaultMarkLimit(): int
	{
		return 0;
	}

	/**
	 * @return bool whether the terms match case-sensitively. Defaults to true.
	 */
	public function getCaseSensitive(): bool
	{
		return $this->getViewState('CaseSensitive', true);
	}

	/**
	 * @param bool|string $value whether the terms match case-sensitively
	 */
	public function setCaseSensitive($value)
	{
		$this->setViewState('CaseSensitive', TPropertyValue::ensureBoolean($value), true);
	}
}
