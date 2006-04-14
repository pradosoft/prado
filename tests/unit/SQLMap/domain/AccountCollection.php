<?php

class AccountCollection extends TList
{
	public function addRange($accounts)
	{
		foreach($accounts as $account)
			$this->add($account);
	}

	public function copyTo(TList $array)
	{
		$array->copyFrom($this);
	}
}

?>