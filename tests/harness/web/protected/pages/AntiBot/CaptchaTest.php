<?php

/**
 * Harness page for TCaptcha functional tests.
 *
 * The page prints the token so the test can submit it; a real page never does.
 * The AntiBot folder configures a file cache, which makes the token single-use.
 */
class CaptchaTest extends TPage
{
	public function sendClicked($sender, $param)
	{
		$this->Result->setText($this->getIsValid() ? 'Accepted' : 'Rejected');
	}

	public function onPreRenderComplete($param)
	{
		parent::onPreRenderComplete($param);
		$this->Token->setText($this->Captcha->getToken());
	}
}
