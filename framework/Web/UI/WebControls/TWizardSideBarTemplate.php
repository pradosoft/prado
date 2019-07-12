<?php
/**
 * TWizard and the relevant class definitions.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Web\UI\ITemplate;

/**
 * TWizardSideBarTemplate class.
 * TWizardSideBarTemplate is the default template for wizard sidebar.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TWizardSideBarTemplate extends \Prado\TComponent implements ITemplate
{
	/**
	 * Instantiates the template.
	 * It creates a {@link TDataList} control.
	 * @param TControl $parent parent to hold the content within the template
	 */
	public function instantiateIn($parent)
	{
		$dataList = new TDataList;
		$dataList->setID(TWizard::ID_SIDEBAR_LIST);
		$dataList->getSelectedItemStyle()->getFont()->setBold(true);
		$dataList->setItemTemplate(new TWizardSideBarListItemTemplate);
		$parent->getControls()->add($dataList);
	}
}
