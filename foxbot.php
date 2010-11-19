<?php
/* Example bot usage of the FoxBot PHP IRC Bot framework */

include 'foxbot/foxbot.php';

$nick = 'FawkzBawt';
$ident = 'Fawkz';
$bind = '192.168.1.100';
$gecos = 'FoxBot IRC Bot';

$ClueNet = new Bot( $nick, $ident, $gecos, 'irc.cluenet.org', 6667, FALSE, $bind );
$SleepyIRC = new Bot( $nick, $ident, $gecos, 'irc.sleepyirc.net', 6667, FALSE, $bind );

Bot::mainLoop();

die( 'Broke out of an infinite loop! My life is complete... Bye bye!' . "\n" );
