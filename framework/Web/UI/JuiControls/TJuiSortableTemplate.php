<?php
/**
 * TJuiSortable class file.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\JuiControls;

use Prado\Web\UI\ITemplate;

/**
 * TJuiSortableTemplate class.
 *
 * TJuiSortableTemplate is the default template for TJuiSortableTemplate
 * item template.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.1
 */
class TJuiSortableTemplate extends \Prado\TComponent implements ITemplate
{
	private $_template;

	public function __construct($template)
	{
		$this->_template = $template;
		parent::__construct();
	}
	/**
	 * Instantiates the template.
	 * It creates a {@link TDataList} control.
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
