<?php

/**
 * TContentPlaceHolder class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;

/**
 * TContentPlaceHolder class
 *
 * TContentPlaceHolder reserves a place on a template where a {@see \Prado\Web\UI\WebControls\TContent}
 * control can inject itself and its children in. TContentPlaceHolder and {@see \Prado\Web\UI\WebControls\TContent}
 * together implement a decoration pattern for prado templated controls.
 * A template control (called content control) can specify a master control
 * whose template contains some TContentPlaceHolder controls.
 * {@see \Prado\Web\UI\WebControls\TContent} controls on the content control's template will replace the corresponding
 * {@see \Prado\Web\UI\WebControls\TContentPlaceHolder} controls on the master control's template.
 * This is called content injection. It is done by matching the IDs of
 * {@see \Prado\Web\UI\WebControls\TContent} and {@see \Prado\Web\UI\WebControls\TContentPlaceHolder} controls.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TContentPlaceHolder extends \Prado\Web\UI\TControl
{
	/**
	 * This method is invoked after the control is instantiated on a template.
	 * This overrides the parent implementation by registering the content placeholder
	 * control to the template owner control. The placeholder control will NOT
	 * be added to the potential parent control!
	 * @param \Prado\Web\UI\TControl $parent potential parent of this control
	 */
	public function createdOnTemplate($parent)
	{
		if (($id = $this->getID()) === '') {
			throw new TConfigurationException('contentplaceholder_id_required');
		}
		$this->getTemplateControl()->registerContentPlaceHolder($id, $this);
		$parent->getControls()->add($this);
	}
}
