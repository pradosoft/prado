<?php
/**
 * TTableHeaderCell class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TTableHeaderCell class.
 *
 * TTableHeaderCell displays a table header cell on a Web page.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TTableHeaderCell extends TTableCell
{
	/**
	 * @return string tag name for the table header cell
	 */
	protected function getTagName()
	{
		return 'th';
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter $writer the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if (($scope = $this->getScope()) !== TTableHeaderScope::NotSet) {
			$writer->addAttribute('scope', $scope === TTableHeaderScope::Row ? 'row' : 'col');
		}
		if (($text = $this->getAbbreviatedText()) !== '') {
			$writer->addAttribute('abbr', $text);
		}
		if (($text = $this->getCategoryText()) !== '') {
			$writer->addAttribute('axis', $text);
		}
	}

	/**
	 * @return TTableHeaderScope the scope of the cells that the header cell applies to. Defaults to TTableHeaderScope::NotSet.
	 */
	public function getScope()
	{
		return $this->getViewState('Scope', TTableHeaderScope::NotSet);
	}

	/**
	 * @param TTableHeaderScope $value the scope of the cells that the header cell applies to.
	 */
	public function setScope($value)
	{
		$this->setViewState('Scope', TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TTableHeaderScope'), TTableHeaderScope::NotSet);
	}

	/**
	 * @return string  the abbr attribute of the HTML th element
	 */
	public function getAbbreviatedText()
	{
		return $this->getViewState('AbbreviatedText', '');
	}

	/**
	 * @param string $value the abbr attribute of the HTML th element
	 */
	public function setAbbreviatedText($value)
	{
		$this->setViewState('AbbreviatedText', $value, '');
	}

	/**
	 * @return string the axis attribute of the HTML th element
	 * @deprecated use the Scope property instead
	 */
	public function getCategoryText()
	{
		return $this->getViewState('CategoryText', '');
	}

	/**
	 * @param string $value the axis attribute of the HTML th element
	 * @deprecated use the Scope property instead
	 */
	public function setCategoryText($value)
	{
		$this->setViewState('CategoryText', $value, '');
	}
}
