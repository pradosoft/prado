<?php
/**
 * TScaffoldEditView class and IScaffoldEditRenderer interface file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\ActiveRecord\Scaffold
 */

namespace Prado\Data\ActiveRecord\Scaffold;

/**
 * IScaffoldEditRenderer interface.
 *
 * IScaffoldEditRenderer defines the interface that an edit renderer
 * needs to implement. Besides the {@link getData Data} property, an edit
 * renderer also needs to provide {@link updateRecord updateRecord} method
 * that is called before the save() method is called on the TActiveRecord.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\ActiveRecord\Scaffold
 * @since 3.1
 */
interface IScaffoldEditRenderer extends \Prado\IDataRenderer
{
	/**
	 * This method should update the record with the user input data.
	 * @param TActiveRecord $record record to be saved.
	 */
	public function updateRecord($record);
}
