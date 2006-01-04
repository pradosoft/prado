<?php
/**
 * File containing the ezcLogMap class.
 *
 * @package EventLog
 * @version //autogentag//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */

/**
 * Fast mapping of a mixed variable to an eventType, eventSource and eventCategory.
 *
 * This class is used in ezcLog to map writers to the log messages they
 * are supposed to write. It is important that the get() method is extremely
 * fast since it will be called for each log message.
 *
 * If every combination of eventTypes, eventSources, and eventCategories are
 * combined (product) and placed in one hash-table. The table grows too fast.
 * Imagine: 7 types * 10 sources * 10 categories = 700 entries in the hash.
 *
 * Our solution:
 * Most probably the amount of writers will be limited. So every writer
 * (result) is one bit (of an integer).
 *
 * The hashes: Type, Source, and Category map the input strings to an integer.
 * These integers are AND with each other, and the result is again stored in a
 * hash.
 *
 *
 * @package EventLog
 * @version //autogentag//
 * @access private
 */
class ezcLogMap
{
    /**
     * Hashtable that binds a single eventType to a bitmask. The possible
     * key-values are: 1, 2, 4, 8, ...
     *
     * @var array(integer=>bitmask)
     */
    protected $structure;


    public function __construct()
    {
        $this->result = array();
    }

    /**
     * Map the object $object to the given $eventTypeMask, $eventSources and $eventCategories.
     *
     * Example:
     * <code>
     * [ eventType x eventSource x eventCategory ] |-> [ object ]
     * </code>
     *
     * @param int $eventTypeMask
     * @param array(string) $eventSources
     * @param array(string) $eventCategories
     * @param object $object
     *
     * @return void
     */
    public function map( $eventTypeMask, $eventSources, $eventCategories, $object )
    {
        $eventTypes = $this->getMaskArray( $eventTypeMask );
        $result = array( $object );

        if ( count( $eventTypes ) == 0 )
        {
            $eventTypes = array( "*" );
        }
        if ( count( $eventSources ) == 0 )
        {
            $eventSources = array( "*" );
        }
        if ( count( $eventCategories ) == 0)
        {
            $eventCategories = array( "*" );
        }
        $c = $this->createMinimumHash( $eventCategories, $result );
        $s = $this->createMinimumHash( $eventSources, $c );
        $new = $this->createMinimumHash( $eventTypes, $s );

        $this->mergeHash( $new, $this->structure, $history );
    }

    /**
     * Unmap the object $object to the given $eventTypeMask, $eventSources and $eventCategories.
     *
     * @param int $eventTypeMask
     * @param array(string) $eventSources
     * @param array(string) $eventCategories
     * @param object $object
     *
     * @return void
     */
    public function unmap( $eventTypeMask, $eventSources, $eventCategories, $object)
    {
        $eventTypes = $this->getMaskArray( $eventTypeMask );

        $result = array( $object );

        if ( count( $eventTypes ) == 0 )
        {
            $eventTypes = array( "*" );
        }
        if ( count( $eventSources ) == 0 )
        {
            $eventSources = array( "*" );
        }
        if ( count( $eventCategories ) == 0)
        {
            $eventCategories = array( "*" );
        }

        $c = $this->createMinimumHash( $eventCategories, $result );
        $s = $this->createMinimumHash( $eventSources, $c );
        $new = $this->createMinimumHash( $eventTypes, $s );

        $this->unmergeHash( $this->structure, $new, $history );
    }


    /**
     * Fills the array $out with the keys $inKeys and the value $inValue.
     *
     * @param array inKeys
     * @param mixed $inValue
     * @param array &$out
     * @return array
     */
    protected function createMinimumHash($inKeys, $inValue )
    {
        foreach ( $inKeys as $key )
        {
            $out[$key] = $inValue;
        }
        return $out;
    }

    /**
     *
     *
     * @return void
     **/
    protected function mergeSingle( &$in, &$history )
    {
        $extra = $this->get( $history[0], $history[1], $history[2] );
        $extra = array_merge( $in, $extra );

        $tmp =& $this->structure[$history[0]];
        $tmp =& $tmp[$history[1]];
        $tmp =& $tmp[$history[2]];

        foreach ( $extra as $inKey => $inVal )
        {
            if ( !isset( $tmp ) || !in_array( $inVal, $tmp ) )
            {
                if ( is_numeric( $inKey ) )
                {
                    $tmp[] =  $inVal;
                }
                else
                {
                    $tmp[$inKey] =& $inVal;
                }
            }
         }
     }


     // XXX should change out.. the way it is now.
    protected function mergeHash( &$in, $out, &$history )
    {
        $depth = count( $history );

        if ( $depth == 3 )
        {
            $this->mergeSingle( $in, $history );
        }

        $i = 0;
        foreach ( $in as $inKey => $inVal )
        {
            $history[$depth] = $inKey;

            if ( strcmp( $inKey, "*" ) == 0)
            {
                if ( is_array( $out ) )
                {
                    foreach ( $out as $outKey => $outVal )
                    {
                        $history[$depth] = $outKey;
                        $this->mergeHash( $in[$inKey], $out[$outKey], $history );
                    }
                }
            }

            $history[$depth] = $inKey;
            if ( is_array( $inVal ) )
            {
                if ( is_numeric( $inKey ) )
                {
                    // FIXME
                    $this->mergeHash( $in[$inKey], $out, $history );
                }
                else
                {
                    //$this->mergeHash( $in[$inKey], $out[$inKey], $history );
                    $this->mergeHash( $in[$inKey], $out, $history );
                }
            }

        }
        array_pop( $history );
    }


    // XXX: ugly, should get a rewrite.
    protected function unmergeHash( &$out, &$in, &$history )
    {
        $depth = count( $history );

        if ( $depth == 3 )
        {
            foreach ( $in as $inKey => $inVal )
            {
                if ( !isset( $out ) || in_array( $inVal, $out ) )
                {
                    if ( is_array( $out ) )
                    {
                        foreach ( $out as $outKey => $outVal )
                        {
                            if ( $outVal === $inVal )
                            {
                                unset( $out[$outKey] );
                                $result = true;
                            }
                        }
                    }
                }
            }
        }

        foreach ( $in as $inKey => $inVal )
        {
            $history[$depth] = $inKey;

            if ( strcmp( $inKey, "*" ) == 0)
            {
                if ( is_array( $out ) )
                {
                    foreach ( $out as $outKey => $outVal )
                    {
                        $this->unmergeHash( $out[$outKey], $in[$inKey], $history );
                    }
                }
            }

            if ( is_array( $inVal ) )
            {
                $this->unmergeHash( $out[$inKey], $in[$inKey], $history );
            }
        }
        array_pop( $history );
    }


    /**
     * Returns the bits set in $mask as separate values in an array.
     *
     * @return array(int)
     */
    protected function getMaskArray( $mask )
    {
        $result = array();

        $input = 1;
        while ( $input <= $mask )
        {
            if ( $mask & $input )
            {
                $result[] = $input;
            }
            $input <<= 1;
        }

        return $result;
    }

    /**
     * Returns an array with objects are mapped to the key generated by $eventType, $eventSource and $eventCategory.
     *
     * The eventType is an integer from in the range: 2^x with x -> N+.
     * (1, 2, 4, 8, 16, ... )
     *
     * @param int $eventType
     * @param string  $eventSource
     * @param string  $eventCategory
     * @return array(object).
     */
    public function get( $eventType, $eventSource, $eventCategory)
    {
        $tmp = $this->structure;


        if ( isset( $tmp[$eventType] ) )
        {
            $tmp = $tmp[$eventType];
        }
        else if ( isset( $tmp [ "*" ] ) )
        {
            $tmp = $tmp["*"];
        }
        else
        {
            return array();
        }

        if ( isset( $tmp[$eventSource] ) )
        {
            $tmp = $tmp[$eventSource];
        }
        else if ( isset( $tmp [ "*" ] ) )
        {
            $tmp = $tmp["*"];
        }
        else
        {
            return array();
        }

        if ( isset( $tmp[$eventCategory] ) )
        {
            $tmp = $tmp[$eventCategory];
        }
        else if ( isset( $tmp [ "*" ] ) )
        {
            $tmp = $tmp["*"];
        }
        else
        {
            return array();
        }

        return $tmp;
    }


    public function printStructure()
    {
        $this->printStructureRecursive( $this->structure, $history = array() );
    }

    protected function printStructureRecursive( $structure, &$history )
    {
        $depth = count( $history );
        if ( $depth == 3 )
        {
            $tmp =& $this->structure[$history[0]];
            $tmp =& $tmp[$history[1]];
            $tmp =& $tmp[$history[2]];

            if ( isset( $tmp ) )
            {
                foreach ( $tmp as $key => $val )
                {
                    echo $history[0] . "  " . $history[1] . "  " . $history[2] . " [ $key => $val ]\n";
                }
            }

            return;
        }

        foreach ( $structure as $key => $val )
        {
            $history[$depth] = $key;
            $this->printStructureRecursive( $structure, $history );
        }
    }
}
?>
