<?php
/**
 * TWizard and the relevant class definitions.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TWizardSideBarTemplate class.
 * TWizardSideBarTemplate is the default template for wizard sidebar.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TWizardSideBarTemplate extends TComponent implements ITemplate
{
	/**
	 * Instantiates the template.
	 * It creates a {@link TDataList} control.
	 * @param TControl parent to hold the content within the template
	 */
	public function instantiateIn($parent)
	{
		$dataList=new TDataList;
		$dataList->setID(TWizard::ID_SIDEBAR_LIST);
		$dataList->getSelectedItemStyle()->getFont()->setBold(true);
		$dataList->setItemTemplate(new TWizardSideBarListItemTemplate);
		$parent->getControls()->add($dataList);
	}
}