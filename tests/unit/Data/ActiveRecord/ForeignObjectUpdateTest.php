<?php


class BaseFkRecord extends TActiveRecord
{
	public function getDbConnection()
	{
		static $conn;
		if ($conn === null) {
			$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest', 'prado_unitest');
			//$this->OnExecuteCommand[] = array($this,'logger');
		}
		return $conn;
	}

	public function logger($sender, $param)
	{
	}
}

class TeamRecord extends BaseFkRecord
{
	const TABLE = 'teams';
	public $name;
	public $location;

	public $players = [];

	//define the $player member having has many relationship with PlayerRecord
	public static $RELATIONS = [
		'players' => [self::HAS_MANY, 'PlayerRecord'],
	];

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}

class PlayerRecord extends BaseFkRecord
{
	const TABLE = 'players';
	public $player_id;
	public $age;
	public $team_name;

	public $team;
	private $_skills;
	public $profile;

	public static $RELATIONS = [
		'skills' => [self::MANY_TO_MANY, 'SkillRecord', 'player_skills'],
		'team' => [self::BELONGS_TO, 'TeamRecord'],
		'profile' => [self::HAS_ONE, 'ProfileRecord'],
	];

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}

	public function getSkills()
	{
		if ($this->_skills === null && $this->player_id !== null) {
			//lazy load the skill records
			$this->setSkills($this->withSkills()->findByPk($this->player_id)->skills);
		} elseif ($this->_skills === null) {
			//create new TList;
			$this->setSkills(new TList());
		}
		return $this->_skills;
	}

	public function setSkills($value)
	{
		$this->_skills = $value instanceof TList ? $value : new TList($value);
	}
}

class ProfileRecord extends BaseFkRecord
{
	const TABLE = 'profiles';
	public $fk_player_id;
	public $salary;

	public $player;

	public static $RELATIONS = [
		'player' => [self::BELONGS_TO, 'PlayerRecord'],
	];

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}

class SkillRecord extends BaseFkRecord
{
	const TABLE = 'skills';
	public $skill_id;
	public $name;

	public $players = [];

	public static $RELATIONS = [
		'players' => [self::MANY_TO_MANY, 'PlayerRecord', 'player_skills'],
	];

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}

class ForeignObjectUpdateTest extends PHPUnit\Framework\TestCase
{
	public function test_add_has_one()
	{
		$this->markTestSkipped('Needs fixing');
		/*
				ProfileRecord::finder()->deleteByPk(1);

				$player = new PlayerRecord(array('age'=>27));
				$player->team = 'Team c';
				$player->profile = new ProfileRecord(array('salary'=>50000));
				$player->save();

				//test insert
				$player2 = PlayerRecord::finder()->withProfile()->findByPk(1);
				$this->assertEquals($player2->profile->salary,50000);

				$player2->profile->salary = 45000;
				$player2->save();
				$this->assertEquals($player2->profile->salary,45000);

				//test update
				$player3 = PlayerRecord::finder()->withProfile()->findByPk(1);
				$this->assertEquals($player3->profile->salary,45000);
		*/
	}

	public function test_add_many()
	{
		$this->markTestSkipped('Needs fixing');
		/*
				PlayerRecord::finder()->deleteAll("player_id > ?", 3);

				$team = TeamRecord::finder()->findByPk('Team b');
				$team->players[] = new PlayerRecord(array('age'=>20));
				$team->players[] = new PlayerRecord(array('age'=>25));
				$team->save();

				//test insert
				$team1 = TeamRecord::finder()->withPlayers()->findByPk('Team b');
				$this->assertEquals(count($team1->players),3);
				$this->assertEquals($team1->players[0]->age, 18);
				$this->assertEquals($team1->players[1]->age, 20);
				$this->assertEquals($team1->players[2]->age, 25);

				//test update
				$team1->players[1]->age = 55;
				$team1->save();

				$this->assertEquals($team1->players[0]->age, 18);
				$this->assertEquals($team1->players[1]->age, 55);
				$this->assertEquals($team1->players[2]->age, 25);

				$criteria = new TActiveRecordCriteria();
				$criteria->OrdersBy['age'] = 'desc';
				$team2 = TeamRecord::finder()->withPlayers($criteria)->findByPk('Team b');
				$this->assertEquals(count($team2->players),3);
				//ordered by age
				$this->assertEquals($team2->players[0]->age, 55);
				$this->assertEquals($team2->players[1]->age, 25);
				$this->assertEquals($team2->players[2]->age, 18);
		*/
	}

	public function test_add_belongs_to()
	{
		$this->markTestSkipped('Needs fixing');
		/*
				TeamRecord::finder()->deleteByPk('Team c');
				PlayerRecord::finder()->deleteAll("player_id > ?", 3);

				$player = new PlayerRecord(array('age'=>27));
				$player->team = new TeamRecord(array('name'=>'Team c', 'location'=>'Sydney'));
				$player->save();

				//test insert
				$player1 = PlayerRecord::finder()->withTeam()->findByAge(27);
				$this->assertNotNull($player1);
				$this->assertNotNull($player1->team);
				$this->assertEquals($player1->team->name, 'Team c');
				$this->assertEquals($player1->team->location, 'Sydney');
		*/
	}

	public function test_add_many_via_association()
	{
		$this->markTestSkipped('Needs fixing');
		/*
				PlayerRecord::finder()->deleteAll("player_id > ?", 3);
				SkillRecord::finder()->deleteAll("skill_id > ?", 3);

				$player = new PlayerRecord(array('age'=>37));
				$player->skills[] = new SkillRecord(array('name'=>'Bash'));
				$player->skills[] = new SkillRecord(array('name'=>'Jump'));
				$player->save();

				//test insert
				$player2 = PlayerRecord::finder()->withSkills()->findByAge(37);
				$this->assertNotNull($player2);
				$this->assertEquals(count($player2->skills), 2);
				$this->assertEquals($player2->skills[0]->name, 'Bash');
				$this->assertEquals($player2->skills[1]->name, 'Jump');

				//test update
				$player2->skills[1]->name = "Skip";
				$player2->skills[] = new SkillRecord(array('name'=>'Push'));
				$player2->save();

				$criteria = new TActiveRecordCriteria();
				$criteria->OrdersBy['name'] = 'asc';
				$player3 = PlayerRecord::finder()->withSkills($criteria)->findByAge(37);
				$this->assertNotNull($player3);
				$this->assertEquals(count($player3->skills), 3);
				$this->assertEquals($player3->skills[0]->name, 'Bash');
				$this->assertEquals($player3->skills[1]->name, 'Push');
				$this->assertEquals($player3->skills[2]->name, 'Skip');

				//test lazy load
				$player4 = PlayerRecord::finder()->findByAge(37);
				$this->assertEquals(count($player4->skills), 3);

				$this->assertEquals($player4->skills[0]->name, 'Bash');
				$this->assertEquals($player4->skills[1]->name, 'Skip');
				$this->assertEquals($player4->skills[2]->name, 'Push');
		*/
	}
}
