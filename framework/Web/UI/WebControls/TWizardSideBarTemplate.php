<?php
/**
 * TWizard and the relevant class definitions.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Web\UI\ITemplate;

/**
 * TWizardSideBarTemplate class.
 * TWizardSideBarTemplate is the default template for wizard sidebar.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TWizardSideBarTemplate extends \Prado\TComponent implements ITemplate
{
	/**
	 * Instantiates the template.
	 * It creates a {@link TDataList} control.
	 * @param \Prado\Web\UI\TControl $parent parent to hold the content within the template
	 */
	public function instantiateIn($parent)
	{
		$dataList = new TDataList();
		$dataList->setID(TWizard::ID_SIDEBAR_LIST);
		$dataList->getSelectedItemStyle()->getFont()->setBold(true);
		$dataList->setItemTemplate(new TWizardSideBarListItemTemplate());
		$parent->getControls()->add($dataList);
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
