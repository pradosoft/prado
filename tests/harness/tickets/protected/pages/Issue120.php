<?php

class Issue120 extends TPage
{
	public function buttonClickCallback($sender, $param)
	{
		$this -> ddl1 -> setDataSource(
			[
				'callback value 1' => 'callback item 1',
				'callback value 2' => 'callback item 2',
				'callback value 3' => 'callback item 3',
				'callback value 4' => 'callback item 4'
			]
		);
		$this -> ddl1 -> dataBind();
	}
}
