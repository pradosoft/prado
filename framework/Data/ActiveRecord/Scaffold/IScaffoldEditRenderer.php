<?php

/**
 * TScaffoldEditView class and IScaffoldEditRenderer interface file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\ActiveRecord\Scaffold;

/**
 * IScaffoldEditRenderer interface.
 *
 * IScaffoldEditRenderer defines the interface that an edit renderer
 * needs to implement. Besides the {@see getData Data} property, an edit
 * renderer also needs to provide {@see updateRecord updateRecord} method
 * that is called before the save() method is called on the TActiveRecord.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
interface IScaffoldEditRenderer extends \Prado\IDataRenderer
{
	/**
	 * This method should update the record with the user input data.
	 * @param \Prado\Data\ActiveRecord\TActiveRecord $record record to be saved.
	 */
	public function updateRecord($record);
}
