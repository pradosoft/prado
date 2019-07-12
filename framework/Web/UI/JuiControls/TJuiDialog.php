<?php
/**
 * TJuiDialog class file.
 *
 * @author  David Otto <ottodavid[at]gmx[dot]net>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\JuiControls
 */

namespace Prado\Web\UI\JuiControls;

use Prado\Exceptions\TNotSupportedException;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TActivePanel;

/**
 * TJuiDialog class.
 *
 * TJuiDialog is an extension to {@link TActivePanel} based on jQuery-UI's
 * {@link http://jqueryui.com/dialog/ Dialog} widget.
 *
 *
 * <code>
 * <com:TJuiDialog
 *	ID="dlg1"
 * >
 * contents
 * </com:TJuiDialog>
 * </code>
 *
 * @author David Otto <ottodavid[at]gmx[dot]net>
 * @package Prado\Web\UI\JuiControls
 * @since 3.3
 */
class TJuiDialog extends TActivePanel implements IJuiOptions, ICallbackEventHandler
{
	protected $_options;

	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, by calling this constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TJuiControlAdapter($this));
	}

	/**
	 * @return string the name of the jQueryUI widget method
	 */
	public function getWidget()
	{
		return 'dialog';
	}

	/**
	 * @return string the clientid of the jQueryUI widget element
	 */
	public function getWidgetID()
	{
		return $this->getClientID();
	}

	/**
	 * Object containing defined javascript options
	 * @return TJuiControlOptions
	 */
	public function getOptions()
	{
		if (($options = $this->getViewState('JuiOptions')) === null) {
			$options = new TJuiControlOptions($this);
			$this->setViewState('JuiOptions', $options);
		}
		return $options;
	}

	/**
	 * Array containing valid javascript options
	 * @return array()
	 */
	public function getValidOptions()
	{
		return ['appendTo', 'autoOpen', 'buttons', 'closeOnEscape', 'closeText', 'dialogClass', 'draggable', 'height', 'hide', 'minHeight', 'minWidth', 'maxHeight', 'maxWidth', 'modal', 'position', 'resizable', 'show', 'title', 'width'];
	}

	/**
	 * Array containing valid javascript events
	 * @return array()
	 */
	public function getValidEvents()
	{
		return ['beforeClose', 'close', 'create', 'drag', 'dragStart', 'dragStop', 'focus', 'open', 'resize', 'resizeStart', 'resizeStop'];
	}

	/**
	 * @return array list of callback options.
	 */
	protected function getPostBackOptions()
	{
		$options = $this->getOptions()->toArray();
		// always make the dialog a child of the form, or its inner inputs won't be collected
		if (!isset($options['appendTo'])) {
			$options['appendTo'] = 'form:first';
		}

		foreach ($this->getControls() as $control) {
			if ($control instanceof TJuiDialogButton) {
				$options['buttons'][] = $control->getPostBackOptions();
			}
		}

		return $options;
	}

	/**
	 * Ensure that the ID attribute is rendered and registers the javascript code
	 * for initializing the active control.
	 * @param mixed $writer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);

		$writer->addAttribute('id', $this->getClientID());
		$options = TJavaScript::encode($this->getPostBackOptions());
		$cs = $this->getPage()->getClientScript();
		$code = "jQuery('#" . $this->getWidgetID() . "')." . $this->getWidget() . "(" . $options . ");";
		$cs->registerEndScript(sprintf('%08X', crc32($code)), $code);
	}

	/**
	 * Raises callback event. This method is required by the {@link ICallbackEventHandler}
	 * interface.
	 * @param TCallbackEventParameter $param the parameter associated with the callback event
	 */
	public function raiseCallbackEvent($param)
	{
		$this->getOptions()->raiseCallbackEvent($param);
	}

	/**
	 * Raises the OnCreate event
	 * @param object $params event parameters
	 */
	public function onOpen($params)
	{
		$this->raiseEvent('OnOpen', $this, $params);
	}

	/**
	 * Open this dialog
	 */
	public function open()
	{
		$this->triggerClientMethod('open');
	}

	/**
	 * Close this dialog
	 */
	public function close()
	{
		$this->triggerClientMethod('close');
	}

	/**
	 * Executes a method on the dialog
	 * @param string $method name
	 */
	private function triggerClientMethod($method)
	{
		$cs = $this->getPage()->getClientScript();
		$code = "jQuery(document).ready(function() { jQuery('#" . $this->getClientId() . "').dialog('" . $method . "'); })";
		$cs->registerEndScript(sprintf('%08X', crc32($code)), $code);
	}

	/**
	 * Rendering as a fieldset is not supported for TJuiDialog.
	 * @param string $value the legend text. If this value is not empty, the panel will be rendered as a fieldset.
	 * @throws TNotSupportedException not supported for TJuiDialog.
	 */
	public function setGroupingText($value)
	{
		throw new TNotSupportedException('Rendering as a fieldset is not supported for {0}.', get_class($this));
	}

	/**
	 * Overrides parent implementation to just render the inner contents and avoid replacing the element itself when
	 * updating clientside, because replacing/removing will cause jqueryui to fire destroy on the original dialog extension.
	 * @param THtmlWriter $writer html writer
	 */
	public function render($writer)
	{
		if ($this->getHasPreRendered() && $this->getActiveControl()->canUpdateClientSide()) {
			parent::renderContents($writer);
			$this->getPage()->getCallbackClient()->replaceContent($this, $writer, false);
		} else {
			parent::render($writer);
		}
	}
}
