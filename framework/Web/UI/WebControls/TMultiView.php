<?php
/**
 * TMultiView and TView class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TPropertyValue;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\TControl;

/**
 * TMultiView class
 *
 * TMultiView serves as a container for a group of {@link TView} controls.
 * The view collection can be retrieved by {@link getViews Views}.
 * Each view contains child controls. TMultiView determines which view and its
 * child controls are visible. At any time, at most one view is visible (called
 * active). To make a view active, set {@link setActiveView ActiveView} or
 * {@link setActiveViewIndex ActiveViewIndex}.
 *
 * TMultiView also responds to specific command events raised from button controls
 * contained in current active view. A command event with name 'NextView'
 * will cause TMultiView to make the next available view active.
 * Other command names recognized by TMultiView include
 * - PreviousView : switch to previous view
 * - SwitchViewID : switch to a view by its ID path
 * - SwitchViewIndex : switch to a view by its index in the {@link getViews Views} collection.
 *
 * TMultiView raises {@link OnActiveViewChanged OnActiveViewChanged} event
 * when its active view is changed during a postback.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TMultiView extends \Prado\Web\UI\TControl
{
	const CMD_NEXTVIEW = 'NextView';
	const CMD_PREVIOUSVIEW = 'PreviousView';
	const CMD_SWITCHVIEWID = 'SwitchViewID';
	const CMD_SWITCHVIEWINDEX = 'SwitchViewIndex';
	private $_cachedActiveViewIndex = -1;
	private $_ignoreBubbleEvents = false;

	/**
	 * Processes an object that is created during parsing template.
	 * This method overrides the parent implementation by adding only {@link TView}
	 * controls as children.
	 * @param string|TComponent $object text string or component parsed and instantiated in template
	 * @see createdOnTemplate
	 * @throws TConfigurationException if controls other than {@link TView} is being added
	 */
	public function addParsedObject($object)
	{
		if ($object instanceof TView) {
			$this->getControls()->add($object);
		} elseif (!is_string($object)) {
			throw new TConfigurationException('multiview_view_required');
		}
	}

	/**
	 * Creates a control collection object that is to be used to hold child controls
	 * @return TViewCollection control collection
	 */
	protected function createControlCollection()
	{
		return new TViewCollection($this);
	}

	/**
	 * @return int the zero-based index of the current view in the view collection. -1 if no active view. Default is -1.
	 */
	public function getActiveViewIndex()
	{
		if ($this->_cachedActiveViewIndex > -1) {
			return $this->_cachedActiveViewIndex;
		} else {
			return $this->getControlState('ActiveViewIndex', -1);
		}
	}

	/**
	 * @param int $value the zero-based index of the current view in the view collection. -1 if no active view.
	 * @throws TInvalidDataValueException if the view index is invalid
	 */
	public function setActiveViewIndex($value)
	{
		if (($index = TPropertyValue::ensureInteger($value)) < 0) {
			$index = -1;
		}
		$views = $this->getViews();
		$count = $views->getCount();
		if ($count === 0 && $this->getControlStage() < TControl::CS_CHILD_INITIALIZED) {
			$this->_cachedActiveViewIndex = $index;
		} elseif ($index < $count) {
			$this->setControlState('ActiveViewIndex', $index, -1);
			$this->_cachedActiveViewIndex = -1;
			if ($index >= 0) {
				$this->activateView($views->itemAt($index), true);
			}
		} else {
			throw new TInvalidDataValueException('multiview_activeviewindex_invalid', $index);
		}
	}

	/**
	 * @throws TInvalidDataValueException if the current active view index is invalid
	 * @return TView the currently active view, null if no active view
	 */
	public function getActiveView()
	{
		$index = $this->getActiveViewIndex();
		$views = $this->getViews();
		if ($index >= $views->getCount()) {
			throw new TInvalidDataValueException('multiview_activeviewindex_invalid', $index);
		}
		if ($index < 0) {
			return null;
		}
		$view = $views->itemAt($index);
		if (!$view->getActive()) {
			$this->activateView($view, false);
		}
		return $view;
	}

	/**
	 * @param TView $view the view to be activated
	 * @throws TInvalidOperationException if the view is not in the view collection
	 */
	public function setActiveView($view)
	{
		if (($index = $this->getViews()->indexOf($view)) >= 0) {
			$this->setActiveViewIndex($index);
		} else {
			throw new TInvalidOperationException('multiview_view_inexistent');
		}
	}

	/**
	 * Activates the specified view.
	 * If there is any view currently active, it will be deactivated.
	 * @param TView $view the view to be activated
	 * @param bool $triggerViewChangedEvent whether to trigger OnActiveViewChanged event.
	 */
	protected function activateView($view, $triggerViewChangedEvent = true)
	{
		if ($view->getActive()) {
			return;
		}
		$triggerEvent = $triggerViewChangedEvent && ($this->getControlStage() >= \Prado\Web\UI\TControl::CS_STATE_LOADED || ($this->getPage() && !$this->getPage()->getIsPostBack()));
		foreach ($this->getViews() as $v) {
			if ($v === $view) {
				$view->setActive(true);
				if ($triggerEvent) {
					$view->onActivate(null);
					$this->onActiveViewChanged(null);
				}
			} elseif ($v->getActive()) {
				$v->setActive(false);
				if ($triggerEvent) {
					$v->onDeactivate(null);
				}
			}
		}
	}

	/**
	 * @return TViewCollection the view collection
	 */
	public function getViews()
	{
		return $this->getControls();
	}

	/**
	 * Makes the multiview ignore all bubbled events.
	 * This is method is used internally by framework and control
	 * developers.
	 */
	public function ignoreBubbleEvents()
	{
		$this->_ignoreBubbleEvents = true;
	}

	/**
	 * Initializes the active view if any.
	 * This method overrides the parent implementation.
	 * @param TEventParameter $param event parameter
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->_cachedActiveViewIndex >= 0) {
			$this->setActiveViewIndex($this->_cachedActiveViewIndex);
		}
	}

	/**
	 * Raises <b>OnActiveViewChanged</b> event.
	 * The event is raised when the currently active view is changed to a new one
	 * @param TEventParameter $param event parameter
	 */
	public function onActiveViewChanged($param)
	{
		$this->raiseEvent('OnActiveViewChanged', $this, $param);
	}

	/**
	 * Processes the events bubbled from child controls.
	 * The method handles view-related command events.
	 * @param TControl $sender sender of the event
	 * @param mixed $param event parameter
	 * @return bool whether this event is handled
	 */
	public function bubbleEvent($sender, $param)
	{
		if (!$this->_ignoreBubbleEvents && ($param instanceof \Prado\Web\UI\TCommandEventParameter)) {
			switch ($param->getCommandName()) {
				case self::CMD_NEXTVIEW:
					if (($index = $this->getActiveViewIndex()) < $this->getViews()->getCount() - 1) {
						$this->setActiveViewIndex($index + 1);
					} else {
						$this->setActiveViewIndex(-1);
					}
					return true;
				case self::CMD_PREVIOUSVIEW:
					if (($index = $this->getActiveViewIndex()) >= 0) {
						$this->setActiveViewIndex($index - 1);
					}
					return true;
				case self::CMD_SWITCHVIEWID:
					$view = $this->findControl($viewID = $param->getCommandParameter());
					if ($view !== null && $view->getParent() === $this) {
						$this->setActiveView($view);
						return true;
					} else {
						throw new TInvalidDataValueException('multiview_viewid_invalid', $viewID);
					}
					break;
				case self::CMD_SWITCHVIEWINDEX:
					$index = TPropertyValue::ensureInteger($param->getCommandParameter());
					$this->setActiveViewIndex($index);
					return true;
			}
		}
		return false;
	}

	/**
	 * Loads state into the wizard.
	 * This method is invoked by the framework when the control state is being saved.
	 */
	public function loadState()
	{
		// a dummy call to ensure the view is activated
		$this->getActiveView();
	}

	/**
	 * Renders the currently active view.
	 * @param THtmlWriter $writer the writer for the rendering purpose.
	 */
	public function render($writer)
	{
		if (($view = $this->getActiveView()) !== null) {
			$view->renderControl($writer);
		}
	}
}
