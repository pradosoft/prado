<?php

/**
 * TJsonLd class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TTextWriter;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\TMediaType;

/**
 * TJsonLd class
 *
 * TJsonLd renders structured data as a `<script type="application/ld+json">`
 * data block, such as a schema.org Organization, Product, or BreadcrumbList.
 * The body of the control is the JSON document. TJsonLd renders where it is
 * placed, in {@see THead} or in the body; it renders nothing when it has no
 * document.
 * ```php
 * <com:THead>
 *   <com:TJsonLd>
 *     {"@type": "Organization", "name": "Acme", "url": "https://example.com"}
 *   </com:TJsonLd>
 *   <com:TJsonLd ID="ProductLd" />
 * </com:THead>
 * ```
 * Page code sets {@see setStructuredData StructuredData} to a PHP array, which
 * encodes every value, so data from a record cannot break the JSON:
 * ```php
 * $this->ProductLd->setStructuredData(['@type' => 'Product', 'name' => $product->Name]);
 * ```
 *
 * | Source | Use |
 * |---|---|
 * | {@see setStructuredData StructuredData} | A document built in code; it takes precedence over the body |
 * | Body | A fixed document written in the template |
 *
 * The body renders, then decodes as JSON. An expression in the body inserts its
 * text unescaped, so a dynamic value belongs in StructuredData. A body that is
 * not valid JSON throws when the control renders.
 *
 * An associative document without an `@context` key receives
 * {@see setContext Context}, `https://schema.org` by default, as its first key.
 * The JSON escapes `<` and `>`, so a value cannot close the script element, and
 * escapes non-ASCII characters, so it is correct in any page charset. The data
 * block carries the request's CSP nonce, when one is active, as every inline
 * script of the page does.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TJsonLd extends \Prado\Web\UI\TControl
{
	/**
	 * The default JSON-LD context.
	 */
	public const DEFAULT_CONTEXT = 'https://schema.org';

	/**
	 * @return mixed the structured data: an array, a JSON-serializable object, or
	 *   null. Defaults to null.
	 */
	public function getStructuredData()
	{
		return $this->getViewState('StructuredData', null);
	}

	/**
	 * Sets the structured data, which takes precedence over the body. JSON text is
	 * decoded into an array.
	 * @param mixed $value an array, a JSON-serializable object, JSON text, or null
	 * @throws TInvalidDataValueException if the JSON text is not valid.
	 */
	public function setStructuredData($value)
	{
		if (is_string($value)) {
			$value = $this->decodeJson($value, 'StructuredData');
		}
		$this->setViewState('StructuredData', $value, null);
	}

	/**
	 * @return string the JSON-LD context added to an associative document without
	 *   one. Defaults to `https://schema.org`.
	 */
	public function getContext()
	{
		return $this->getViewState('Context', self::DEFAULT_CONTEXT);
	}

	/**
	 * @param string $value the JSON-LD context; empty adds none.
	 */
	public function setContext($value)
	{
		$this->setViewState('Context', TPropertyValue::ensureString($value), self::DEFAULT_CONTEXT);
	}

	/**
	 * Decodes JSON text into an array.
	 * @param string $json the JSON text
	 * @param string $source the name of the source, for the error message
	 * @throws TInvalidDataValueException if the JSON text is not valid.
	 * @return mixed the decoded value, or null for empty text
	 */
	protected function decodeJson(string $json, string $source)
	{
		if (($json = trim($json)) === '') {
			return null;
		}
		try {
			return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException $e) {
			throw new TInvalidDataValueException('jsonld_json_invalid', $source, $e->getMessage());
		}
	}

	/**
	 * Renders the child controls into a string. The writer keeps its render filter
	 * context, and its output is restored afterward.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 * @return string the rendered body
	 */
	protected function renderBody($writer): string
	{
		$output = $writer->getWriter();
		$writer->setWriter(new TTextWriter());
		try {
			$this->renderChildren($writer);
			return $writer->flush();
		} finally {
			$writer->setWriter($output);
		}
	}

	/**
	 * Encodes a document with its context as script-safe JSON.
	 * @param mixed $data the document
	 * @throws \JsonException if the document cannot be encoded.
	 * @return string the JSON, or empty when the document is null or `[]`
	 */
	protected function encodeDocument($data): string
	{
		if ($data === null || $data === []) {
			return '';
		}
		$context = $this->getContext();
		if ($context !== '' && is_array($data) && !array_is_list($data) && !array_key_exists('@context', $data)) {
			$data = ['@context' => $context] + $data;
		}
		return TJavaScript::jsonEncode($data, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES);
	}

	/**
	 * Renders the data block of StructuredData, or else of the body.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 * @throws TInvalidDataValueException if the body is not valid JSON.
	 */
	public function render($writer)
	{
		if (($data = $this->getStructuredData()) === null && $this->getHasControls()) {
			$data = $this->decodeJson($this->renderBody($writer), 'body');
		}
		if (($json = $this->encodeDocument($data)) === '') {
			return;
		}
		$writer->addAttribute('type', TMediaType::JSON_LD);
		if (($nonce = TJavaScript::getScriptNonce()) !== null) {
			$writer->addAttribute('nonce', $nonce);
		}
		$writer->renderBeginTag('script');
		$writer->write($json);
		$writer->renderEndTag();
	}
}
