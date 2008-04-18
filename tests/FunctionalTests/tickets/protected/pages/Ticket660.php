<?php
Prado::Using ('System.Web.UI.ActiveControls.*');
class Ticket660 extends TPage {

    public function __construct() {
        Prado::getApplication()->getGlobalization()->setCharset('ISO-8859-1');
        parent::__construct();
    }

    public function changeA($sender,$param) {
        $iso_text=iconv('UTF-8', 'ISO-8859-1//IGNORE', 'ÄÖÜ äöü');
        $this->A->setText($this->T->getText() . $iso_text);
    }

	public function suggest($sender, $param)
	{
        $token=$param->getCallbackParameter();
		$sender->setDataSource(array(
            1 => 'ABC Token:'.$token,
            2 => iconv('UTF-8', 'ISO-8859-1//IGNORE', 'ÄÖÜ').' Token:'.$token
        ));
		$sender->dataBind();
	}

}
?>
