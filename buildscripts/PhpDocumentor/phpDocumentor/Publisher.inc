<?php
//
// +------------------------------------------------------------------------+
// | phpDocumentor                                                          |
// +------------------------------------------------------------------------+
// | Publisher Copyright 2000-2003 Kellin                                   |
// | Email passionplay@hotmail.com                                          |
// | phpDocumentor Copyright (c) 2000-2003 Joshua Eichorn, Gregory Beaver   |
// | Email         jeichorn@phpdoc.org, cellog@phpdoc.org                   |
// | Web           http://www.phpdoc.org                                    |
// | Mirror        http://phpdocu.sourceforge.net/                          |
// | PEAR          http://pear.php.net/package-info.php?pacid=137           |
// +------------------------------------------------------------------------+
// | This source file is subject to version 3.00 of the PHP License,        |
// | that is available at http://www.php.net/license/3_0.txt.               |
// | If you did not receive a copy of the PHP license and are unable to     |
// | obtain it through the world-wide-web, please send a note to            |
// | license@php.net so we can mail you a copy immediately.                 |
// +------------------------------------------------------------------------+
//

/**
 * a class for handling the publishing of data
 *
 * @author Kellin <passionplay@hotmail.com>
 * @author Joshua Eichorn <jeichorn@phpdoc.org>
 * @version	$Id: Publisher.inc,v 1.1 2005/10/17 18:36:57 jeichorn Exp $
 * @package 	phpDocumentor
 */
/**
 * a class for handling the publishing of data
 *
 * @author Kellin <passionplay@hotmail.com>
 * @author Joshua Eichorn <jeichorn@phpdoc.org>
 * @version	$Id: Publisher.inc,v 1.1 2005/10/17 18:36:57 jeichorn Exp $
 * @package 	phpDocumentor
 */
class Publisher
{
    /**#@+
     * @var array
     */
	/**
     * Array of references objects that have Subscribed to this publisher
     */
	var $subscriber	=	array();

	var $tokens	=	array();

	var $pushEvent	=	array();
	var $popEvent	=	array();
    /**#@-*/


	/**
     * Adds a subscriber to the {@link $subscriber} array().
     * if $event is '*', the publisher will use $object as the default event handler
     * @param integer $event see {@link Parser.inc} PARSER_EVENT_* constants
     * @param class $object any class that has a HandleEvent() method like {@link phpDocumentor_IntermediateParser::HandleEvent()} or {@link Classes::HandleEvent()}
     */
	function subscribe($event, &$object)
	{
		$this->subscriber[$event] =& $object;
	}

	/**
     * @param integer $event see {@link Parser.inc} PARSER_EVENT_* constants
     * @param mixed $data anything the subscribed event handler is expecting
     */
	function publishEvent($event,$data)
	{
		
		// see if there is a specific event handler
		if (!empty($this->subscriber[$event]))
		{
			$this->subscriber[$event]->HandleEvent($event,$data);
		} 
		 else if (isset($this->subscriber['*']) && is_object($this->subscriber['*'])) // check to see if a generic handler exists
		{
			$this->subscriber['*']->HandleEvent($event,$data);
		}
	}	
}
?>
