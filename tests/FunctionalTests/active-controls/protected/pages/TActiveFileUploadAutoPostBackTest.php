<?php
/**
 * BActiveFileUploadTest.php
 *
 * @author Bradley Booms <Bradley.Booms@nsighttel.com>
 * @version Creation Date: Aug 9, 2007
 */

/**
 * BActiveFileUploadTest.php class
 *
 *
 *
 * Properties
 * -
 *
 * @author Bradley Booms <Bradley.Booms@nsighttel.com>
 * @version Modified Date: Aug 9, 2007
 *
 * Modifications:
 */
class TActiveFileUploadAutoPostBackTest extends TPage
{
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->button1->Attributes->onclick = "{$this->uploader->getCallbackJavascript()}; return false;";
	}
	
	public function uploadComplete($sender, $param)
	{
		$this->label1->setText($sender->getFileName());
	}
}
