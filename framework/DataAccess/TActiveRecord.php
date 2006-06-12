<?php

Prado::using('System.3rdParty.adodb.ADOdb_Active_Record');

/**
 * Adodb's active record implementation is very basic. Example: A table
 * named "persons"
 * <code>
 * CREATE TABLE persons 
 * ( 
 *    id               INTEGER PRIMARY KEY,   
 *    first_name       TEXT NOT NULL,   
 *    last_name        TEXT NOT NULL, 
 *    favorite_color   TEXT NOT NULL 
 * );
 * </code>
 * Create a class called <tt>Person</tt>, connect insert some data as follows.
 * <code>
 * class Person extends TActiveRecord { }
 *
 * $person = new Person(); 
 * $person->first_name     = 'Andi';
 * $person->last_name      = 'Gutmans';
 * $person->favorite_color = 'blue';
 * $person->save(); // this save will perform an INSERT successfully
 *
 * $person = new Person();
 * $person->first_name     = 'John';
 * $person->last_name      = 'Lim';
 * $person->favorite_color = 'lavender';
 * $person->save(); // this save will perform an INSERT successfully
 *
 * // load record where id=2 into a new ADOdb_Active_Record
 * $person2 = new Person();
 * $person2->Load('id=2');
 * var_dump($person2);
 * </code>
 *
 * 
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.DataAccess
 * @since 3.0
 */
class TActiveRecord extends ADOdb_Active_Record
{

}

?>