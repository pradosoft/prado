<?php

/**
 * TMetaTag class file
 *
 * @author Marcus Nyeholt <tanus@users.sourceforge.net> and Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TMetaTag class.
 *
 * TMetaTag represents a meta tag appearing in a page head section.
 * You can set its {@see setID ID}, {@see setHttpEquiv HttpEquiv},
 * {@see setName Name}, {@see setProperty Property}, {@see setContent Content},
 * {@see setScheme Scheme}, {@see setCharset Charset} properties, which correspond to
 * id, http-equiv, name, property, content, scheme and charset attributes for a meta tag, respectively.
 * The RDFa `property` attribute names Open Graph metadata, such as `og:title`;
 * {@see TOpenGraph} renders those.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TMetaTag extends \Prado\TComponent
{
	/**
	 * @var string id of the meta tag
	 */
	private $_id = '';
	/**
	 * @var string http-equiv attribute of the meta tag
	 */
	private $_httpEquiv = '';
	/**
	 * @var string name attribute of the meta tag
	 */
	private $_name = '';
	/**
	 * @var string property attribute of the meta tag
	 */
	private $_property = '';
	/**
	 * @var string content attribute of the meta tag
	 */
	private $_content = '';
	/**
	 * @var string scheme attribute of the meta tag
	 */
	private $_scheme = '';
	/**
	 * @var string charset attribute of the meta tag
	 */
	private $_charset = '';

	/**
	 * @return string id of the meta tag
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string $value id of the meta tag
	 */
	public function setID($value)
	{
		$this->_id = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string http-equiv attribute of the meta tag
	 */
	public function getHttpEquiv()
	{
		return $this->_httpEquiv;
	}

	/**
	 * @param string $value http-equiv attribute of the meta tag
	 */
	public function setHttpEquiv($value)
	{
		$this->_httpEquiv = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string name attribute of the meta tag
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @param string $value name attribute of the meta tag
	 */
	public function setName($value)
	{
		$this->_name = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string property attribute of the meta tag
	 * @since 4.4.0
	 */
	public function getProperty()
	{
		return $this->_property;
	}

	/**
	 * Sets the RDFa property the meta tag carries, such as `og:title`.
	 * @param string $value property attribute of the meta tag
	 * @since 4.4.0
	 */
	public function setProperty($value)
	{
		$this->_property = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string content attribute of the meta tag
	 */
	public function getContent()
	{
		return $this->_content;
	}

	/**
	 * @param string $value content attribute of the meta tag
	 */
	public function setContent($value)
	{
		$this->_content = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string scheme attribute of the meta tag
	 * @deprecated considered useless
	 */
	public function getScheme()
	{
		return $this->_scheme;
	}

	/**
	 * @param string $value scheme attribute of the meta tag
	 * @deprecated considered useless
	 */
	public function setScheme($value)
	{
		$this->_scheme = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string charset attribute of the meta tag
	 */
	public function getCharset()
	{
		return $this->_charset;
	}

	/**
	 * @param string $value charset attribute of the meta tag
	 */
	public function setCharset($value)
	{
		$this->_charset = TPropertyValue::ensureString($value);
	}

	/**
	 * Renders the meta tag. A charset meta tag has no content attribute; any other
	 * renders its content, even when empty.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function render($writer)
	{
		$charset = $this->getCharset();
		$attributes = [
			'id' => $this->getID(),
			'name' => $this->getName(),
			'property' => $this->getProperty(),
			'http-equiv' => $this->getHttpEquiv(),
			'scheme' => $this->getScheme(),
			'charset' => $charset,
		];
		foreach ($attributes as $name => $value) {
			if ($value !== '') {
				$writer->addAttribute($name, $value);
			}
		}
		if ($charset === '') {
			$writer->addAttribute('content', $this->getContent());
		}
		$writer->renderBeginTag('meta');
		$writer->renderEndTag();
	}
}
