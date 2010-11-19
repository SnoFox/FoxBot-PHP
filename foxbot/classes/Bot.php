<?php

class Bot {
    
    private static $botHandlers = array(); // Array of bot objects

    // IRC Session stuff
    protected $socket; // Socket this session is attached to
    protected $connected; // Connected to IRC or no
    protected $nick; // Bot's current nick
    protected $serverHost; // DNS hostname
    protected $serverName; // What the server calls itself
    protected $serverPass; // Accounting for the /PASS command...
    protected $port; // The port we used
    protected $ssl; // Using SSL or no
    protected $vHost; // Host we bound to
    protected $host; // Bot's host on IRC (maybe!)
    protected $ident; // Bots identd on IRC (maybe!)

    function __construct( $nick, $ident, $gecos, $server, $port = 6667, $ssl = FALSE, $host = NULL, $pass = NULL ) {
        $this->nick = $nick;
        $this->ident = $ident;
        $this->gecos = $gecos;
        $this->serverHost = $server;
        $this->port = $port;
        $this->serverPass = $pass;
        $this->ssl = $ssl;
        $this->vHost = $host;
        $this->connected = FALSE;

        // Can't set these until we get on IRC
        $this->serverName = NULL;
        $this->host = NULL;

        self::$botHandlers[] = $this;
    }

    function __destruct() {
        // Quit the bot from IRC if it's on it
        if( !feof( $this->socket ) ) {
            IRC::raw( $this->socket, 'QUIT :Being sent to destruction...' );
        }
        // Remove the bot from the list of bots
        foreach( self::$botHandlers as $handlerKey => $botHandler ) {
            if( $botHandler === $botHandlers[$handlerKey] ) {
                unset( $botHandlers[$handlerKey] );
            }
        }
    }

    function init() {

        if( $this->ssl === FALSE ) {
            $transport = 'tcp://';
        } else {
            $transport = 'ssl://';
        }
        $uri = $transport . $this->serverHost . ':' . $this->port;

        logit( 'Connecting to ' . $uri . '...', 'notice' );

        $this->socket = stream_socket_client( $uri,
            $errNo,
            $errStr,
            30,
            STREAM_CLIENT_CONNECT,
            stream_context_create( array( 'socket' => array( 'bindto' => $this->vHost.':0' ) ) )
        );
        if( !$this->socket )
            throw new Exception( 'Failed to connect to IRC: ' . $errStr . '(' . $errNo . ')' . "\n" );
var_dump($this->ident);
        if( $this->serverPass !== NULL )
            IRC::raw( $this->socket, 'PASS :' . $this->pass );
        //IRC::raw( $this->socket, 'USER ' . $this->ident . ' ' . $this->vHost . ' ' . $this->serverHost . ' :' . $this->gecos ); 
        IRC::raw( $this->socket, 'USER ' . $this->ident . ' ' . ($this->vHost === NULL ? 0 : $this->vHost) . ' ' . $this->serverHost . ' :' . $this->gecos );
        // IRC::raw( $this->socket, 'USER foxbot 1 1 :gecos here');
        IRC::raw( $this->socket, 'NICK ' . $this->nick );
    }

    public static function mainLoop() {
        logit( 'Started main loop...', 'debug' );
        foreach( self::$botHandlers as $botHandler ) {
            $botHandler->init();
        }
        while( TRUE ) {

            $sockets = array();

            foreach( self::$botHandlers as $currHandler ) {
                $sockets[] = $currHandler->socket;
            }
            $null = NULL; // because I can :D
            $activeSockets = stream_select( $sockets, $null, $null, NULL );


            if( $activeSockets === 0 ) {
                continue;
            }

            foreach( $sockets as $currSocket ) {

                $currentBot = NULL;

                foreach( self::$botHandlers as $handlerKey => $currHandler ) {
                    if( $currHandler->socket === $currSocket ) {
                        $currentBot = $handlerKey;
                    } // If this socket = current socket
                } // foreach bothandler...
                if( $currentBot === NULL )
                    throw new Exception( 'Read from a socket I don\'t know about... Wtf?' );
                $currentBot = self::$botHandlers[$currentBot];

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
