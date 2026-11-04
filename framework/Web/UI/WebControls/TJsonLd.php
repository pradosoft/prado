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
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\TMediaType;

/**
 * TJsonLd class
 *
 * TJsonLd renders structured data as a `<script type="application/ld+json">`
 * data block, such as a schema.org Organization, Product, or BreadcrumbList.
 * {@see setData Data} holds the document as a PHP array, a JSON-serializable
 * object, or JSON text. TJsonLd renders where it is placed, in {@see THead} or
 * in the body; it renders nothing when Data is empty.
 * ```php
 * <com:THead>
 *   <com:TJsonLd ID="Organization">
 *     <prop:Data>{"@type": "Organization", "name": "Acme", "url": "https://example.com"}</prop:Data>
 *   </com:TJsonLd>
 * </com:THead>
 * ```
 * ```php
 * $this->Organization->setData(['@type' => 'Organization', 'name' => 'Acme']);
 * ```
 *
 * An associative array without an `@context` key receives
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
	public function getData()
	{
		return $this->getViewState('Data', null);
	}

	/**
	 * Sets the structured data. JSON text is decoded into an array.
	 * @param mixed $value an array, a JSON-serializable object, JSON text, or null
	 * @throws TInvalidDataValueException if the JSON text is not valid.
	 */
	public function setData($value)
	{
		if (is_string($value)) {
			$value = trim($value);
			try {
				$value = $value === '' ? null : json_decode($value, true, 512, JSON_THROW_ON_ERROR);
			} catch (\JsonException $e) {
				throw new TInvalidDataValueException('jsonld_data_invalid', $e->getMessage());
			}
		}
		$this->setViewState('Data', $value, null);
	}

	/**
	 * @return string the JSON-LD context added to an associative Data without one.
	 *   Defaults to `https://schema.org`.
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
	 * Returns the encoded document: Data with its context, as script-safe JSON.
	 * @throws \JsonException if Data cannot be encoded.
	 * @return string the JSON, or empty when Data is empty
	 */
	public function getJson(): string
	{
		$data = $this->getData();
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
	 * Renders the data block.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function render($writer)
	{
		if (($json = $this->getJson()) === '') {
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
