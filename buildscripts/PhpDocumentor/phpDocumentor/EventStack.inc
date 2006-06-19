<?php
//
// +------------------------------------------------------------------------+
// | phpDocumentor                                                          |
// +------------------------------------------------------------------------+
// | Copyright (c) 2000-2003 Joshua Eichorn, Gregory Beaver                 |
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
 * An event Stack
 * 
 * @author    Joshua Eichorn <jeichorn@phpdoc.org>
 * @version    $Id: EventStack.inc,v 1.1 2005/10/17 18:36:55 jeichorn Exp $
 * @package     phpDocumentor
 */
/**
 * An event Stack
 * 
 * @author    Joshua Eichorn <jeichorn@phpdoc.org>
 * @version    $Id: EventStack.inc,v 1.1 2005/10/17 18:36:55 jeichorn Exp $
 * @package     phpDocumentor
 */
class EventStack
{
    /**
     * The stack
     * @var array
     */
    var $stack = array(PARSER_EVENT_NOEVENTS);

    /**
     * The number of events in the stack
     * @var integer
     */
    var $num = 0;

    /**
     * Push an event onto the stack
     *
     * @param    int    $event    All events must be constants
     */
    function pushEvent($event)
    {
        $this->num = array_push($this->stack,$event) - 1;
    }

    /**
     * Pop an event from the stack
     *
     * @return    int    An event
     */
    function popEvent()
    {
        $this->num--;
        return array_pop($this->stack);
    }

    /**
     * Get the current event
     *
     * @return    int    An event
     */
    function getEvent()
    {
        return $this->stack[$this->num];
    }
}
