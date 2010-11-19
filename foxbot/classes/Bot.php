<?php

class Bot {
    protected $socket; // Socket this session is attached to

    // IRC Session stuff
    protected $nick; // Bot's current nick
    protected $serverHost; // DNS hostname
    protected $serverName; // What the server calls itself
    protected $port; // The port we used
    protected $ssl; // Using SSL or no
    protected $vHost; // Host we bound to
    protected $host; // Bot's host on IRC (maybe!)
    protected $ident; // Bots identd on IRC (maybe!)

    public static function start( $nick, $ident, $gecos, $server, $port = 6667, $ssl = 0, $host = NULL ) {
        if( $ssl == 0 ) {
            $transport = 'tcp://';
        } else {
            $transport = 'ssl://';
        }
        $uri = $transport . $server . ':' . $port;

        logit( 'Connecting to ' . $uri . '...', 'notice' );

        $socket = stream_socket_client( $uri,
            $errNo,
            $errStr,
            30,
            STREAM_CLIENT_CONNECT,
            stream_context_create( array( 'socket' => array( 'bindto' => $host.':0' ) ) )
        );
        if( !$socket )
            throw new Exception( 'Failed to connect to IRC: ' . $errStr . '(' . $errNo . ')' . "\n" );

        return new self( $socket, $server, $port, $ssl, $host );
    }

    protected function __construct( $socket, $server, $port, $ssl, $host ) {
        $this->socket = $socket;
        $this->serverHost = $server;
        $this->port = $port;
        $this->ssl = $ssl;
        $this->host = $host;
    }

    function __destruct() {
        if( !feof( $this->socket ) ) {
            IRC::raw( $this->socket, 'QUIT :Being sent to destruction...' );
        }
    }


    public static function mainLoop( $botHandlers ) {
        logit( 'Started main loop...', 'debug' );
        while( TRUE ) {

            $sockets = array();

            foreach( $botHandlers as $currHandler ) {
                $sockets[] = $currHandler->socket;
            }
            $null = NULL; // because I can :D
            $activeSockets = stream_select( $sockets, $null, $null, NULL );


            if( $activeSockets === 0 ) {
                continue;
            }

            foreach( $sockets as $currSocket ) {

                $currentBot = NULL;

                foreach( $botHandlers as $handlerKey => $currHandler ) {
                    if( $currHandler->socket === $currSocket ) {
                        $currentBot = $handlerKey;
                    } // If this socket = current socket
                } // foreach bothandler...
                if( $currentBot === NULL )
                    throw new Exception( 'Read from a socket I don\'t know about... Wtf?' );
                $currentBot = $botHandlers[$currentBot];

                if( !feof( $currentBot->socket ) ) {
                    $ircData = fgets( $currentBot->socket, 512 );
                    $ircData = str_replace( array( "\n", "\r" ), '', $ircData );
                    logit( 'IRC:In : ' . $ircData, 'rawirc' );
                    $ircData = IRC::split( $ircData );

                    if( $ircData['type'] == 'direct' ) {
                        if( $ircData['command'] == 'ping' ) {
                            IRC::raw( $currentBot->socket, 'PONG :' . $ircData['pieces'][0] );
                        }
                    }
                    // Do other IRC matching stuff here
                } // if it's still a resource...
            } // foreach socket...
        } // While true loop
    } // function mainLoop

} // Bot class

?>
