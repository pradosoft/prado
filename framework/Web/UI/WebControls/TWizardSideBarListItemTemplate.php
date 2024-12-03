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
 * TWizardSideBarListItemTemplate class.
 * TWizardSideBarListItemTemplate is the default template for each item in the sidebar datalist.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TWizardSideBarListItemTemplate extends \Prado\TComponent implements ITemplate
{
	/**
	 * Instantiates the template.
	 * It creates a {@see \Prado\Web\UI\WebControls\TLinkButton}.
	 * @param \Prado\Web\UI\TControl $parent parent to hold the content within the template
	 */
	public function instantiateIn($parent)
	{
		$button = new TLinkButton();
		$button->setID(TWizard::ID_SIDEBAR_BUTTON);
		$parent->getControls()->add($button);
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
