<?php
/**
 * TJuiControlAdapter class file.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\JuiControls;

/**
 * IJuiOptions interface
 *
 * IJuiOptions is the interface that must be implemented by controls using
 * {@see TJuiControlOptions}.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @since 3.3
 */
interface IJuiOptions
{
	public function getWidget();
	public function getWidgetID();
	public function getOptions();
	public function getValidOptions();
	public function getValidEvents();
}
