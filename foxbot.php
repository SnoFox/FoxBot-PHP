<?php
/* Example bot usage of the FoxBot PHP IRC Bot framework */

include 'foxbot/foxbot.php';
logit( 'Included the bot!' );
$nick = 'FawkzBawt';
$ident = 'Fawkz';
$bind = '192.168.1.100';
$gecos = 'FoxBot IRC Bot';

$ClueNet = Bot::start( $nick, $ident, $gecos, 'irc.cluenet.org', 6667, 0, $bind );
logit( 'ClueNet started!' );
$SleepyIRC = Bot::start( $nick, $ident, $gecos, 'irc.sleepyirc.net', 6667, 0, $bind );
logit( 'SleepyIRC started!' );
$bots = array( $ClueNet, $SleepyIRC );
Bot::mainLoop($bots);

die( 'Broke out of an infinite loop! My life is complete... Bye bye!' . "\n" );
