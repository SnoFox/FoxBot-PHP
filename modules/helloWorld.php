<?php
    
class helloWorld {
    // Example module for the FoxBot PHP IRC Framework
    // This will make the bot respond to "Hello" in the channel.

    public static event_chanmsg( $bot, $nick, $ident, $host, $chan, $text ) {
        if( strtolower($text) == 'hello' ) {
            $bot->say( $chan, 'Hello world!' );
        }
    }
}
?>
