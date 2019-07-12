<?php
/**
 * TSafeHtml class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\IO\TTextWriter;
use Prado\Prado;

/**
 * TSafeHtml class
 *
 * TSafeHtml is a control that strips down all potentially dangerous HTML content.
 * It is mainly a wrapper of {@link http://htmlpurifier.org/ HTMLPurifier} project.
 *
 * To use TSafeHtml, simply enclose the content to be secured within
 * the body of TSafeHtml in a template.
 *
 * You can specify a custom configuration for HTMLPurifier using the
 * {@link setConfig Config} property. Please refer to the
 * {@link http://htmlpurifier.org/docs HTMLPurifier documentation} for the
 * possibile configuration parameters.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TSafeHtml extends \Prado\Web\UI\TControl
{
	/**
	 * Sets a custom configuration for HTMLPurifier.
	 * @param \HTMLPurifier_Config $value custom configuration
	 */
	public function setConfig(\HTMLPurifier_Config $value)
	{
		$this->setViewState('Config', $value, null);
	}

	/**
	 * @return \HTMLPurifier_Config Configuration for HTMLPurifier.
	 */
	public function getConfig()
	{
		$config = $this->getViewState('Config', null);
		return ($config === null) ? \HTMLPurifier_Config::createDefault() : $config;
	}

	/**
	 * Renders body content.
	 * This method overrides parent implementation by removing malicious code from the body content
	 * @param THtmlWriter $writer writer
	 */
	public function render($writer)
	{
		$htmlWriter = Prado::createComponent($this->GetResponse()->getHtmlWriterType(), new TTextWriter());
		parent::render($htmlWriter);
		$writer->write($this->parseSafeHtml($htmlWriter->flush()));
	}

	/**
	 * Use HTMLPurifier to remove malicous content from HTML.
	 * @param string $text HTML content
	 * @return string safer HTML content
	 */
	protected function parseSafeHtml($text)
	{
		$purifier = new \HTMLPurifier($this->getConfig());
		return $purifier->purify($text);
	}
}
