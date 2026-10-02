<?php

/**
 * TProofOfWorkValidator class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;

/**
 * TProofOfWorkValidator class.
 *
 * TProofOfWorkValidator validates the {@see TProofOfWork} named by
 * {@see setControlToValidate ControlToValidate}. It fails when the posted solution is
 * missing, altered, expired, or already used. Client-side validation is off because the
 * client control holds the submission until the solution is ready.
 *
 * ```php
 * <com:TProofOfWork ID="Work" />
 * <com:TProofOfWorkValidator ControlToValidate="Work"
 *     ErrorMessage="Verification did not finish. Please submit again." />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TProofOfWorkValidator extends TBaseValidator
{
	/**
	 * @return bool false; the server verifies the solution.
	 */
	public function getEnableClientScript()
	{
		return false;
	}

	/**
	 * @return string the client class name; unused because client validation is off.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TBaseValidator';
	}

	/**
	 * @throws TConfigurationException if ControlToValidate does not name a TProofOfWork.
	 * @return bool whether the TProofOfWork accepts its posted solution.
	 */
	protected function evaluateIsValid()
	{
		$control = $this->getValidationTarget();
		if (!($control instanceof TProofOfWork)) {
			throw new TConfigurationException('proofofworkvalidator_control_invalid', $this->getControlToValidate());
		}
		return $control->validate();
	}
}
