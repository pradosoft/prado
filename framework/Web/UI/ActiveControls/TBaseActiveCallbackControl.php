<?php
/**
 * TBaseActiveControl and TBaseActiveCallbackControl class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;

/**
 * TBaseActiveCallbackControl is a common set of options and functionality for
 * active controls that can perform callback requests.
 *
 * The properties of TBaseActiveCallbackControl can be accessed and changed from
 * each individual active controls' {@link getActiveControl ActiveControl}
 * property.
 *
 * The following example sets the validation group property of a TCallback component.
 * <code>
 * 	<com:TCallback ActiveControl.ValidationGroup="group1" ... />
 * </code>
 *
 * Additional client-side options and events can be set using the
 * {@link getClientSide ClientSide} property. The following example shows
 * an alert box when a TCallback component response returns successfully.
 * <code>
 * 	<com:TCallback ActiveControl.ClientSide.OnSuccess="alert('ok!')" ... />
 * </code>
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TBaseActiveCallbackControl extends TBaseActiveControl
{
	/**
	 * Callback client-side options can be set by setting the properties of
	 * the ClientSide property. E.g. <com:TCallback ActiveControl.ClientSide.OnSuccess="..." />
	 * See {@link TCallbackClientSide} for details on the properties of ClientSide.
	 * @return TCallbackClientSide client-side callback options.
	 */
	public function getClientSide()
	{
		if (($client = $this->getOption('ClientSide')) === null) {
			$client = $this->createClientSide();
			$this->setOption('ClientSide', $client);
		}
		return $client;
	}

	/**
	 * Sets the client side options. Can only be set when client side is null.
	 * @param TCallbackClientSide $client client side options.
	 */
	public function setClientSide($client)
	{
		if ($this->getOption('ClientSide') === null) {
			$this->setOption('ClientSide', $client);
		} else {
			throw new TConfigurationException(
				'active_controls_client_side_exists',
				$this->getControl()->getID()
			);
		}
	}

	/**
	 * @return TCallbackClientSide callback client-side options.
	 */
	protected function createClientSide()
	{
		return new TCallbackClientSide;
	}

	/**
	 * Sets default callback options. Takes the ID of a TCallbackOptions
	 * component to duplicate the client-side
	 * options for this control. The {@link getClientSide ClientSide}
	 * subproperties takes precedence over the CallbackOptions property.
	 * @param string $value ID of a TCallbackOptions control from which ClientSide
	 * options are cloned.
	 */
	public function setCallbackOptions($value)
	{
		$this->setOption('CallbackOptions', $value, '');
	}

	/**
	 * @return string ID of a TCallbackOptions control from which ClientSide
	 * options are duplicated.
	 */
	public function getCallbackOptions()
	{
		return $this->getOption('CallbackOptions', '');
	}

	/**
	 * Returns an array of default callback client-side options. The default options
	 * are obtained from the client-side options of a TCallbackOptions control with
	 * ID specified by {@link setCallbackOptions CallbackOptions}.
	 * @return array list of default callback client-side options.
	 */
	protected function getDefaultClientSideOptions()
	{
		if (($id = $this->getCallbackOptions()) !== '') {
			if (($pos = strrpos($id, '.')) !== false) {
				$control = $this->getControl()->getSubProperty(substr($id, 0, $pos));
				$newid = substr($id, $pos + 1);
				if ($control !== null) {
					$control = $control->$newid;
				}
			} else {
				// TCheckBoxList overrides findControl() with a fake implementation
				// but accepts a second parameter to use the standard one
				$control = $this->getControl()->findControl($id, true);
			}

			if ($control instanceof TCallbackOptions) {
				return $control->getClientSide()->getOptions()->toArray();
			} else {
				throw new TConfigurationException('callback_invalid_callback_options', $this->getControl()->getID(), $id);
			}
		}

		return [];
	}

	/**
	 * @return bool whether callback event trigger by this button will cause
	 * input validation, default is true
	 */
	public function getCausesValidation()
	{
		return $this->getOption('CausesValidation', true);
	}

	/**
	 * @param bool $value whether callback event trigger by this button will cause
	 * input validation
	 */
	public function setCausesValidation($value)
	{
		$this->setOption('CausesValidation', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return string the group of validators which the button causes validation
	 * upon callback
	 */
	public function getValidationGroup()
	{
		return $this->getOption('ValidationGroup', '');
	}

	/**
	 * @param string $value the group of validators which the button causes validation
	 * upon callback
	 */
	public function setValidationGroup($value)
	{
		$this->setOption('ValidationGroup', $value, '');
	}

	/**
	 * @return bool whether to perform validation if the callback is
	 * requested.
	 */
	public function canCauseValidation()
	{
		if ($this->getCausesValidation()) {
			$group = $this->getValidationGroup();
			return $this->getPage()->getValidators($group)->getCount() > 0;
		} else {
			return false;
		}
	}

	/**
	 * @param mixed $value callback parameter value.
	 */
	public function setCallbackParameter($value)
	{
		$this->setOption('CallbackParameter', $value, '');
	}

	/**
	 * @return mixed callback parameter value.
	 */
	public function getCallbackParameter()
	{
		return $this->getOption('CallbackParameter', '');
	}


	/**
	 * @return array list of callback javascript options.
	 */
	protected function getClientSideOptions()
	{
		$default = $this->getDefaultClientSideOptions();
		$options = array_merge($default, $this->getClientSide()->getOptions()->toArray());
		$validate = $this->getCausesValidation();
		$options['CausesValidation'] = $validate ? '' : false;
		$options['ValidationGroup'] = $this->getValidationGroup();
		$options['CallbackParameter'] = $this->getCallbackParameter();
		// needed for TCallback
		if (!isset($options['EventTarget'])) {
			$options['EventTarget'] = $this->getControl()->getUniqueID();
		}
		return $options;
	}

	/**
	 * Registers the callback control javascript code. Client-side options are
	 * merged and passed to the javascript code. This method should be called by
	 * Active component developers wanting to register the javascript to initialize
	 * the active component with additional options offered by the
	 * {@link getClientSide ClientSide} property.
	 * @param string $class client side javascript class name.
	 * @param null|array $options additional callback options.
	 */
	public function registerCallbackClientScript($class, $options = null)
	{
		$cs = $this->getPage()->getClientScript();
		if (is_array($options)) {
			$options = array_merge($this->getClientSideOptions(), $options);
		} else {
			$options = $this->getClientSideOptions();
		}

		//remove true as default to save bytes
		if ($options['CausesValidation'] === true) {
			$options['CausesValidation'] = '';
		}
		$cs->registerCallbackControl($class, $options);
	}

	/**
	 * Returns the javascript callback request instance. To invoke a callback
	 * request for this control call the <tt>dispatch()</tt> method on the
	 * request instance. Example code in javascript
	 * <code>
	 *   var request = <%= $this->mycallback->ActiveControl->Javascript %>;
	 *   request.setParameter('hello');
	 *   request.dispatch(); //make the callback request.
	 * </code>
	 *
	 * Alternatively,
	 * <code>
	 * //dispatches immediately
	 * Prado.Callback("<%= $this->mycallback->UniqueID %>",
	 *    $this->mycallback->ActiveControl->JsCallbackOptions);
	 * </code>
	 * @return string javascript client-side callback request object (javascript
	 * code)
	 */
	public function getJavascript()
	{
		$client = $this->getPage()->getClientScript();
		return $client->getCallbackReference($this->getControl(), $this->getClientSideOptions());
	}

	/**
	 * @return string callback request options as javascript code.
	 */
	public function getJsCallbackOptions()
	{
		return TJavaScript::encode($this->getClientSideOptions());
	}
}
