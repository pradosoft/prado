<?php

/**
 * TJuiAutoComplete class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\JuiControls;

use Prado\Web\UI\ITemplate;

/**
 * TJuiAutoCompleteTemplate class.
 *
 * TJuiAutoCompleteTemplate is a template of fixed markup.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.1
 */
class TJuiAutoCompleteTemplate extends \Prado\TComponent implements ITemplate
{
	private $_template;

	/**
	 * @param string $template the markup the template adds
	 */
	public function __construct($template)
	{
		$this->_template = $template;
		parent::__construct();
	}
	/**
	 * Instantiates the template by adding its markup to the parent's controls.
	 * @param \Prado\Web\UI\TControl $parent parent to hold the content within the template
	 */
	public function instantiateIn($parent)
	{
		$parent->getControls()->add($this->_template);
	}

	/**
	 * TTemplateManager calls this method for caching the included file modification times.
	 * @return array list of included external template files
	 */
	public function getIncludedFiles()
	{
		return [];
	}
}
