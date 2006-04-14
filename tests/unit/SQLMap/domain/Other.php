<?php

class Other
{
	private $_Int=-1;
	private $_Long=-1;
	private $_Bool=false;
	private $_Bool2=false;

	public function getBool2(){ return $this->_Bool2; }
	public function setBool2($value){ $this->_Bool2 = $value; }

	public function getBool(){ return $this->_Bool; }
	public function setBool($value){ $this->_Bool = $value; }

	public function getInt(){ return $this->_Int; }
	public function setInt($value){ $this->_Int = $value; }

	public function getLong(){ return $this->_Long; }
	public function setLong($value){ $this->_Long = $value; }
}

?>