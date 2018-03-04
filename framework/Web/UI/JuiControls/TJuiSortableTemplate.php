<?php
/**
 * TJuiSortable class file.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2013-2014 PradoSoft
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\JuiControls
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
 * @package Prado\Web\UI\JuiControls
 * @since 3.1
 */
class TJuiSortableTemplate extends \Prado\TComponent implements ITemplate
{
	private $_template;

	public function __construct($template)
	{
		$this->_template = $template;
	}
	/**
	 * Instantiates the template.
	 * It creates a {@link TDataList} control.
	 * @param TControl $parent parent to hold the content within the template
	 */
	public function instantiateIn($parent)
	{
		$parent->getControls()->add($this->_template);
	}
}
