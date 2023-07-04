<?php
/**
 * TActiveMultiView class file
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link http://www.landwehr-software.de/
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Includes the following used classes
 */
use Prado\Prado;
use Prado\Web\UI\WebControls\TMultiView;

/**
 * TActiveMultiView class.
 *
 * TActiveMultiView is the active counterpart to the original {@see \Prado\Web\UI\WebControls\TMultiView} control.
 * It re-renders on Callback when {@see setActiveView ActiveView} or
 * {@see setActiveViewIndex ActiveViewIndex} is called.
 *
 * Please refer to the original documentation of the regular counterpart for usage.
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @since 3.1.6
 * @method TActiveControlAdapter getAdapter()
 */
class TActiveMultiView extends TMultiView implements IActiveControl
{
	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}

	/**
	 * @return TBaseActiveControl standard active control options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	/**
	 * Returns the id of the surrounding container (span).
	 * @return string container id
	 */
	protected function getContainerID()
	{
		return $this->getClientID() . '_Container';
	}

	/**
	 * Renders the TActiveMultiView.
	 * If the MutliView did not pass the prerender phase yet, it will register itself for rendering later.
	 * Else it will call the {@see renderMultiView()} method which will do the rendering of the MultiView.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function render($writer)
	{
		if ($this->getHasPreRendered()) {
			$this->renderMultiView($writer);
			if ($this->getActiveControl()->canUpdateClientSide()) {
				$this->getPage()->getCallbackClient()->replaceContent($this->getContainerID(), $writer);
			}
		} else {
			$this->getPage()->getAdapter()->registerControlToRender($this, $writer);
		}
	}

	/**
	 * Renders the TActiveMultiView by writing a span tag with the container id obtained from {@see getContainerID()}
	 * which will be called by the replacement method of the client script to update it's content.
	 * @param \Prado\Web\UI\THtmlWriter $writer THtmlWriter writer for the rendering purpose
	 */
	protected function renderMultiView($writer)
	{
		$writer->addAttribute('id', $this->getContainerID());
		$writer->renderBeginTag('span');
		parent::render($writer);
		$writer->renderEndTag();
	}

	/**
	 * @param int $value the zero-based index of the current view in the view collection. -1 if no active view.
	 * @throws \Prado\Exceptions\TInvalidDataValueException if the view index is invalid
	 */
	public function setActiveViewIndex($value)
	{
		parent::setActiveViewIndex($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->getPage()->getAdapter()->registerControlToRender($this, $this->getResponse()->createHtmlWriter());
		}
	}

	/**
	 * @param \Prado\Web\UI\WebControls\TView $value the view to be activated
	 * @throws \Prado\Exceptions\TInvalidOperationException if the view is not in the view collection
	 */
	public function setActiveView($value)
	{
		parent::setActiveView($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->getPage()->getAdapter()->registerControlToRender($this, $this->getResponse()->createHtmlWriter());
		}
	}
}
