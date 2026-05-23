<?php

require_once(__DIR__ . '/../../PradoUnit.php');

use Prado\Collections\TList;
use Prado\Collections\TMap;
use Prado\Data\SqlMap\Configuration\TParameterProperty;
use Prado\Data\SqlMap\Configuration\TResultProperty;
use Prado\Data\SqlMap\Configuration\TSqlMapStatement;
use Prado\Data\SqlMap\Statements\TMappedStatement;
use Prado\Data\SqlMap\Statements\TPreparedStatement;
use Prado\Data\SqlMap\Statements\TSqlMapObjectCollectionTree;

/**
 * Sleep / wakeup tests for SqlMap classes that implement _getZappableSleepProps.
 *
 * Pattern: call __sleep() and assert that properties with default/empty values are
 * absent from the returned array (excluded from serialization), while properties that
 * carry meaningful data are present (serialized).
 *
 * Private property mangled names follow the format "\0FQCN\0_propName".
 */
class SqlMapSleepTest extends PHPUnit\Framework\TestCase
{
	// =========================================================================
	//  TSqlMapStatement
	// =========================================================================

	private const STMT_CN  = 'Prado\Data\SqlMap\Configuration\TSqlMapStatement';

	public function testTSqlMapStatementResultMapAlwaysExcluded(): void
	{
		$s = new TSqlMapStatement();
		$props = $s->__sleep();
		// _resultMap (the resolved object) is always stripped regardless of its value
		$this->assertNotContains("\0" . self::STMT_CN . "\0_resultMap", $props);
	}

	public function testTSqlMapStatementDefaultPropsExcluded(): void
	{
		$s = new TSqlMapStatement();
		$cn = self::STMT_CN;
		$props = $s->__sleep();
		$this->assertNotContains("\0$cn\0_parameterMapName",   $props);
		$this->assertNotContains("\0$cn\0_parameterMap",       $props);
		$this->assertNotContains("\0$cn\0_parameterClassName", $props);
		$this->assertNotContains("\0$cn\0_resultMapName",      $props);
		$this->assertNotContains("\0$cn\0_resultClassName",    $props);
		$this->assertNotContains("\0$cn\0_cacheModelName",     $props);
		$this->assertNotContains("\0$cn\0_SQL",                $props);
		$this->assertNotContains("\0$cn\0_listClass",          $props);
		$this->assertNotContains("\0$cn\0_typeHandler",        $props);
		$this->assertNotContains("\0$cn\0_extendStatement",    $props);
		$this->assertNotContains("\0$cn\0_cache",              $props);
	}

	public function testTSqlMapStatementSetPropsIncluded(): void
	{
		$s = new TSqlMapStatement();
		// setParameterMap sets _parameterMapName; setResultMap sets _resultMapName
		$s->setParameterMap('paramMap');
		$s->setParameterClass('stdClass');
		$s->setResultMap('resultMap');
		$s->setResultClass('stdClass');
		$s->setCacheModel('myCache');
		$s->setSqlText('SELECT 1');
		$s->setListClass('TList');
		$s->setExtends('baseStmt');

		$cn = self::STMT_CN;
		$props = $s->__sleep();
		$this->assertContains("\0$cn\0_parameterMapName",   $props);
		$this->assertContains("\0$cn\0_parameterClassName", $props);
		$this->assertContains("\0$cn\0_resultMapName",      $props);
		$this->assertContains("\0$cn\0_resultClassName",    $props);
		$this->assertContains("\0$cn\0_cacheModelName",     $props);
		$this->assertContains("\0$cn\0_SQL",                $props);
		$this->assertContains("\0$cn\0_listClass",          $props);
		$this->assertContains("\0$cn\0_extendStatement",    $props);
	}

	public function testTSqlMapStatementRoundTrip(): void
	{
		$s = new TSqlMapStatement();
		$s->setID('selectUser');
		$s->setParameterClass('User');
		$s->setResultClass('User');
		$s->setSqlText('SELECT * FROM users WHERE id = ?');

		$restored = unserialize(serialize($s));
		$this->assertSame('selectUser',                    $restored->getID());
		$this->assertSame('User',                          $restored->getParameterClass());
		$this->assertSame('User',                          $restored->getResultClass());
		$this->assertSame('SELECT * FROM users WHERE id = ?', $restored->getSqlText());
		$this->assertNull($restored->getResultMap()); // _resultMap always stripped
	}

	// =========================================================================
	//  TParameterProperty
	// =========================================================================

	private const PARAM_CN = 'Prado\Data\SqlMap\Configuration\TParameterProperty';

	public function testTParameterPropertyDefaultPropsExcluded(): void
	{
		$p = new TParameterProperty();
		$cn = self::PARAM_CN;
		$props = $p->__sleep();
		$this->assertNotContains("\0$cn\0_typeHandler", $props);
		$this->assertNotContains("\0$cn\0_type",        $props);
		$this->assertNotContains("\0$cn\0_column",      $props);
		$this->assertNotContains("\0$cn\0_dbType",      $props);
		$this->assertNotContains("\0$cn\0_property",    $props);
		$this->assertNotContains("\0$cn\0_nullValue",   $props);
	}

	public function testTParameterPropertySetPropsIncluded(): void
	{
		$p = new TParameterProperty();
		$p->setProperty('username');
		$p->setColumn('user_name');
		$p->setType('string');
		$p->setDbType('VARCHAR');
		$p->setNullValue('');

		$cn = self::PARAM_CN;
		$props = $p->__sleep();
		$this->assertContains("\0$cn\0_property",  $props);
		$this->assertContains("\0$cn\0_column",    $props);
		$this->assertContains("\0$cn\0_type",      $props);
		$this->assertContains("\0$cn\0_dbType",    $props);
		$this->assertContains("\0$cn\0_nullValue", $props);
	}

	public function testTParameterPropertyRoundTrip(): void
	{
		$p = new TParameterProperty();
		$p->setProperty('email');
		$p->setColumn('email_address');
		$p->setNullValue('none@example.com');

		$restored = unserialize(serialize($p));
		$this->assertSame('email',            $restored->getProperty());
		$this->assertSame('email_address',    $restored->getColumn());
		$this->assertSame('none@example.com', $restored->getNullValue());
		$this->assertNull($restored->getType());
	}

	// =========================================================================
	//  TResultProperty
	// =========================================================================

	private const RESULT_PROP_CN = 'Prado\Data\SqlMap\Configuration\TResultProperty';

	public function testTResultPropertyDefaultPropsExcluded(): void
	{
		$r = new TResultProperty();
		$cn = self::RESULT_PROP_CN;
		$props = $r->__sleep();
		$this->assertNotContains("\0$cn\0_nullValue",           $props);
		$this->assertNotContains("\0$cn\0_propertyName",        $props);
		$this->assertNotContains("\0$cn\0_columnName",          $props);
		$this->assertNotContains("\0$cn\0_columnIndex",         $props); // default -1 → excluded
		$this->assertNotContains("\0$cn\0_nestedResultMapName", $props);
		$this->assertNotContains("\0$cn\0_nestedResultMap",     $props);
		$this->assertNotContains("\0$cn\0_valueType",           $props);
		$this->assertNotContains("\0$cn\0_typeHandler",         $props);
		$this->assertNotContains("\0$cn\0_isLazyLoad",          $props); // default false → excluded
		$this->assertNotContains("\0$cn\0_select",              $props);
	}

	public function testTResultPropertySetPropsIncluded(): void
	{
		$r = new TResultProperty();
		$r->setProperty('id');
		$r->setColumn('user_id');
		$r->setColumnIndex(0);           // non-default: != -1
		$r->setType('integer');          // sets _valueType
		$r->setResultMapping('userMap'); // sets _nestedResultMapName
		$r->setSelect('selectAddress');
		$r->setLazyLoad(true);           // non-default: true

		$cn = self::RESULT_PROP_CN;
		$props = $r->__sleep();
		$this->assertContains("\0$cn\0_propertyName",        $props);
		$this->assertContains("\0$cn\0_columnName",          $props);
		$this->assertContains("\0$cn\0_columnIndex",         $props);
		$this->assertContains("\0$cn\0_valueType",           $props);
		$this->assertContains("\0$cn\0_nestedResultMapName", $props);
		$this->assertContains("\0$cn\0_select",              $props);
		$this->assertContains("\0$cn\0_isLazyLoad",          $props);
	}

	public function testTResultPropertyRoundTrip(): void
	{
		$r = new TResultProperty();
		$r->setProperty('name');
		$r->setColumn('full_name');
		$r->setColumnIndex(2);
		$r->setNullValue('N/A');

		$restored = unserialize(serialize($r));
		$this->assertSame('name',      $restored->getProperty());
		$this->assertSame('full_name', $restored->getColumn());
		$this->assertSame(2,           $restored->getColumnIndex());
		$this->assertSame('N/A',       $restored->getNullValue());
		$this->assertNull($restored->getType());
	}

	// =========================================================================
	//  TPreparedStatement
	// =========================================================================

	private const PREP_CN = 'Prado\Data\SqlMap\Statements\TPreparedStatement';

	public function testTPreparedStatementDefaultPropsExcluded(): void
	{
		$p = new TPreparedStatement();
		$cn = self::PREP_CN;
		$props = $p->__sleep();
		// Empty TList/TMap → excluded
		$this->assertNotContains("\0$cn\0_parameterNames",  $props);
		$this->assertNotContains("\0$cn\0_parameterValues", $props);
	}

	public function testTPreparedStatementSetPropsIncluded(): void
	{
		$p = new TPreparedStatement();
		$names = new TList();
		$names->add(':id');
		$p->setParameterNames($names);

		$values = new TMap();
		$values->add(':id', 42);
		$p->setParameterValues($values);

		$cn = self::PREP_CN;
		$props = $p->__sleep();
		$this->assertContains("\0$cn\0_parameterNames",  $props);
		$this->assertContains("\0$cn\0_parameterValues", $props);
	}

	public function testTPreparedStatementRoundTrip(): void
	{
		$p = new TPreparedStatement();
		$p->setPreparedSql('SELECT * FROM users WHERE id = :id');
		$names = new TList();
		$names->add(':id');
		$p->setParameterNames($names);

		$restored = unserialize(serialize($p));
		$this->assertSame('SELECT * FROM users WHERE id = :id', $restored->getPreparedSql());
		$this->assertSame(1,    $restored->getParameterNames()->getCount());
		$this->assertSame(':id',$restored->getParameterNames()->itemAt(0));
	}

	// =========================================================================
	//  TMappedStatement
	// =========================================================================

	private const MAPPED_CN = 'Prado\Data\SqlMap\Statements\TMappedStatement';

	/**
	 * Create a TMappedStatement without calling the constructor, so that tests
	 * can inspect _getZappableSleepProps without needing a live TSqlMapManager.
	 */
	private function makeMappedStatement(): TMappedStatement
	{
		return (new \ReflectionClass(TMappedStatement::class))->newInstanceWithoutConstructor();
	}

	public function testTMappedStatementDefaultPropsExcluded(): void
	{
		$m = $this->makeMappedStatement();
		$cn = self::MAPPED_CN;
		$props = $m->__sleep();
		// _selectQueue=[], _groupBy=null, _IsRowDataFound=false are all excluded by default
		$this->assertNotContains("\0$cn\0_selectQueue",    $props);
		$this->assertNotContains("\0$cn\0_groupBy",        $props);
		$this->assertNotContains("\0$cn\0_IsRowDataFound", $props);
	}

	public function testTMappedStatementSetPropsIncluded(): void
	{
		$m = $this->makeMappedStatement();
		$ref = new \ReflectionClass($m);

		$ref->getProperty('_selectQueue')->setValue($m, [['key' => 'val']]);
		$ref->getProperty('_groupBy')->setValue($m, new \stdClass());
		$ref->getProperty('_IsRowDataFound')->setValue($m, true);

		$cn = self::MAPPED_CN;
		$props = $m->__sleep();
		$this->assertContains("\0$cn\0_selectQueue",    $props);
		$this->assertContains("\0$cn\0_groupBy",        $props);
		$this->assertContains("\0$cn\0_IsRowDataFound", $props);
	}

	// =========================================================================
	//  TSqlMapObjectCollectionTree
	// =========================================================================

	private const TREE_CN = 'Prado\Data\SqlMap\Statements\TSqlMapObjectCollectionTree';

	public function testTSqlMapObjectCollectionTreeDefaultPropsExcluded(): void
	{
		$t = new TSqlMapObjectCollectionTree();
		$cn = self::TREE_CN;
		$props = $t->__sleep();
		$this->assertNotContains("\0$cn\0_tree",    $props);
		$this->assertNotContains("\0$cn\0_entries", $props);
		$this->assertNotContains("\0$cn\0_list",    $props);
	}

	public function testTSqlMapObjectCollectionTreeSetPropsIncluded(): void
	{
		$t = new TSqlMapObjectCollectionTree();
		$ref = new \ReflectionClass($t);
		foreach (['_tree', '_entries', '_list'] as $propName) {
			$ref->getProperty($propName)->setValue($t, ['item' => new \stdClass()]);
		}

		$cn = self::TREE_CN;
		$props = $t->__sleep();
		$this->assertContains("\0$cn\0_tree",    $props);
		$this->assertContains("\0$cn\0_entries", $props);
		$this->assertContains("\0$cn\0_list",    $props);
	}

	public function testTSqlMapObjectCollectionTreeRoundTrip(): void
	{
		$t = new TSqlMapObjectCollectionTree();
		$ref = new \ReflectionClass($t);
		$listProp = $ref->getProperty('_list');
		$listProp->setValue($t, ['row1' => new \stdClass()]);

		$restored = unserialize(serialize($t));
		$resListProp = (new \ReflectionClass($restored))->getProperty('_list');
		$this->assertCount(1, $resListProp->getValue($restored));
	}
}
