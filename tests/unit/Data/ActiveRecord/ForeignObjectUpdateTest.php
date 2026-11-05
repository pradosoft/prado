<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

class ForeignObjectUpdateTest extends \PHPUnit\Framework\TestCase
{
	public function test_add_has_one()
	{
		$this->markTestSkipped('Test exposes framework bug: TList cannot be converted to int for foreign key');
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
		$this->markTestSkipped('Test exposes framework bug: Property "players" on null');
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
		$this->markTestSkipped('Test exposes framework bug: null foreign key not allowed when column allows null');
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
		$this->markTestSkipped('Test exposes framework bug: null foreign key not allowed when column allows null');
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
