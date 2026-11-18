<?php

/**
 * TEmailHtmlToText class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email;

/**
 * TEmailHtmlToText class.
 *
 * TEmailHtmlToText converts the HTML of an email to its plain-text alternative.
 * {@see TEmail::finalize} uses it when an email has Html and no Text.
 *
 * | HTML | Text |
 * |---|---|
 * | `head`, `style`, `script`, `title`, `template` | removed |
 * | an element with `aria-hidden="true"` or `display:none` | removed |
 * | `<a href="https://x">label</a>` | `label (https://x)` |
 * | `<img alt="logo">` | `logo` |
 * | `<li>item</li>` | `- item` on its own line |
 * | `<br>` | a line break |
 * | headings, paragraphs, lists, tables | separated by a blank line |
 * | other block elements, table rows | on their own line |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailHtmlToText
{
	/** The elements whose content is not text of the message. */
	protected const SKIPPED = ['head', 'style', 'script', 'title', 'template', 'object', 'iframe'];

	/** The elements separated from their neighbors by a blank line. */
	protected const PARAGRAPHS = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'dl', 'table', 'blockquote', 'pre', 'hr'];

	/** Marks the edge of a block element; a run of them is one line break. */
	protected const LINE = "\x01";

	/** Marks the edge of a paragraph element; a run that holds one is a blank line. */
	protected const PARAGRAPH = "\x02";

	/** The elements placed on their own line. */
	protected const BLOCKS = ['div', 'tr', 'section', 'article', 'header', 'footer', 'main', 'aside', 'nav', 'address', 'dt', 'dd', 'figure', 'figcaption', 'form', 'center', 'caption'];

	/**
	 * Converts HTML to plain text.
	 * @param string $html the HTML, a document or a fragment, in UTF-8.
	 * @return string the plain text.
	 */
	public static function convert(string $html): string
	{
		if (trim($html) === '') {
			return '';
		}
		$dom = new \DOMDocument();
		$previous = libxml_use_internal_errors(true);
		$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		$text = $dom->documentElement === null ? '' : static::nodeText($dom->documentElement);
		$text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text);
		$text = preg_replace('/[ \x01\x02]*\x02[ \x01\x02]*/', "\n\n", $text);
		$text = preg_replace('/[ \x01]*\x01[ \x01]*/', "\n", $text);
		$text = preg_replace('/ ?\n ?/', "\n", $text);
		$text = preg_replace('/\n{3,}/', "\n\n", $text);
		return trim($text);
	}

	/**
	 * Converts a node and its descendants to text.
	 * @param \DOMNode $node the node.
	 * @return string the text, with "\n" for a line break and the {@see LINE} and {@see PARAGRAPH} marks at block edges.
	 */
	protected static function nodeText(\DOMNode $node): string
	{
		if ($node instanceof \DOMText) {
			return preg_replace('/\s+/', ' ', $node->nodeValue ?? '');
		}
		if (!($node instanceof \DOMElement)) {
			return '';
		}
		$tag = strtolower($node->tagName);
		if (in_array($tag, self::SKIPPED, true) || static::isHidden($node)) {
			return '';
		}
		if ($tag === 'br') {
			return "\n";
		}
		if ($tag === 'img') {
			return $node->getAttribute('alt');
		}
		$text = '';
		foreach ($node->childNodes as $child) {
			$text .= static::nodeText($child);
		}
		if ($tag === 'a') {
			return static::linkText($node, $text);
		}
		if ($tag === 'li') {
			return self::LINE . '- ' . trim($text, " \x01\x02") . self::LINE;
		}
		if ($tag === 'td' || $tag === 'th') {
			return $text . ' ';
		}
		if (in_array($tag, self::PARAGRAPHS, true)) {
			return self::PARAGRAPH . $text . self::PARAGRAPH;
		}
		if (in_array($tag, self::BLOCKS, true)) {
			return self::LINE . $text . self::LINE;
		}
		return $text;
	}

	/**
	 * @param \DOMElement $element the element.
	 * @return bool whether the element is hidden from the reader.
	 */
	protected static function isHidden(\DOMElement $element): bool
	{
		return strtolower($element->getAttribute('aria-hidden')) === 'true'
			|| $element->hasAttribute('hidden')
			|| preg_match('/display\s*:\s*none/i', $element->getAttribute('style')) === 1;
	}

	/**
	 * Writes a link as its label followed by its URL.
	 * @param \DOMElement $link the anchor element.
	 * @param string $label the text of the anchor.
	 * @return string "label (url)", or the one of them that is not redundant.
	 */
	protected static function linkText(\DOMElement $link, string $label): string
	{
		$url = trim($link->getAttribute('href'));
		$label = trim($label);
		if ($url === '' || $url[0] === '#' || preg_match('/^javascript:/i', $url)) {
			return $label;
		}
		$target = preg_replace('/^mailto:/i', '', $url);
		if ($label === '' || $label === $url || $label === $target) {
			return $target;
		}
		return $label . ' (' . $target . ')';
	}
}
