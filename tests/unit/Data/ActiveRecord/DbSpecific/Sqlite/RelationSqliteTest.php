<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlite;

use Prado\Collections\TList;
use Prado\Data\ActiveRecord\Exceptions\TActiveRecordException;
use Prado\Data\ActiveRecord\Relations\TActiveRecordRelationContext;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordCriteria;
use Prado\Data\ActiveRecord\TActiveRecordManager;
use Prado\Data\TDbConnection;
use Prado\Test\Unit\PradoUnit;

/**
 * Records over an in-memory SQLite database with every relation type. RelTeam and
 * RelPlayer leave some relations undeclared as properties, so they go through
 * TActiveRecord::__get() and __set(); RelPlayer declares `team` and `profile`.
 */
abstract class RelSqliteRecord extends TActiveRecord
{
	public const SCHEMA = <<<'SQL'
		CREATE TABLE rel_teams (name varchar(20) NOT NULL PRIMARY KEY, location varchar(20));
		CREATE TABLE rel_players (
			player_id integer PRIMARY KEY AUTOINCREMENT,
			age integer,
			team_name varchar(20) REFERENCES rel_teams (name)
		);
		CREATE TABLE rel_profiles (
			fk_player_id integer NOT NULL PRIMARY KEY REFERENCES rel_players (player_id),
			salary integer
		);
		CREATE TABLE rel_skills (skill_id integer PRIMARY KEY AUTOINCREMENT, name varchar(20) NOT NULL);
		CREATE TABLE rel_player_skills (
			player_id integer NOT NULL REFERENCES rel_players (player_id),
			skill_id integer NOT NULL REFERENCES rel_skills (skill_id)
		);
		CREATE TABLE rel_loose (id integer PRIMARY KEY, team_name varchar(20));
		INSERT INTO rel_teams VALUES ('Team a', 'Sydney');
		INSERT INTO rel_teams VALUES ('Team b', 'Brisbane');
		INSERT INTO rel_teams VALUES ('Team c', 'Perth');
		INSERT INTO rel_players VALUES (1, 18, 'Team a');
		INSERT INTO rel_players VALUES (2, 20, 'Team a');
		INSERT INTO rel_players VALUES (3, 25, 'Team b');
		INSERT INTO rel_players VALUES (4, 30, NULL);
		INSERT INTO rel_profiles VALUES (1, 50000);
		INSERT INTO rel_skills VALUES (1, 'Bash');
		INSERT INTO rel_skills VALUES (2, 'Jump');
		INSERT INTO rel_player_skills VALUES (1, 1);
		INSERT INTO rel_player_skills VALUES (1, 2);
		INSERT INTO rel_player_skills VALUES (2, 2);
		SQL;

	protected static $conn;

	public static function resetDatabase()
	{
		self::$conn = new TDbConnection('sqlite::memory:');
		self::$conn->setActive(true);
		self::$conn->getPdoInstance()->exec(self::SCHEMA);
	}

	public static function connection()
	{
		return self::$conn;
	}

	public function getDbConnection()
	{
		return self::$conn;
	}
}

class RelTeam extends RelSqliteRecord
{
	public const TABLE = 'rel_teams';

	public static $RELATIONS = [
		'players' => [self::HAS_MANY, RelPlayer::class],
	];

	public $name;
	public $location;
}

class RelPlayer extends RelSqliteRecord
{
	public const TABLE = 'rel_players';

	public static $RELATIONS = [
		'team' => [self::BELONGS_TO, RelTeam::class],
		'profile' => [self::HAS_ONE, RelProfile::class],
		'skills' => [self::MANY_TO_MANY, RelSkill::class, 'rel_player_skills'],
		'teamMagic' => [self::BELONGS_TO, RelTeam::class],
		'profileMagic' => [self::HAS_ONE, RelProfile::class],
	];

	public $player_id;
	public $age;
	public $team_name;
	public $team;
	public $profile;
}

class RelProfile extends RelSqliteRecord
{
	public const TABLE = 'rel_profiles';

	public static $RELATIONS = [
		'player' => [self::BELONGS_TO, RelPlayer::class],
	];

	public $fk_player_id;
	public $salary;
}

class RelSkill extends RelSqliteRecord
{
	public const TABLE = 'rel_skills';

	public static $RELATIONS = [
		'players' => [self::MANY_TO_MANY, RelPlayer::class, 'rel_player_skills'],
	];

	public $skill_id;
	public $name;
}

/** rel_loose.team_name names a team, but the table declares no foreign key. */
class RelLoose extends RelSqliteRecord
{
	public const TABLE = 'rel_loose';

	public static $RELATIONS = [
		'team' => [self::BELONGS_TO, RelTeam::class],
	];

	public $id;
	public $team_name;
	public $team;
}

/**
 * Covers relation loading and the explicit relation save path
 * (TActiveRecordRelationContext::updateAssociatedRecords()) over SQLite.
 */
class RelationSqliteTest extends \PHPUnit\Framework\TestCase
{
	private $_manager;
	private int $_queries = 0;

	protected function setUp(): void
	{
		$this->_manager = TActiveRecordManager::getInstance();
		TActiveRecordManager::getInstance(new TActiveRecordManager());
		RelSqliteRecord::resetDatabase();
		$this->_queries = 0;
		TActiveRecordManager::getInstance()->getRecordGateway()->OnExecuteCommand[] = function () {
			$this->_queries++;
		};
	}

	protected function tearDown(): void
	{
		TActiveRecordManager::getInstance($this->_manager);
	}

	private function scalar(string $sql)
	{
		return RelSqliteRecord::connection()->createCommand($sql)->queryScalar();
	}

	public function testHasMany_undeclaredProperty_loadsOnceAndKeepsAssignment()
	{
		$team = RelTeam::finder()->findByPk('Team a');
		$this->_queries = 0;
		$players = $team->players;
		$this->assertCount(2, $players);
		$this->assertSame(1, $this->_queries);
		$this->assertSame($players, $team->players);
		$this->assertSame(1, $this->_queries, 'a second read uses the loaded players');

		$team->players = [];
		$this->assertSame([], $team->players);
		unset($team->players);
		$this->assertCount(2, $team->players);
		$this->assertSame(2, $this->_queries, 'after unset the next read loads again');
	}

	public function testHasMany_eagerLoadIsKeptForUndeclaredProperty()
	{
		$teams = RelTeam::finder()->withPlayers()->findAll();
		$this->assertCount(3, $teams);
		$this->_queries = 0;
		$counts = [];
		foreach ($teams as $team) {
			$this->assertTrue(isset($team->players));
			$counts[$team->name] = count($team->players);
		}
		$this->assertSame(['Team a' => 2, 'Team b' => 1, 'Team c' => 0], $counts);
		$this->assertSame(0, $this->_queries);
	}

	public function testHasOne_noMatch_isNullAndLoadedOnce()
	{
		$player = RelPlayer::finder()->findByPk(2);
		$this->_queries = 0;
		$this->assertNull($player->profileMagic);
		$this->assertNull($player->profileMagic);
		$this->assertSame(1, $this->_queries);

		$player = RelPlayer::finder()->findByPk(1);
		$this->assertSame(50000, (int) $player->profileMagic->salary);
	}

	public function testHasOne_eagerLoadSetsNullWhenNoMatch()
	{
		$players = RelPlayer::finder()->withProfile()->findAll();
		$salaries = [];
		foreach ($players as $player) {
			$salaries[$player->player_id] = $player->profile?->salary;
		}
		$this->assertEquals([1 => 50000, 2 => null, 3 => null, 4 => null], $salaries);
	}

	public function testHasOne_multipleMatchesThrow()
	{
		RelSqliteRecord::connection()->createCommand('CREATE TABLE rel_twice (id integer PRIMARY KEY, fk_player_id integer REFERENCES rel_players (player_id))')->execute();
		RelSqliteRecord::connection()->createCommand('INSERT INTO rel_twice VALUES (1, 1), (2, 1)')->execute();
		$context = new TActiveRecordRelationContext(RelPlayer::finder()->findByPk(1), 'profile', [TActiveRecord::HAS_ONE, RelTwice::class]);
		$this->expectException(TActiveRecordException::class);
		$context->getRelationHandler()->fetchResultsInto($context->getSourceRecord());
	}

	public function testBelongsTo_multipleMatchesThrow()
	{
		RelSqliteRecord::connection()->getPdoInstance()->exec(
			"PRAGMA foreign_keys = OFF;
			CREATE TABLE rel_cities (id integer PRIMARY KEY, team_name varchar(20));
			INSERT INTO rel_cities VALUES (1, 'Team a'), (2, 'Team a');
			CREATE TABLE rel_fans (id integer PRIMARY KEY, team_name varchar(20) REFERENCES rel_cities (team_name));
			INSERT INTO rel_fans VALUES (1, 'Team a');"
		);
		$context = new TActiveRecordRelationContext(RelFan::finder()->findByPk(1), 'city', [TActiveRecord::BELONGS_TO, RelCity::class]);
		$this->expectException(TActiveRecordException::class);
		$context->getRelationHandler()->fetchResultsInto($context->getSourceRecord());
	}

	public function testBelongsTo_nullForeignKey_isNullWithoutQuery()
	{
		$player = RelPlayer::finder()->findByPk(4);
		$this->_queries = 0;
		$this->assertNull($player->teamMagic);
		$this->assertSame(0, $this->_queries);

		$players = RelPlayer::finder()->withTeam()->findAllByPks(4);
		$this->assertCount(1, $players);
		$this->assertNull($players[0]->team);
	}

	public function testBelongsTo_loadsTheReferencedRecord()
	{
		$players = RelPlayer::finder()->withTeam()->findAll();
		$teams = [];
		foreach ($players as $player) {
			$teams[$player->player_id] = $player->team?->location;
		}
		$this->assertSame([1 => 'Sydney', 2 => 'Sydney', 3 => 'Brisbane', 4 => null], $teams);
	}

	public function testManyToMany_newRecordWithoutKey_hasNoSkillsAndNoQuery()
	{
		$player = new RelPlayer();
		$this->_queries = 0;
		$this->assertTrue(PradoUnit::invoke($player, 'fetchResultsFor', 'skills'));
		$this->assertSame([], $player->skills);
		$this->assertSame(0, $this->_queries);
	}

	public function testRelationCriteria_isNotChangedByUse()
	{
		$criteria = new TActiveRecordCriteria('age > :age', [':age' => 0]);
		$a = RelTeam::finder()->withPlayers($criteria)->findByPk('Team a');
		$b = RelTeam::finder()->withPlayers($criteria)->findByPk('Team b');
		$this->assertCount(2, $a->players);
		$this->assertCount(1, $b->players);
		$this->assertSame('age > :age', $criteria->getCondition());

		$handler = RelTeam::finder()->withPlayers($criteria);
		$this->assertCount(2, $handler->findByPk('Team a')->players);
		$this->assertCount(1, $handler->findByPk('Team b')->players);
	}

	public function testFind_doesNotChangeCallerCriteria()
	{
		$criteria = new TActiveRecordCriteria('age > ?', [0]);
		$this->assertNotNull(RelPlayer::finder()->find($criteria));
		$this->assertNull($criteria->getLimit());
		$this->assertCount(4, RelPlayer::finder()->findAll($criteria));
		$this->assertNotNull(RelPlayer::finder()->findBySql(new TActiveRecordCriteria('SELECT * FROM rel_players')));
	}

	public function testChainedRelations_loadEach()
	{
		$players = RelPlayer::finder()->withTeam()->withProfile()->withSkills()->findAll();
		$this->assertCount(4, $players);
		$this->assertSame('Team a', $players[0]->team->name);
		$this->assertSame(50000, (int) $players[0]->profile->salary);
		$this->assertSame(['Bash', 'Jump'], array_map(fn ($s) => $s->name, $players[0]->skills));
	}

	public function testChainedRelations_emptyResultDropsPendingRelations()
	{
		$this->assertSame([], RelPlayer::finder()->withTeam()->withProfile()->findAll('age > 100'));
		$players = RelPlayer::finder()->withProfile()->findAll();
		$this->assertNull($players[0]->team, 'the pending team relation is not applied to a later query');
		$this->assertNotNull($players[0]->profile);
	}

	public function testMissingForeignKey_throws()
	{
		$loose = new RelLoose(['id' => 1, 'team_name' => 'Team a']);
		$this->expectException(TActiveRecordException::class);
		$this->expectExceptionMessageMatches('/rel_loose/');
		PradoUnit::invoke($loose, 'fetchResultsFor', 'team');
	}

	public function testContext_relationForeignKeysAreCached()
	{
		$context = new TActiveRecordRelationContext(new RelPlayer(), 'team', RelPlayer::$RELATIONS['team']);
		$this->assertSame(['team_name' => 'name'], $context->getRelationForeignKeys());
		$this->assertSame(['team_name' => 'name'], $context->getRelationForeignKeys());
	}

	public function testManyToMany_sourceTableInfo()
	{
		$context = new TActiveRecordRelationContext(new RelPlayer(), 'skills', RelPlayer::$RELATIONS['skills']);
		$handler = $context->getRelationHandler();
		$table = PradoUnit::invoke($handler, 'getSourceTable');
		$this->assertSame('rel_players', $table->getTableName());
		$this->assertSame($table, PradoUnit::invoke($handler, 'getSourceTable'));
	}

	public function testUpdateAssociated_belongsTo_savesForeignRecordAndCopiesKey()
	{
		$player = new RelPlayer(['age' => 27]);
		$player->team = new RelTeam(['name' => 'Team d', 'location' => 'Darwin']);
		$context = new TActiveRecordRelationContext($player);
		$this->assertTrue($context->updateAssociatedRecords(true));
		$this->assertSame('Team d', $player->team_name);
		$this->assertSame('Darwin', $this->scalar("SELECT location FROM rel_teams WHERE name = 'Team d'"));

		$player->save();
		$this->assertSame('Team d', $this->scalar('SELECT team_name FROM rel_players WHERE age = 27'));
	}

	public function testUpdateAssociated_belongsToUndeclaredProperty()
	{
		$player = new RelPlayer(['age' => 28]);
		$player->teamMagic = new RelTeam(['name' => 'Team e', 'location' => 'Hobart']);
		$handler = (new TActiveRecordRelationContext($player, 'teamMagic', RelPlayer::$RELATIONS['teamMagic']))->getRelationHandler();
		$this->assertTrue($handler->updateAssociatedRecords());
		$this->assertSame('Team e', $player->team_name);

		$handler = (new TActiveRecordRelationContext(new RelPlayer(), 'team', RelPlayer::$RELATIONS['team']))->getRelationHandler();
		$this->assertFalse($handler->updateAssociatedRecords(), 'no team object, nothing to save');
	}

	public function testUpdateAssociated_hasMany_savesPlayersWithTeamKey()
	{
		$team = RelTeam::finder()->findByPk('Team c');
		$team->players = [new RelPlayer(['age' => 40]), new RelPlayer(['age' => 41])];
		$context = new TActiveRecordRelationContext($team);
		$this->assertTrue($context->updateAssociatedRecords());
		$this->assertSame(2, (int) $this->scalar("SELECT COUNT(*) FROM rel_players WHERE team_name = 'Team c'"));
		$this->assertNotNull($team->players[0]->player_id);

		$empty = RelTeam::finder()->findByPk('Team b');
		$empty->players = [];
		$this->assertTrue((new TActiveRecordRelationContext($empty, 'players', RelTeam::$RELATIONS['players']))->getRelationHandler()->updateAssociatedRecords());
	}

	public function testUpdateAssociated_hasOne_savesProfileWithPlayerKey()
	{
		$player = RelPlayer::finder()->findByPk(2);
		$player->profile = new RelProfile(['salary' => 45000]);
		$handler = (new TActiveRecordRelationContext($player, 'profile', RelPlayer::$RELATIONS['profile']))->getRelationHandler();
		$this->assertTrue($handler->updateAssociatedRecords());
		$this->assertSame(45000, (int) $this->scalar('SELECT salary FROM rel_profiles WHERE fk_player_id = 2'));
	}

	public function testUpdateAssociated_manyToMany_savesSkillsAndLinks()
	{
		$player = RelPlayer::finder()->findByPk(3);
		$player->skills = new TList([new RelSkill(['name' => 'Push']), RelSkill::finder()->findByPk(1)]);
		$context = new TActiveRecordRelationContext($player);
		$this->assertTrue($context->updateAssociatedRecords());
		$this->assertSame(['Bash', 'Push'], RelSqliteRecord::connection()->createCommand(
			'SELECT s.name FROM rel_skills s INNER JOIN rel_player_skills ps ON ps.skill_id = s.skill_id WHERE ps.player_id = 3 ORDER BY s.name'
		)->queryColumn());

		$this->assertTrue($context->updateAssociatedRecords(), 'existing links are not inserted twice');
		$this->assertSame(2, (int) $this->scalar('SELECT COUNT(*) FROM rel_player_skills WHERE player_id = 3'));

		$player->skills = [];
		$handler = (new TActiveRecordRelationContext($player, 'skills', RelPlayer::$RELATIONS['skills']))->getRelationHandler();
		$this->assertTrue($handler->updateAssociatedRecords());
	}
}

class RelTwice extends RelSqliteRecord
{
	public const TABLE = 'rel_twice';

	public $id;
	public $fk_player_id;
}

class RelCity extends RelSqliteRecord
{
	public const TABLE = 'rel_cities';

	public $id;
	public $team_name;
}

class RelFan extends RelSqliteRecord
{
	public const TABLE = 'rel_fans';

	public $id;
	public $team_name;
	public $city;
}
