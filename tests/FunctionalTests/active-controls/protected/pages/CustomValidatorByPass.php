<?php

class CustomValidatorByPass extends TPage
{
	public function onLoad($param)
	{

		parent::onLoad($param);

		$Client = $this->validator2->getActiveControl()->getClientSide();

		$Client->setOnLoading('$(\'loginLoader\').show();');
		$Client->setOnComplete('$(\'loginLoader\').hide();');

		//$Client->setOnValidationError('alert(\'Authentication Failed\');');
		$Client->setOnValidationSuccess('new Effect.Fade(\'loginBox\')');

	}

	public function validateUser($sender,$param)
	{
		$param->IsValid = $this->Password->Text=='test';
	}

	public function doLogin($sender,$param)
	{

		/* This isnt even getting called */
		if($this->Page->IsValid)
		{
			// Re-Render the active panel
		}

	}
}

?>