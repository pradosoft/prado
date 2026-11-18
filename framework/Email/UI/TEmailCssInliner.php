<?php

/**
 * TEmailCssInliner class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\UI;

/**
 * TEmailCssInliner class.
 *
 * TEmailCssInliner copies the rules of an HTML document's `<style>` elements into the
 * style attributes of the elements they match.  Many mail clients drop `<style>`
 * elements, and inline styles survive.  The `<style>` elements stay in the document for
 * the clients that support them.
 *
 * Supported selectors are type and `*`, `#id`, `.class`, attribute selectors (`[a]`,
 * `[a=v]`, `~=`, `|=`, `^=`, `$=`, `*=`), and the descendant, `>`, `+`, and `~`
 * combinators.  Rules with pseudo-classes or pseudo-elements, and rules inside at-rules
 * such as `@media`, are not inlined.
 *
 * Declarations apply in cascade order:
 * - stylesheet declarations by specificity, then source order;
 * - the element's own style attribute;
 * - stylesheet `!important` declarations.
 *
 * "{{KEY}}" custom data placeholders are preserved, including inside URLs.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailCssInliner
{
	/**
	 * Inlines the `<style>` rules of an HTML document.
	 * @param string $html the HTML document or fragment.
	 * @return string the HTML with inlined styles; unchanged when it has no `<style>` element.
	 */
	public function inline(string $html): string
	{
		if (stripos($html, '<style') === false) {
			return $html;
		}
		$tokens = [];
		$html = preg_replace_callback('/{{.*?}}/s', function ($m) use (&$tokens) {
			$key = 'PRADOCSSTOKEN' . count($tokens) . 'X';
			$tokens[$key] = $m[0];
			return $key;
		}, $html);

		$isDocument = stripos($html, '<html') !== false;
		$dom = new \DOMDocument();
		$previous = libxml_use_internal_errors(true);
		$dom->loadHTML('<?xml encoding="UTF-8">' . $html, $isDocument ? 0 : (LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD));
		libxml_clear_errors();
		libxml_use_internal_errors($previous);
		foreach (iterator_to_array($dom->childNodes) as $node) {
			if ($node instanceof \DOMProcessingInstruction) {
				$dom->removeChild($node);
			}
		}

		$css = '';
		foreach ($dom->getElementsByTagName('style') as $style) {
			$media = strtolower(trim($style->getAttribute('media')));
			if ($media === '' || $media === 'all' || $media === 'screen') {
				$css .= $style->textContent . "\n";
			}
		}
		$this->applyRules($dom, $this->parseRules($css));

		$result = $dom->saveHTML();
		return strtr($result, $tokens);
	}

	/**
	 * Applies the rules to the matching elements.
	 * @param \DOMDocument $dom the document.
	 * @param array $rules the rules from {@see parseRules}.
	 */
	protected function applyRules(\DOMDocument $dom, array $rules): void
	{
		$xpath = new \DOMXPath($dom);
		$matches = new \SplObjectStorage();
		$order = 0;
		foreach ($rules as [$selector, $declarations]) {
			if (($query = $this->selectorToXPath($selector)) === null) {
				continue;
			}
			$specificity = $this->getSpecificity($selector);
			$nodes = @$xpath->query($query);
			if ($nodes === false) {
				continue;
			}
			$order++;
			foreach ($nodes as $node) {
				$list = $matches->contains($node) ? $matches[$node] : [];
				foreach ($declarations as [$property, $value, $important]) {
					$list[] = [$important ? 1 : 0, $specificity, $order, $property, $value];
				}
				$matches[$node] = $list;
			}
		}
		foreach ($matches as $node) {
			/** @var \DOMElement $node */
			$list = $matches[$node];
			usort($list, fn ($a, $b) => [$a[0], $a[1], $a[2]] <=> [$b[0], $b[1], $b[2]]);
			$normal = $important = [];
			foreach ($list as [$isImportant, , , $property, $value]) {
				if ($isImportant) {
					$important[$property] = $value;
				} else {
					$normal[$property] = $value;
				}
			}
			$own = [];
			foreach ($this->parseDeclarations($node->getAttribute('style')) as [$property, $value]) {
				$own[$property] = $value;
			}
			$final = array_merge($normal, $own, $important);
			$style = [];
			foreach ($final as $property => $value) {
				$style[] = $property . ': ' . $value;
			}
			$node->setAttribute('style', implode('; ', $style) . ';');
		}
	}

	/**
	 * Parses top-level CSS rules.  At-rule blocks are skipped.
	 * @param string $css the style sheet.
	 * @return array list of [selector, declarations].
	 */
	public function parseRules(string $css): array
	{
		$css = preg_replace('#/\*.*?\*/#s', '', $css);
		$rules = [];
		$length = strlen($css);
		$i = 0;
		while ($i < $length) {
			$open = strpos($css, '{', $i);
			if ($open === false) {
				break;
			}
			$prelude = trim(substr($css, $i, $open - $i));
			$close = $this->findBlockEnd($css, $open);
			$body = substr($css, $open + 1, $close - $open - 1);
			$i = $close + 1;
			if ($prelude === '' || $prelude[0] === '@') {
				continue;
			}
			$declarations = $this->parseDeclarations($body);
			foreach (explode(',', $prelude) as $selector) {
				if (($selector = trim($selector)) !== '') {
					$rules[] = [$selector, $declarations];
				}
			}
		}
		return $rules;
	}

	/**
	 * @param string $css the style sheet.
	 * @param int $open the offset of an opening brace.
	 * @return int the offset of the matching closing brace, or the end of the string.
	 */
	protected function findBlockEnd(string $css, int $open): int
	{
		$depth = 0;
		$length = strlen($css);
		for ($j = $open; $j < $length; $j++) {
			if ($css[$j] === '{') {
				$depth++;
			} elseif ($css[$j] === '}' && --$depth === 0) {
				return $j;
			}
		}
		return $length;
	}

	/**
	 * Parses a declaration block.  Semicolons inside quotes and parentheses do not split.
	 * @param string $block the declarations.
	 * @return array list of [property, value, important].
	 */
	public function parseDeclarations(string $block): array
	{
		$parts = [];
		$current = '';
		$depth = 0;
		$quote = null;
		$length = strlen($block);
		for ($i = 0; $i < $length; $i++) {
			$c = $block[$i];
			if ($quote !== null) {
				$quote = $c === $quote ? null : $quote;
			} elseif ($c === '"' || $c === "'") {
				$quote = $c;
			} elseif ($c === '(') {
				$depth++;
			} elseif ($c === ')') {
				$depth = max(0, $depth - 1);
			} elseif ($c === ';' && $depth === 0) {
				$parts[] = $current;
				$current = '';
				continue;
			}
			$current .= $c;
		}
		$parts[] = $current;
		$declarations = [];
		foreach ($parts as $part) {
			if (($colon = strpos($part, ':')) === false) {
				continue;
			}
			$property = strtolower(trim(substr($part, 0, $colon)));
			$value = trim(substr($part, $colon + 1));
			$important = false;
			if (preg_match('/\s*!\s*important\s*$/i', $value, $m)) {
				$important = true;
				$value = trim(substr($value, 0, -strlen($m[0])));
			}
			if ($property !== '' && $value !== '') {
				$declarations[] = [$property, $value, $important];
			}
		}
		return $declarations;
	}

	/**
	 * Converts a CSS selector to XPath.
	 * @param string $selector the selector.
	 * @return ?string the XPath query, null when the selector is not supported.
	 */
	public function selectorToXPath(string $selector): ?string
	{
		if (str_contains($selector, ':')) {
			return null;
		}
		$pattern = '/\s*([>+~])\s*|\s+/';
		$parts = preg_split($pattern, trim($selector), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		$xpath = '//';
		$combinator = null;
		foreach ($parts as $part) {
			if (in_array($part, ['>', '+', '~'], true)) {
				$combinator = $part;
				continue;
			}
			if (($step = $this->compoundToXPath($part)) === null) {
				return null;
			}
			[$tag, $conditions] = $step;
			$test = $tag . $conditions;
			if ($xpath === '//') {
				$xpath .= $test;
			} elseif ($combinator === '>') {
				$xpath .= '/' . $test;
			} elseif ($combinator === '+') {
				$xpath .= '/following-sibling::*[1]/self::' . $test;
			} elseif ($combinator === '~') {
				$xpath .= '/following-sibling::' . $test;
			} else {
				$xpath .= '//' . $test;
			}
			$combinator = null;
		}
		return $xpath === '//' ? null : $xpath;
	}

	/**
	 * Converts a compound selector, such as "p.note#a[title]", to an XPath node test.
	 * @param string $compound the compound selector.
	 * @return ?array [tag, conditions], null when not supported.
	 */
	protected function compoundToXPath(string $compound): ?array
	{
		if (!preg_match('/^(\*|[a-zA-Z][a-zA-Z0-9-]*)?((?:#[\w-]+|\.[\w-]+|\[[^\]]+\])*)$/', $compound, $m)) {
			return null;
		}
		$tag = $m[1] !== '' ? strtolower($m[1]) : '*';
		$conditions = '';
		preg_match_all('/#([\w-]+)|\.([\w-]+)|\[\s*([\w-]+)\s*(?:([~|^$*]?=)\s*(?:"([^"]*)"|\'([^\']*)\'|([^\]\s]*)))?\s*\]/', $m[2], $selectors, PREG_SET_ORDER);
		foreach ($selectors as $s) {
			if (($s[1] ?? '') !== '') {
				$conditions .= '[@id=' . $this->quote($s[1]) . ']';
			} elseif (($s[2] ?? '') !== '') {
				$conditions .= "[contains(concat(' ', normalize-space(@class), ' '), " . $this->quote(' ' . $s[2] . ' ') . ')]';
			} else {
				$attr = '@' . strtolower($s[3]);
				$op = $s[4] ?? '';
				$value = ($s[5] ?? '') . ($s[6] ?? '') . ($s[7] ?? '');
				$q = $this->quote($value);
				if ($q === null) {
					return null;
				}
				$conditions .= match ($op) {
					'' => '[' . $attr . ']',
					'=' => '[' . $attr . '=' . $q . ']',
					'~=' => "[contains(concat(' ', normalize-space(" . $attr . "), ' '), " . $this->quote(' ' . $value . ' ') . ')]',
					'|=' => '[' . $attr . '=' . $q . ' or starts-with(' . $attr . ', ' . $this->quote($value . '-') . ')]',
					'^=' => '[starts-with(' . $attr . ', ' . $q . ')]',
					'$=' => '[substring(' . $attr . ', string-length(' . $attr . ') - ' . (strlen($value) - 1) . ') = ' . $q . ']',
					'*=' => '[contains(' . $attr . ', ' . $q . ')]',
				};
			}
		}
		return [$tag, $conditions];
	}

	/**
	 * @param string $value the literal.
	 * @return ?string the XPath string literal, null when it holds both quote characters.
	 */
	protected function quote(string $value): ?string
	{
		if (!str_contains($value, "'")) {
			return "'" . $value . "'";
		}
		return str_contains($value, '"') ? null : '"' . $value . '"';
	}

	/**
	 * Computes the specificity of a selector as one comparable integer.
	 * @param string $selector the selector.
	 * @return int the specificity: ids * 10000 + classes and attributes * 100 + types.
	 */
	public function getSpecificity(string $selector): int
	{
		$ids = preg_match_all('/#[\w-]+/', $selector);
		$classes = preg_match_all('/\.[\w-]+|\[[^\]]+\]/', $selector);
		$stripped = preg_replace('/#[\w-]+|\.[\w-]+|\[[^\]]+\]/', ' ', $selector);
		$types = preg_match_all('/(?:^|[\s>+~])([a-zA-Z][a-zA-Z0-9-]*)/', $stripped);
		return $ids * 10000 + $classes * 100 + $types;
	}
}
