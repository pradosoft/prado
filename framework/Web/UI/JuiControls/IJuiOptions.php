<?php
/**
 * TJuiControlAdapter class file.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\JuiControls
 */

namespace Prado\Web\UI\JuiControls;

/**
 * IJuiOptions interface
 *
 * IJuiOptions is the interface that must be implemented by controls using
 * {@link TJuiControlOptions}.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @package Prado\Web\UI\JuiControls
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
