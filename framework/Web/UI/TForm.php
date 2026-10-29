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
 * | Property | Attribute |
 * |---|---|
 * | {@see setMethod Method}, {@see setEnctype Enctype} | `method`, `enctype` |
 * | {@see setNoValidate NoValidate} | `novalidate`: no browser constraint check on submission |
 * | {@see setAutoComplete AutoComplete} | `autocomplete`: the default autofill of the form's fields |
 * | {@see setTarget Target} | `target`: the browsing context that shows the response |
 * | {@see setAcceptCharset AcceptCharset} | `accept-charset` |
 * | {@see setRel Rel} | `rel`: link types of the submission, such as `noopener` |
 *
 * A button overrides the action, encoding, target, and constraint check of a
 * submission with {@see \Prado\Web\UI\WebControls\TFormSubmitterTrait}.
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
		if ($this->getNoValidate()) {
			$writer->addAttribute('novalidate', 'novalidate');
		}
		if (($autoComplete = $this->getAutoComplete()) !== null) {
			$writer->addAttribute('autocomplete', $autoComplete ? 'on' : 'off');
		}
		foreach (['target' => $this->getTarget(), 'accept-charset' => $this->getAcceptCharset(),
			'rel' => $this->getRel()] as $name => $value) {
			if ($value !== '') {
				$writer->addAttribute($name, $value);
			}
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
	 * @return bool whether the browser skips the constraint check on submission. Defaults to false.
	 * @since 4.4.0
	 */
	public function getNoValidate()
	{
		return $this->getViewState('NoValidate', false);
	}

	/**
	 * Sets whether the browser skips the constraint check of the form's fields, such
	 * as `required` and `pattern`, when the form submits (`novalidate`). The server
	 * validators still run.
	 * @param bool|string $value whether the browser skips the check
	 * @since 4.4.0
	 */
	public function setNoValidate($value)
	{
		$this->setViewState('NoValidate', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return ?bool whether the browser autofills the form's fields, null for the
	 *   browser default
	 * @since 4.4.0
	 */
	public function getAutoComplete()
	{
		return $this->getViewState('AutoComplete', null);
	}

	/**
	 * Sets whether the browser autofills the form's fields by default (`autocomplete`).
	 * A field's own `autocomplete` takes precedence.
	 * @param null|bool|string $value true, false, or null or '' for the browser default
	 * @since 4.4.0
	 */
	public function setAutoComplete($value)
	{
		$this->setViewState('AutoComplete', ($value === null || $value === '') ? null : TPropertyValue::ensureBoolean($value), null);
	}

	/**
	 * @return string the browsing context that shows the response, or '' if not set
	 * @since 4.4.0
	 */
	public function getTarget()
	{
		return $this->getViewState('Target', '');
	}

	/**
	 * Sets the browsing context that shows the response of a submission (`target`),
	 * such as `_blank` or a frame name.
	 * @param string $value the target
	 * @since 4.4.0
	 */
	public function setTarget($value)
	{
		$this->setViewState('Target', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @return string the character encodings the server accepts, or '' if not set
	 * @since 4.4.0
	 */
	public function getAcceptCharset()
	{
		return $this->getViewState('AcceptCharset', '');
	}

	/**
	 * Sets the character encoding of the submission (`accept-charset`). HTML allows
	 * only `UTF-8`, which is the default.
	 * @param string $value the encoding
	 * @since 4.4.0
	 */
	public function setAcceptCharset($value)
	{
		$this->setViewState('AcceptCharset', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @return string the link types of the submission, or '' if not set
	 * @since 4.4.0
	 */
	public function getRel()
	{
		return $this->getViewState('Rel', '');
	}

	/**
	 * Sets the link types of the submission (`rel`), such as `noopener noreferrer`
	 * for a {@see setTarget Target} of `_blank`.
	 * @param string $value space-separated link types
	 * @since 4.4.0
	 */
	public function setRel($value)
	{
		$this->setViewState('Rel', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @return string form name, which is equal to {@see getUniqueID UniqueID}.
	 */
	public function getName()
	{
		return $this->getUniqueID();
	}
}
