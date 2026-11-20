<?php

namespace Application\Emails;

use Prado\Email\UI\TEmailBody;

class ClassOnly extends TEmailBody
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		$this->getEmail()->setSubject('From the class');
		$this->getEmail()->setHtml('<p>Built in code</p>');
	}
}
