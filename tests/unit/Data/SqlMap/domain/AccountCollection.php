<?php

class AccountCollection extends \Prado\Collections\TList
{
	public function addRange($accounts)
	{
		foreach ($accounts as $account) {
			$this->add($account);
		}
	}

	public function copyTo(TList $array)
	{
		$array->copyFrom($this);
	}
}
