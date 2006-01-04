<?php

interface TLog
{
	public function info($msg, $source='Prado', $category='main');
	public function debug($msg, $source='Prado', $category='main');
	public function notice($msg, $source='Prado', $category='main');
	public function warn($msg, $source='Prado', $category='main');
	public function error($msg, $source='Prado', $category='main');
	public function fatal($msg, $source='Prado', $category='main');
}

?>