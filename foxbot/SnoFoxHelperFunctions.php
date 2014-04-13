<?php
/*
 * This file contains various helper functions
 * that I have rewritten and found useful in many programs
 */

/**
 * @function searchAndDestroy - Destructive
 * @param $search   - referenced array of things to search in
 * @param $destroy  - thing to remove from the array
 * @param $stop     - stop after $stop things have been deleted
 * @return mixed    - number of things deleted, FALSE if error
 */

function searchAndDestroy( &$search, $destroy, $stop = 1 ) {
    if( !is_array( $search ) ) {
        trigger_error( 'searchAndDestroy(): Search param is not iteratable' );
        return false;
    }

    $numDeleted = 0;
    foreach( $search as $key => $currSearch ) {
        if( $search[$key] === $currSearch ) {
            unset( $search[$key] );
            $numDeleted++;
            if( $numDeleted >= $stop ) {
                return $numDeleted;
            }
        }
    }
    return $numDeleted;
}

/**
 * @function randLetter
 * @param $lowerBound   - First letter to pick from
 * @param $upperBound   - Last letter to pick from
 * @return string       - one, random letter
 * @return false        - bad params
 */
function randLetter( $lowerBound = 'a', $upperBound = 'Z' ) {
    $lowerBound = ord($lowerBound);
    $upperBound = ord($upperBound);
     // lower: 97/122; CAPS: 65/90

    if( checkBounds( array( $lowerBound, $upperBound ), array( 97, 122 ), array( 65, 90 ) ) === false ) {
        trigger_error( 'randLetter(): Non-letter parameter given' );
        return false;
    }

    if( $upperBound < $lowerBound ) {
        $realUpperBound = $lowerBound;
        $lowerBound = $upperBound;
        $upperBound = $realUpperBound;
    }

    $strMap = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    $range[0] = strpos( $strMap, chr( $lowerBound ) );
    $range[1] = strpos( $strMap, chr( $upperBound ) );

    $strMap = substr( $strMap, $range[0], $range[1]+1 );

    $pick = rand( 0, strlen( $strMap )-1 );

    $pick = substr( $strMap, $pick, 1 );

    return $pick;

   /* //Doesn't work
    print "Debug: Picking b/w $lowerBound and $upperBound\n";

    $pick = rand( $lowerBound, $upperBound );

    print 'Debug: pick: ' . $pick . '(' . chr($pick) . ')' . "\n";
    if( !checkBounds( $pick, array(91, 96) ) ) {
        // Valid character
        return chr( $pick );
    } else {
        // Not valid; try again
        randLetter( chr( $lowerBound ), chr( $upperBound ) );
    }
    */
}

/** @function checkBounds
 * @param $check    - number we're checking (int or array of ints)
 * @param $bound    - overload with more of these -- bounds to check for (arrays lowerBound/upperBound)
 * @return bool     - does all $check's lie within all given bounds?
 * Note             - Bounds must be lower/upper bounds, not upper/lower
 */
function checkBounds() {
    $bounds = func_get_args();
    $tmpCheck = array_shift( $bounds );

    if( !is_array( $tmpCheck ) ) {
        $check[] = $tmpCheck;
    } else {
        $check = $tmpCheck;
    }

    foreach( $check as $currCheck ) {
        foreach( $bounds as $currBound ) {
            if( $currCheck >= $currBound[0]
                and $currCheck <= $currBound[1] ) {
                    return true;
                }
            }
        // If we're here, the foreach loop never returned, so it's false
        return false;
    }
}

