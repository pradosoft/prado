<?php
/**
 * TActiveRecordHasMany class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord.Relations
 */

/**
 * Loads base active record relations class.
 */
Prado::using('System.Data.ActiveRecord.Relations.TActiveRecordRelation');

/**
 * Implements TActiveRecord::HAS_MANY relationship between the source object having zero or
 * more foreign objects. Consider the <b>entity</b> relationship between a Team and a Player.
 * <code>
 * +------+            +--------+
 * | Team | 1 <----- * | Player |
 * +------+            +--------+
 * </code>
 * Where one team may have 0 or more players and each player belongs to only
 * one team. We may model Team-Player <b>object</b> relationship as active record as follows.
 * <code>
 * class TeamRecord extends TActiveRecord
 * {
 *     const TABLE='team';
 *     public $name; //primary key
 *     public $location;
 *
 *     public $players=array(); //list of players
 *
 *     protected static $RELATIONS=array(
 *         'players' => array(self::HAS_MANY, 'PlayerRecord'));
 *
 *	   public static function finder($className=__CLASS__)
 *	   {
 *		   return parent::finder($className);
 *	   }
 * }
 * class PlayerRecord extends TActiveRecord
 * {
 *     // see TActiveRecordBelongsTo for detailed definition
 * }
 * </code>
 * The <tt>$RELATIONS</tt> static property of TeamRecord defines that the
 * property <tt>$players</tt> has many <tt>PlayerRecord</tt>s.
 *
 * The players list may be fetched as follows.
 * <code>
 * $team = TeamRecord::finder()->with_players()->findAll();
 * </code>
 * The method <tt>with_xxx()</tt> (where <tt>xxx</tt> is the relationship property
 * name, in this case, <tt>players</tt>) fetchs the corresponding PlayerRecords using
 * a second query (not by using a join). The <tt>with_xxx()</tt> accepts the same
 * arguments as other finder methods of TActiveRecord, e.g. <tt>with_player('age < ?', 35)</tt>.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Relations
 * @since 3.1
 */
class TActiveRecordHasMany extends TActiveRecordRelation
{
	/**
	 * Get the foreign key index values from the results and make calls to the
	 * database to find the corresponding foreign objects.
	 * @param array original results.
	 */
	protected function collectForeignObjects(&$results)
	{
		$fkObject = $this->getContext()->getForeignRecordFinder();
		$fkeys = $this->findForeignKeys($fkObject, $this->getSourceRecord());

		$properties = array_values($fkeys);
		$fields = array_keys($fkeys);

		$indexValues = $this->getIndexValues($properties, $results);
		$fkObjects = $this->findForeignObjects($fields,$indexValues);
		$this->populateResult($results,$properties,$fkObjects,$fields);
	}
}

?>