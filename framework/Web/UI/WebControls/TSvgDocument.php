<?php

/**
 * TSvgDocument class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use DOMDocument;
use DOMElement;
use Prado\Exceptions\TInvalidDataValueException;

/**
 * TSvgDocument class
 * TSvgDocument parses SVG markup into a DOM to write it inline in an HTML page.
 * {@see TSvg} and {@see TDot} use it.
 * - {@see getRootAttributes()} and {@see getInnerMarkup()} split the root `<svg>`
 *   from its content.
 * - {@see getTitle()} finds the root `<title>`, the SVG's own text alternative.
 * - {@see sanitize()} removes script and external resources from untrusted SVG.
 * - {@see prefixIds()} makes the IDs unique on the page and rewrites the
 *   references to them.
 * - {@see scopeStyles()} limits the CSS of the `<style>` elements to the graphic.
 * - {@see extractStyles()} removes the `<style>` elements and returns their CSS.
 * The markup is parsed as XML without network access. A document that declares
 * entities is refused, so entity expansion cannot amplify it; a DOCTYPE without
 * entities, as SVG editors write, is dropped.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSvgDocument
{
	/**
	 * The SVG namespace.
	 */
	public const SVG_NS = 'http://www.w3.org/2000/svg';

	/**
	 * Elements {@see sanitize()} removes: they run script, embed other documents, or
	 * hold HTML.
	 */
	public const UNSAFE_ELEMENTS = ['script', 'foreignObject', 'iframe', 'embed', 'object', 'handler', 'listener'];

	/**
	 * ARIA attributes whose value is a list of IDs, rewritten by {@see prefixIds()}.
	 */
	public const IDREF_ATTRIBUTES = ['aria-labelledby', 'aria-describedby', 'aria-controls', 'aria-owns',
		'aria-flowto', 'aria-activedescendant', 'aria-details', 'aria-errormessage'];

	/**
	 * @var DOMDocument the parsed document.
	 */
	private DOMDocument $_doc;

	/**
	 * @var DOMElement the root `<svg>` element.
	 */
	private DOMElement $_root;

	/**
	 * Parses SVG markup.
	 * @param string $markup the SVG markup, with a root `<svg>` element.
	 * @throws TInvalidDataValueException if the markup is not well-formed XML, declares
	 *   entities, or has a root other than `<svg>`.
	 */
	public function __construct(string $markup)
	{
		$doc = new DOMDocument();
		$previous = libxml_use_internal_errors(true);
		try {
			$loaded = $doc->loadXML($markup, LIBXML_NONET | LIBXML_COMPACT);
			libxml_clear_errors();
		} finally {
			libxml_use_internal_errors($previous);
		}
		if (!$loaded) {
			throw new TInvalidDataValueException('svgdocument_invalid');
		}
		if ($doc->doctype !== null) {
			if ($doc->doctype->entities->length > 0) {
				throw new TInvalidDataValueException('svgdocument_entity_invalid');
			}
			$doc->removeChild($doc->doctype);
		}
		$root = $doc->documentElement;
		if (!($root instanceof DOMElement) || $root->localName !== 'svg') {
			throw new TInvalidDataValueException('svgdocument_root_invalid');
		}
		$this->_doc = $doc;
		$this->_root = $root;
	}

	/**
	 * Parses an SVG file.
	 * @param string $path the path of the `.svg` file.
	 * @throws TInvalidDataValueException if the file cannot be read or is not SVG.
	 * @return self the parsed document.
	 */
	public static function fromFile(string $path): self
	{
		if (!is_file($path) || ($markup = @file_get_contents($path)) === false) {
			throw new TInvalidDataValueException('svgdocument_file_invalid', $path);
		}
		return new self($markup);
	}

	/**
	 * @return DOMElement the root `<svg>` element.
	 */
	public function getRoot(): DOMElement
	{
		return $this->_root;
	}

	/**
	 * Returns the attributes of the root `<svg>`, such as `viewBox` and `fill`. Namespace
	 * declarations are not attributes; an HTML page does not need them.
	 * @return array<string, string> the attribute names and values.
	 */
	public function getRootAttributes(): array
	{
		$attributes = [];
		foreach ($this->_root->attributes as $attribute) {
			$attributes[$attribute->nodeName] = $attribute->value;
		}
		return $attributes;
	}

	/**
	 * @return string the markup of the root's content.
	 */
	public function getInnerMarkup(): string
	{
		$markup = '';
		foreach ($this->_root->childNodes as $child) {
			$markup .= $this->_doc->saveXML($child);
		}
		return $markup;
	}

	/**
	 * Returns the root `<title>`, the SVG's own text alternative, or null.
	 * @return ?DOMElement the `<title>` child of the root.
	 */
	public function getTitle(): ?DOMElement
	{
		foreach ($this->_root->childNodes as $child) {
			if ($child instanceof DOMElement && $child->localName === 'title' && trim($child->textContent) !== '') {
				return $child;
			}
		}
		return null;
	}

	/**
	 * Removes the root `<title>` and `<desc>` elements, which a text alternative of the
	 * control replaces.
	 */
	public function removeTitles(): void
	{
		foreach (iterator_to_array($this->_root->childNodes) as $child) {
			if ($child instanceof DOMElement && ($child->localName === 'title' || $child->localName === 'desc')) {
				$this->_root->removeChild($child);
			}
		}
	}

	/**
	 * Gives the root `<title>` an ID when it has none.
	 * @param string $id the ID to give it.
	 * @return ?string the ID of the root `<title>`, or null when there is none.
	 */
	public function ensureTitleId(string $id): ?string
	{
		if (($title = $this->getTitle()) === null) {
			return null;
		}
		if (($current = $title->getAttribute('id')) !== '') {
			return $current;
		}
		$title->setAttribute('id', $id);
		return $id;
	}

	/**
	 * Removes what can run script or load external resources:
	 * - the {@see UNSAFE_ELEMENTS};
	 * - `on*` event attributes;
	 * - `href`, `xlink:href`, and `src` values with a `javascript:`, `vbscript:`, or
	 *   non-image `data:` scheme;
	 * - on `<use>`, `<image>`, and `<feImage>`, references outside the document,
	 *   other than `data:` images;
	 * - `<animate>` and `<set>` that change an `href` or an event attribute;
	 * - in `<style>` and `style` attributes, `@import` rules and `url()` references
	 *   other than `#fragment` and `data:` images.
	 * Links in `<a>` to other pages remain.
	 */
	public function sanitize(): void
	{
		foreach ($this->elements() as $element) {
			$name = $element->localName;
			if (in_array($name, self::UNSAFE_ELEMENTS, true)) {
				$element->parentNode?->removeChild($element);
				continue;
			}
			if (($name === 'animate' || $name === 'set') && $this->isUnsafeAnimation($element)) {
				$element->parentNode?->removeChild($element);
				continue;
			}
			foreach (iterator_to_array($element->attributes) as $attribute) {
				$attrName = strtolower($attribute->localName);
				$value = $attribute->value;
				if (str_starts_with($attrName, 'on')
					|| (in_array($attrName, ['href', 'src'], true) && !$this->isSafeReference($name, $value))) {
					$element->removeAttributeNode($attribute);
				} elseif ($attrName === 'style') {
					$attribute->value = $this->sanitizeCss($value);
				}
			}
			if ($name === 'style') {
				$element->textContent = $this->sanitizeCss($element->textContent);
			}
		}
	}

	/**
	 * Prefixes every ID with `$prefix` and `_`, and rewrites the references to them:
	 * `#id` in `href` and `xlink:href`, `url(#id)` in any attribute and in `<style>`,
	 * `#id` selectors in `<style>`, the ID lists of {@see IDREF_ATTRIBUTES}, and
	 * `id.event` in animation `begin` and `end`. Two copies of one SVG on a page then
	 * reference their own gradients, clip paths, and filters.
	 * @param string $prefix the prefix, such as the control's client ID.
	 * @return array<string, string> the original IDs mapped to the prefixed IDs.
	 */
	public function prefixIds(string $prefix): array
	{
		$map = [];
		foreach ($this->elements() as $element) {
			if (($id = $element->getAttribute('id')) !== '') {
				$map[$id] = $prefix . '_' . $id;
			}
		}
		if ($map === []) {
			return $map;
		}
		$urlPattern = '/url\(\s*([\'"]?)#([^\'")\s]+)\1\s*\)/';
		$urlReplace = fn ($m) => isset($map[$m[2]]) ? 'url(' . $m[1] . '#' . $map[$m[2]] . $m[1] . ')' : $m[0];
		foreach ($this->elements() as $element) {
			foreach (iterator_to_array($element->attributes) as $attribute) {
				$attrName = $attribute->localName;
				$value = $attribute->value;
				if ($attrName === 'id' && isset($map[$value])) {
					$value = $map[$value];
				} elseif ($attrName === 'href' && str_starts_with($value, '#') && isset($map[substr($value, 1)])) {
					$value = '#' . $map[substr($value, 1)];
				} elseif (in_array($attrName, self::IDREF_ATTRIBUTES, true)) {
					$value = implode(' ', array_map(fn ($ref) => $map[$ref] ?? $ref, preg_split('/\s+/', trim($value))));
				} elseif ($attrName === 'begin' || $attrName === 'end') {
					$value = preg_replace_callback('/(^|[;\s])([A-Za-z_][\w.-]*?)(?=\.(?:begin|end|click|repeat|mouse\w*|focus\w*|activate))/', fn ($m) => $m[1] . ($map[$m[2]] ?? $m[2]), $value);
				} elseif (str_contains($value, 'url(')) {
					$value = preg_replace_callback($urlPattern, $urlReplace, $value);
				}
				if ($value !== $attribute->value) {
					$attribute->value = $value;
				}
			}
			if ($element->localName === 'style') {
				$css = preg_replace_callback($urlPattern, $urlReplace, $element->textContent);
				$css = preg_replace_callback('/#([A-Za-z_][\w-]*)/', fn ($m) => isset($map[$m[1]]) ? '#' . $map[$m[1]] : $m[0], $css);
				$element->textContent = $css;
			}
		}
		return $map;
	}

	/**
	 * Limits the CSS of the `<style>` elements to the graphic. CSS in an inline SVG
	 * applies to the whole page, so a rule such as `.shape { fill: url(#g) }` in two
	 * SVGs reaches both, and the last one wins. Each selector gains `$scope` as an
	 * ancestor; a leading `svg` or `:root` becomes `$scope` itself. Rules in `@media`,
	 * `@supports`, `@container`, and `@layer` are scoped; `@keyframes`, `@font-face`,
	 * and other at-rules are not. Comments are removed.
	 * @param string $scope the selector of the graphic, such as `#icon`.
	 */
	public function scopeStyles(string $scope): void
	{
		foreach ($this->elements() as $element) {
			if ($element->localName === 'style') {
				$element->textContent = static::scopeCss($element->textContent, $scope);
			}
		}
	}

	/**
	 * Prefixes each selector of the CSS with a scope; see {@see scopeStyles()}.
	 * @param string $css the CSS.
	 * @param string $scope the selector of the graphic.
	 * @return string the scoped CSS.
	 */
	public static function scopeCss(string $css, string $scope): string
	{
		$css = preg_replace('#/\*.*?\*/#s', '', $css);
		$out = '';
		$length = strlen($css);
		$i = 0;
		while ($i < $length) {
			if (($brace = strpos($css, '{', $i)) === false) {
				$out .= substr($css, $i);
				break;
			}
			$depth = 1;
			for ($j = $brace + 1; $j < $length && $depth > 0; $j++) {
				if ($css[$j] === '{') {
					$depth++;
				} elseif ($css[$j] === '}') {
					$depth--;
				}
			}
			$prelude = substr($css, $i, $brace - $i);
			$body = substr($css, $brace + 1, max(0, $j - $brace - 2));
			if (($semicolon = strrpos($prelude, ';')) !== false) {
				$out .= substr($prelude, 0, $semicolon + 1);
				$prelude = substr($prelude, $semicolon + 1);
			}
			$selector = trim($prelude);
			if (str_starts_with($selector, '@')) {
				$nested = (bool) preg_match('/^@(media|supports|container|layer)\b/i', $selector);
				$out .= $prelude . '{' . ($nested ? static::scopeCss($body, $scope) : $body) . '}';
			} else {
				$selectors = array_map(fn ($s) => static::scopeSelector($s, $scope), static::splitSelectors($selector));
				$out .= ($out === '' || ctype_space(substr($out, -1)) ? '' : ' ') . implode(', ', $selectors) . ' {' . $body . '}';
			}
			$i = $j;
		}
		return $out;
	}

	/**
	 * @param string $selector one selector.
	 * @param string $scope the selector of the graphic.
	 * @return string the selector within the scope.
	 */
	protected static function scopeSelector(string $selector, string $scope): string
	{
		if (preg_match('/^(svg|:root)(?![\w-])/i', $selector, $m)) {
			return $scope . substr($selector, strlen($m[1]));
		}
		return $scope . ' ' . $selector;
	}

	/**
	 * Splits a selector list at its top-level commas, so `:is(a, b)` stays whole.
	 * @param string $list the selector list.
	 * @return string[] the selectors.
	 */
	protected static function splitSelectors(string $list): array
	{
		$selectors = [];
		$depth = 0;
		$start = 0;
		$length = strlen($list);
		for ($i = 0; $i < $length; $i++) {
			$c = $list[$i];
			if ($c === '(' || $c === '[') {
				$depth++;
			} elseif ($c === ')' || $c === ']') {
				$depth--;
			} elseif ($c === ',' && $depth === 0) {
				$selectors[] = trim(substr($list, $start, $i - $start));
				$start = $i + 1;
			}
		}
		$selectors[] = trim(substr($list, $start));
		return array_values(array_filter($selectors, fn ($s) => $s !== ''));
	}

	/**
	 * Removes the `<style>` elements and returns their CSS.
	 * @return string[] the CSS of each `<style>`, in document order.
	 */
	public function extractStyles(): array
	{
		$styles = [];
		foreach ($this->elements() as $element) {
			if ($element->localName === 'style') {
				if (trim($css = $element->textContent) !== '') {
					$styles[] = $css;
				}
				$element->parentNode?->removeChild($element);
			}
		}
		return $styles;
	}

	/**
	 * @return DOMElement[] the elements of the document, root first, in document order.
	 */
	protected function elements(): array
	{
		return iterator_to_array($this->_doc->getElementsByTagName('*'));
	}

	/**
	 * @param string $element the local name of the element holding the reference.
	 * @param string $value an `href` or `src` value.
	 * @return bool whether the reference is safe to keep.
	 */
	protected function isSafeReference(string $element, string $value): bool
	{
		$value = strtolower(preg_replace('/[\s\x00-\x1f]+/', '', $value));
		if (preg_match('/^(javascript|vbscript):/', $value)) {
			return false;
		}
		$isImage = (bool) preg_match('#^data:image/(png|gif|jpe?g|webp|avif);#', $value);
		if (str_starts_with($value, 'data:')) {
			return $isImage;
		}
		if (in_array($element, ['use', 'image', 'feImage'], true)) {
			return str_starts_with($value, '#') || $isImage;
		}
		return true;
	}

	/**
	 * @param DOMElement $element an `<animate>` or `<set>`.
	 * @return bool whether the animation changes an `href` or an event attribute.
	 */
	protected function isUnsafeAnimation(DOMElement $element): bool
	{
		$target = strtolower($element->getAttribute('attributeName'));
		return str_ends_with($target, 'href') || str_starts_with($target, 'on');
	}

	/**
	 * Removes `@import` rules and `url()` references other than `#fragment` and `data:`
	 * images from CSS.
	 * @param string $css the CSS.
	 * @return string the CSS without them.
	 */
	protected function sanitizeCss(string $css): string
	{
		$css = preg_replace('/@import[^;]*;?/i', '', $css);
		return preg_replace_callback('/url\(\s*([\'"]?)([^\'")]*)\1\s*\)/i', function ($m) {
			$ref = strtolower(trim($m[2]));
			return (str_starts_with($ref, '#') || preg_match('#^data:image/(png|gif|jpe?g|webp|avif);#', $ref)) ? $m[0] : 'none';
		}, $css);
	}
}
