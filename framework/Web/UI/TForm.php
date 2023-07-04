<?php
/**
 * TForm class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;

/**
 * TForm class
 *
 * TForm displays an HTML form. Besides regular body content,
 * it displays hidden fields, javascript blocks and files that are registered
 * through {@see \Prado\Web\UI\TClientScriptManager}.
 *
 * A TForm is required for a page that needs postback.
 * Each page can contain at most one TForm. If multiple HTML forms are needed,
 * please use regular HTML form tags for those forms that post to different
 * URLs.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TForm extends TControl
{
	/**
	 * Registers the form with the page.
	 * @param mixed $param event parameter
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		$this->getPage()->setForm($this);
	}

	/**
	 * Adds form specific attributes to renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer
	 */
	protected function addAttributesToRender($writer)
	{
		$writer->addAttribute('id', $this->getClientID());
		$writer->addAttribute('method', $this->getMethod());
		$uri = $this->getRequest()->getRequestURI();
		$writer->addAttribute('action', str_replace('&', '&amp;', str_replace('&amp;', '&', $uri)));
		if (($enctype = $this->getEnctype()) !== '') {
			$writer->addAttribute('enctype', $enctype);
		}

		$attributes = $this->getAttributes();
		$attributes->remove('action');
		$writer->addAttributes($attributes);

		if (($butt = $this->getDefaultButton()) !== '') {
			if (($button = $this->findControl($butt)) !== null) {
				$this->getPage()->getClientScript()->registerDefaultButton($this, $button);
			} else {
				throw new TInvalidDataValueException('form_defaultbutton_invalid', $butt);
			}
		}
	}

	/**
	 * Renders the form.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer
	 */
	public function render($writer)
	{
		$page = $this->getPage();

		$this->addAttributesToRender($writer);
		$writer->renderBeginTag('form');

		$cs = $page->getClientScript();
		if ($page->getClientSupportsJavaScript()) {
			$cs->renderHiddenFieldsBegin($writer);
			$cs->renderScriptFilesBegin($writer);
			$cs->renderBeginScripts($writer);

			$page->beginFormRender($writer);
			$this->renderChildren($writer);
			$cs->renderHiddenFieldsEnd($writer);
			$page->endFormRender($writer);

			$cs->renderScriptFilesEnd($writer);
			$cs->renderEndScripts($writer);
		} else {
			$cs->renderHiddenFieldsBegin($writer);

			$page->beginFormRender($writer);
			$this->renderChildren($writer);
			$page->endFormRender($writer);

			$cs->renderHiddenFieldsEnd($writer);
		}

		$writer->renderEndTag();
	}

	/**
	 * @return string id path to the default button control.
	 */
	public function getDefaultButton()
	{
		return $this->getViewState('DefaultButton', '');
	}

	/**
	 * Sets a button to be default one in a form.
	 * A default button will be clicked if a user presses 'Enter' key within
	 * the form.
	 * @param string $value id path to the default button control.
	 */
	public function setDefaultButton($value)
	{
		$this->setViewState('DefaultButton', $value, '');
	}

	/**
	 * @return string form submission method. Defaults to 'post'.
	 */
	public function getMethod()
	{
		return $this->getViewState('Method', 'post');
	}

	/**
	 * @param string $value form submission method. Valid values include 'post' and 'get'.
	 */
	public function setMethod($value)
	{
		$this->setViewState('Method', TPropertyValue::ensureEnum($value, 'post', 'get'), 'post');
	}

	/**
	 * @return string the encoding type a browser uses to post data back to the server
	 */
	public function getEnctype()
	{
		return $this->getViewState('Enctype', '');
	}

	/**
	 * @param string $value the encoding type a browser uses to post data back to the server.
	 * Commonly used types include
	 * - application/x-www-form-urlencoded : Form data is encoded as name/value pairs. This is the standard encoding format.
	 * - multipart/form-data : Form data is encoded as a message with a separate part for each control on the page.
	 * - text/plain : Form data is encoded in plain text, without any control or formatting characters.
	 */
	public function setEnctype($value)
	{
		$this->setViewState('Enctype', $value, '');
	}

	/**
	 * @return string form name, which is equal to {@see getUniqueID UniqueID}.
	 */
	public function getName()
	{
		return $this->getUniqueID();
	}
}
