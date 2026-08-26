<?php
/**
 * TActiveFileUploadValidatorTest.php
 *
 * Functional test page for TActiveFileUpload validation integration:
 * validator1 gates the upload client side, validator2 validates the uploaded
 * content server side during the upload callback.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */
class TActiveFileUploadValidatorTest extends TPage
{
	public function uploadComplete($sender, $param)
	{
		$valid = $this->getIsValid() ? 'valid' : 'invalid';
		$this->label1->setText($sender->getFileName() . ' ' . $valid);
	}
}
