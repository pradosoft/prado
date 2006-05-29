<?php

class ErrorReport extends BlogPage
{
	public function getErrorMessage()
	{
		$id=TPropertyValue::ensureInteger($this->Request['id']);
		return BlogErrors::getMessage($id);
	}
}

?>