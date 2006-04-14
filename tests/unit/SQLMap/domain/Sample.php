<?php

class Sample
{
	private $_FirstID='';
	private $_SecondID='';
	private $_ThirdID='';
	private $_FourthID='';
	private $_FifthID='';
	private $_SequenceID='';
	private $_DistributedID='';
	private $_SampleChar='';
	private $_SampleDecimal='';
	private $_SampleMoney='';
	private $_SampleDate='';
	private $_SequenceDate='';

	public function getFirstID(){ return $this->_FirstID; }
	public function setFirstID($value){ $this->_FirstID = $value; }

	public function getSecondID(){ return $this->_SecondID; }
	public function setSecondID($value){ $this->_SecondID = $value; }

	public function getThirdID(){ return $this->_ThirdID; }
	public function setThirdID($value){ $this->_ThirdID = $value; }

	public function getFourthID(){ return $this->_FourthID; }
	public function setFourthID($value){ $this->_FourthID = $value; }

	public function getFifthID(){ return $this->_FifthID; }
	public function setFifthID($value){ $this->_FifthID = $value; }

	public function getSequenceID(){ return $this->_SequenceID; }
	public function setSequenceID($value){ $this->_SequenceID = $value; }

	public function getDistributedID(){ return $this->_DistributedID; }
	public function setDistributedID($value){ $this->_DistributedID = $value; }

	public function getSampleChar(){ return $this->_SampleChar; }
	public function setSampleChar($value){ $this->_SampleChar = $value; }

	public function getSampleDecimal(){ return $this->_SampleDecimal; }
	public function setSampleDecimal($value){ $this->_SampleDecimal = $value; }

	public function getSampleMoney(){ return $this->_SampleMoney; }
	public function setSampleMoney($value){ $this->_SampleMoney = $value; }

	public function getSampleDate(){ return $this->_SampleDate; }
	public function setSampleDate($value){ $this->_SampleDate = $value; }

	public function getSequenceDate(){ return $this->_SequenceDate; }
	public function setSequenceDate($value){ $this->_SequenceDate = $value; }
}

?>