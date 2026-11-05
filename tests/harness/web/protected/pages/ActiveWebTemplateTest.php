<?php

class ActiveWebTemplateTest extends TPage
{
	public function server_stamp($sender, $param)
	{
		$this->rowTemplate->stampInto('listBody', ['name' => 'Ada', 'role' => 'Engineer', 'id' => 7]);
	}

	public function server_repeat($sender, $param)
	{
		$this->rowTemplate->repeatInto('listBody', [
			['name' => 'Ada', 'role' => 'Engineer', 'id' => 1],
			['name' => 'Grace', 'role' => 'Admiral', 'id' => 2],
			['name' => 'Alan', 'role' => 'Logician', 'id' => 3],
		]);
	}

	public function server_update_first($sender, $param)
	{
		$this->rowTemplate->updateInstance($param->getCallbackParameter(), ['role' => 'Countess']);
	}

	public function server_update_all($sender, $param)
	{
		$this->rowTemplate->updateAll(['role' => 'Retired']);
	}

	public function server_set_content($sender, $param)
	{
		$this->rowTemplate->setContent('<div class="row v2"><b class="name">{{name}}</b></div>', true);
	}

	public function server_remove_first($sender, $param)
	{
		$this->rowTemplate->removeInstance($param->getCallbackParameter());
	}
}
