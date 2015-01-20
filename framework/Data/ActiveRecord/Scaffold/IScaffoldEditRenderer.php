<?php
/**
 * TScaffoldEditView class and IScaffoldEditRenderer interface file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Data.ActiveRecord.Scaffold
 */

/**
 * IScaffoldEditRenderer interface.
 *
 * IScaffoldEditRenderer defines the interface that an edit renderer
 * needs to implement. Besides the {@link getData Data} property, an edit
 * renderer also needs to provide {@link updateRecord updateRecord} method
 * that is called before the save() method is called on the TActiveRecord.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package System.Data.ActiveRecord.Scaffold
 * @since 3.1
 */
interface IScaffoldEditRenderer extends IDataRenderer
{
	/**
	 * This method should update the record with the user input data.
	 * @param TActiveRecord record to be saved.
	 */
	public function updateRecord($record);
}