<?php
Prado::using('System.Data.ActiveRecord.TActiveRecord');

class BaseFkRecord extends TActiveRecord
{
	public function getDbConnection()
	{
		static $conn;
		if($conn===null)
		{
			$conn = new TDbConnection('pgsql:host=localhost;dbname=test', 'test','test');
			//$this->OnExecuteCommand[] = array($this,'logger');
		}
		return $conn;
	}

	function logger($sender,$param)
	{
	}
}

class TeamRecord extends BaseFkRecord
{
    const TABLE='teams';
    public $name;
    public $location;

    public $players=array();

    //define the $player member having has many relationship with PlayerRecord
    protected static $RELATIONS=array
    (
        'players' => array(self::HAS_MANY, 'PlayerRecord'),
    );

    public static function finder($className=__CLASS__)
    {
        return parent::finder($className);
    }
}

class PlayerRecord extends BaseFkRecord
{
    const TABLE='players';
    public $player_id;
    public $age;
    public $team_name;

    public $team;
    public $skills=array();
    public $profile;

    protected static $RELATIONS=array
    (
        'skills' => array(self::HAS_MANY, 'SkillRecord', 'player_skills'),
        'team' => array(self::BELONGS_TO, 'TeamRecord'),
        'profile' => array(self::HAS_ONE, 'ProfileRecord'),
    );

    public static function finder($className=__CLASS__)
    {
        return parent::finder($className);
    }
}

class ProfileRecord extends BaseFkRecord
{
    const TABLE='profiles';
    public $fk_player_id;
    public $salary;

    public $player;

    protected static $RELATIONS=array
    (
        'player' => array(self::BELONGS_TO, 'PlayerRecord'),
    );

    public static function finder($className=__CLASS__)
    {
        return parent::finder($className);
    }
}

class SkillRecord extends BaseFkRecord
{
    const TABLE='skills';
    public $skill_id;
    public $name;

    public $players=array();

    protected static $RELATIONS=array
    (
        'players' => array(self::HAS_MANY, 'PlayerRecord', 'player_skills'),
    );

    public static function finder($className=__CLASS__)
    {
        return parent::finder($className);
    }
}

class ForeignObjectUpdateTest extends UnitTestCase
{
	function test_add_has_one()
	{
		ProfileRecord::finder()->deleteByPk(3);

		$player = PlayerRecord::finder()->findByPk(3);
		$player->profile = new ProfileRecord(array('salary'=>50000));
		$player->save();

		//test insert
		$player2 = PlayerRecord::finder()->withProfile()->findByPk(3);
		$this->assertEqual($player2->profile->salary,50000);

		$player2->profile->salary = 45000;
		$player2->save();
		$this->assertEqual($player2->profile->salary,45000);

		//test update
		$player3 = PlayerRecord::finder()->withProfile()->findByPk(3);
		$this->assertEqual($player3->profile->salary,45000);
	}

	function test_add_many()
	{
		PlayerRecord::finder()->deleteAll("player_id > ?", 3);

		$team = TeamRecord::finder()->findByPk('Team b');
		$team->players[] = new PlayerRecord(array('age'=>20));
		$team->players[] = new PlayerRecord(array('age'=>25));
		$team->save();

		//test insert
		$team1 = TeamRecord::finder()->withPlayers()->findByPk('Team b');
		$this->assertEqual(count($team1->players),3);
		$this->assertEqual($team1->players[0]->age, 18);
		$this->assertEqual($team1->players[1]->age, 20);
		$this->assertEqual($team1->players[2]->age, 25);

		//test update
		$team1->players[1]->age = 55;
		$team1->save();

		$this->assertEqual($team1->players[0]->age, 18);
		$this->assertEqual($team1->players[1]->age, 55);
		$this->assertEqual($team1->players[2]->age, 25);

		$criteria = new TActiveRecordCriteria();
		$criteria->OrdersBy['age'] = 'desc';
		$team2 = TeamRecord::finder()->withPlayers($criteria)->findByPk('Team b');
		$this->assertEqual(count($team2->players),3);
		//ordered by age
		$this->assertEqual($team2->players[0]->age, 55);
		$this->assertEqual($team2->players[1]->age, 25);
		$this->assertEqual($team2->players[2]->age, 18);
	}

	function test_add_belongs_to()
	{
		TeamRecord::finder()->deleteByPk('Team c');
		PlayerRecord::finder()->deleteAll("player_id > ?", 3);

		$player = new PlayerRecord(array('age'=>27));
		$player->team = new TeamRecord(array('name'=>'Team c', 'location'=>'Sydney'));
		$player->save();

		//test insert
		$player1 = PlayerRecord::finder()->withTeam()->findByAge(27);
		$this->assertNotNull($player1);
		$this->assertNotNull($player1->team);
		$this->assertEqual($player1->team->name, 'Team c');
		$this->assertEqual($player1->team->location, 'Sydney');
	}

	function test_add_many_via_association()
	{
		PlayerRecord::finder()->deleteAll("player_id > ?", 3);
		SkillRecord::finder()->deleteAll("skill_id > ?", 3);

		$player = new PlayerRecord(array('age'=>37));
		$player->skills[] = new SkillRecord(array('name'=>'Bash'));
		$player->skills[] = new SkillRecord(array('name'=>'Jump'));
		$player->save();

		//test insert
		$player2 = PlayerRecord::finder()->withSkills()->findByAge(37);
		$this->assertNotNull($player2);
		$this->assertEqual(count($player2->skills), 2);
		$this->assertEqual($player2->skills[0]->name, 'Bash');
		$this->assertEqual($player2->skills[1]->name, 'Jump');

		//test update
		$player2->skills[1]->name = "Skip";
		$player2->skills[] = new SkillRecord(array('name'=>'Push'));
		$player2->save();

		$criteria = new TActiveRecordCriteria();
		$criteria->OrdersBy['name'] = 'asc';
		$player3 = PlayerRecord::finder()->withSkills($criteria)->findByAge(37);
		$this->assertNotNull($player3);
		$this->assertEqual(count($player3->skills), 3);
		$this->assertEqual($player3->skills[0]->name, 'Bash');
		$this->assertEqual($player3->skills[1]->name, 'Push');
		$this->assertEqual($player3->skills[2]->name, 'Skip');
	}
}

?>