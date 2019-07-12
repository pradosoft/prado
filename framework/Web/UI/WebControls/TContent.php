<?php
/**
 * TContent class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\Web\UI\TControl;
use Prado\Web\UI\INamingContainer;

/**
 * TContent class
 *
 * TContent specifies a block of content on a control's template
 * that will be injected at somewhere of the master control's template.
 * TContentPlaceHolder and {@link TContent} together implement a decoration
 * pattern for prado templated controls. A template control
 * (called content control) can specify a master control
 * whose template contains some TContentPlaceHolder controls.
 * {@link TContent} controls on the content control's template will replace the corresponding
 * {@link TContentPlaceHolder} controls on the master control's template.
 * This is called content injection. It is done by matching the IDs of
 * {@link TContent} and {@link TContentPlaceHolder} controls.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TContent extends \Prado\Web\UI\TControl implements \Prado\Web\UI\INamingContainer
{
	/**
	 * This method is invoked after the control is instantiated on a template.
	 * This overrides the parent implementation by registering the content control
	 * to the template owner control.
	 * @param TControl $parent potential parent of this control
	 */
	public function createdOnTemplate($parent)
	{
		if (($id = $this->getID()) === '') {
			throw new TConfigurationException('content_id_required');
		}
		$this->getTemplateControl()->registerContent($id, $this);
	}
}
