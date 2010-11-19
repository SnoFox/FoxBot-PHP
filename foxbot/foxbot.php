<?php
/* This file is part of the FoxBot PHP IRC Bot Framework.
 * See LICENSE for more details
 * ----------------------------
 * This file simply includes the rest of the bot framework for simplicity reasons...
 */

function logit( $message, $type = NULL ) {
    print $message . "\n";
}

include 'foxbot/classes/IRC.php';
include 'foxbot/classes/Bot.php';
