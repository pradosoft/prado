<?php

class Home extends TPage
{
	const EASY_LEVEL=10;
	const MEDIUM_LEVEL=5;
	const HARD_LEVEL=3;

	public function selectLevel($sender,$param)
	{
		if($this->EasyLevel->Checked)
			$this->Level=self::EASY_LEVEL;
		else if($this->MediumLevel->Checked)
			$this->Level=self::MEDIUM_LEVEL;
		else if($this->HardLevel->Checked)
			$this->Level=self::HARD_LEVEL;
		else
		{
			$this->LevelError->Visible=true;
			return;
		}
		$wordFile=dirname(__FILE__).'/words.txt';
		$words=preg_split("/[\s,]+/",file_get_contents($wordFile));
		do
		{
			$i=rand(0,count($words)-1);
			$word=$words[$i];
		} while(strlen($word)<5 || !preg_match('/^[a-z]*$/i',$word));
		$word=strtoupper($word);

		$this->Word=$word;
		$this->GuessWord=str_repeat('_',strlen($word));
		$this->Misses=0;
		$this->showPanel('GuessPanel');
	}

	public function guessWord($sender,$param)
	{
		$sender->Enabled=false;
		$letter=$sender->Text;
		$word=$this->Word;
		$guessWord=$this->GuessWord;
		$pos=0;
		$success=false;
		while(($pos=strpos($word,$letter,$pos))!==false)
		{
			$guessWord[$pos]=$letter;
			$success=true;
			$pos++;
		}
		if($success)
		{
			$this->GuessWord=$guessWord;
			if($guessWord===$word)
				$this->showPanel('WinPanel');
		}
		else
		{
			$misses=$this->Misses+1;
			$this->Misses=$misses;
			if($misses>=$this->Level)
				$this->giveUp(null,null);
		}
	}

	public function giveUp($sender,$param)
	{
		$this->showPanel('LosePanel');
	}

	public function startAgain($sender,$param)
	{
		$this->showPanel('IntroPanel');
		$this->LevelError->Visible=false;
		for($letter=65;$letter<=90;++$letter)
		{
			$guessLetter='Guess'.chr($letter);
			$this->$guessLetter->Enabled=true;
		}
	}

	protected function showPanel($panelID)
	{
		$this->IntroPanel->Visible=false;
		$this->GuessPanel->Visible=false;
		$this->WinPanel->Visible=false;
		$this->LosePanel->Visible=false;
		$this->$panelID->Visible=true;
	}

	public function setLevel($value)
	{
		$this->setViewState('Level',$value,0);
	}

	public function getLevel()
	{
		return $this->getViewState('Level',0);
	}

	public function setWord($value)
	{
		$this->setViewState('Word',$value,'');
	}

	public function getWord()
	{
		return $this->getViewState('Word','');
	}

	public function getGuessWord()
	{
		return $this->getViewState('GuessWord','');
	}

	public function setGuessWord($value)
	{
		$this->setViewState('GuessWord',$value,'');
	}

	public function setMisses($value)
	{
		$this->setViewState('Misses',$value,0);
	}

	public function getMisses()
	{
		return $this->getViewState('Misses',0);
	}
}

?>