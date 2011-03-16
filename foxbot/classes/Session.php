<?php
/* This file is part of the FoxBot PHP IRC Bot Framework.
 * See LICENSE for more details
 * ----------------------------
 * This file handles storing current IRC status
 */

class Session {
    protected $nick; // Bot's current nick
    protected $ident; // Bot's current ident
    protected $host; // Bot's current host
    protected $isupport; // Array of features from the 005 numeric and their params

    // Optional, but we'll still init the vars
    protected $channel; // Array of channel objects
    protected $user; // Array of user objects

    function __construct( $nick ) {
        $this->nick = $nick;
        $this->channel = array();
        $this->user = array();
        $this->isupport = array();
    }

    
/* // Array of mode types for future reference...
    return array(
        'list'          => 'beI',
        'paramset'      => 'fljL',
        'param'         => 'k',
        'flag'          => 'psmntirRcOAQKVCuzNSMTGy',
        'prefix'        => 'qaohv'
    );
 */
}
?>
