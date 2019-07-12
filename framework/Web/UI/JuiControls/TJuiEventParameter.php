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

use Prado\Prado;
use Prado\Web\Services\TPageService;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TControl;

/**
 * TJuiEventParameter class
 *
 * TJuiEventParameter encapsulate the parameters for callback
 * events of TJui* components.
 * Any parameter representing a control is identified by its
 * clientside ID.
 * TJuiEventParameter contains a {@link getControl} helper method
 * that retrieves an existing PRADO control on che current page from its
 * clientside ID as returned by the callback.
 * For example, if the parameter contains a "draggable" item (as returned in
 * {@link TJuiDroppable}::OnDrop event), the relative PRADO control can be
 * retrieved using:
 * <code>
 * $draggable = $param->getControl($param->getCallbackParameter()->draggable);
 * </code>
 *
 * A shortcut __get() method is implemented, too:
 * <code>
 * $draggable = $param->DraggableControl;
 * </code>
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\JuiControls
 */
class TJuiEventParameter extends TCallbackEventParameter
{
	/**
	 * getControl
	 *
	 * Compatibility method to get a control from its clientside id
	 * @param mixed $id
	 * @return TControl control, or null if not found
	 */
	public function getControl($id)
	{
		$control = null;
		$service = Prado::getApplication()->getService();
		if ($service instanceof TPageService) {
			// Find the control
			// Warning, this will not work if you have a '_' in your control Id !
			$controlId = str_replace(TControl::CLIENT_ID_SEPARATOR, TControl::ID_SEPARATOR, $id);
			$control = $service->getRequestedPage()->findControl($controlId);
		}
		return $control;
	}

	/**
	 * Gets a control instance named after a returned control id.
	 * Example: if a $param->draggable control id is returned from clientside,
	 * calling $param->DraggableControl will return the control instance
	 * @param mixed $name
	 * @return mixed control or null if not set.
	 */
	public function __get($name)
	{
		$pos = strpos($name, 'Control', 1);
		$name = strtolower(substr($name, 0, $pos));

		$cp = $this->getCallbackParameter();
		if (!isset($cp->$name) || $cp->$name == '') {
			return null;
		}

		return $this->getControl($cp->$name);
	}
}
