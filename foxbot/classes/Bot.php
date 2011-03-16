<?php

class Bot {

    private static $botHandlers = array(); // Array of bot objects

    // IRC Session stuff
    protected $socket; // Socket this session is attached to
    protected $Session; // Session object for this bot
    protected $connected; // Connected to IRC or no
    protected $nick; // Bot's configured nick
    protected $serverHost; // DNS hostname
    protected $serverName; // What the server calls itself
    protected $serverPass; // Accounting for the /PASS command...
    protected $port; // The port we used
    protected $ssl; // Using SSL or no
    protected $vHost; // Host we bound to

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

    protected function init() {
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

        if( $this->serverPass !== NULL )
            IRC::raw( $this->socket, 'PASS :' . $this->pass );
        IRC::raw( $this->socket, 'USER ' . $this->ident . ' ' . ($this->vHost === NULL ? 0 : $this->vHost) . ' ' . $this->serverHost . ' :' . $this->gecos );
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

                    $src = IRC::splitSrc( $ircData['source'] );

                    switch( $ircData['command'] ) {
                        // Numerics:
                    case '001':
                        // RPL_WELCOME
                        $this->Session = new Session( $ircData['target'] );
                        $this->serverName = $ircData['source'];
                        $this->connected = TRUE;
                        Event::emit( $this, 'connected' );
                        break;
                    case '002':
                        // RPL_YOURHOST
                        break;
                    case '003':
                        // RPL_CREATED
                        break;
                    case '004':
                        // RPL_MYINFO
                        break;
                    case '005':
                        // RPL_ISUPPORT
                        $tmpSupport = $data['pieces'];
                        // Rid us of "are supported by this server" >.<
                        array_pop($tmpSupport);
                        $newSupport = array();
                        foreach( $tmpSupport as $key => $feature ) {
                            $split = explode( '=', $feature, 2 );
                            $newSupport[strtolower($split[0])] = isset($split[1]) ? $split[1] : NULL;
                        }
                        $this->Session->isupport = array_merge($this->Session->isupport,$newSupport);
                        break;
                    case '433':
                        // ERR_NICKNAMEINUSE
                        if( $this->connected === FALSE ) {
                            $badNick = $ircData['pieces'][0];
                            if( strlen( $ircData['pieces'][0] ) < 9 ) {
                                // Just append underscores until we hit nine chars.
                                $newNick = $this->nick . '_';
                            } else {
                                switch( rand(0, 3) ) {
                                case 0:
                                    // Reverse the nick
                                    $newNick;
                                    for( $i=strlen($badNick); $i > 0; $i-- ) {
                                        if( ( is_numeric( $badNick[$i] ) or $badNick[$i] === '-' ) and $i === strlen($badNick) ) {
                                            $newNick .= '_'; // Can't start with numbers or hyphens
                                            continue;
                                        }
                                        $newNick .= $badNick[$i];
                                    }
                                    break;
                                case 1:
                                    // Shuffle the badnick
                                    $newNick = str_shuffle( $badNick );
                                    if( is_numeric( $newNick[0] ) )
                                        $newNick[0] = '_';
                                    break;
                                case 2:
                                    // Append a two digit number
                                    $newNick = $badNick . rand(10, 99);
                                    break;
                                case 3:
                                    // Prepend a random letter
                                    $letter = ( rand(0, 1) ? rand(65, 90) : rand(97, 122) );
                                    $newNick[0] = chr($letter);
                                    break;
                                default:
                                    logit( 'rand() doesn\'t do what I think it does...' );
                                    $newNick = 'FoxBot-lol';
                                    break;
                                }
                            }
                        IRC::raw( $this->socket, 'NICK ' . $newNick );
                        }
                        Event::emit( $this, 'numeric_433', array( $ircData['target'], $ircData['pieces'][0] ) );
                        break;
                        // Normal IRC stuffs
                    case 'join':
                        // :SnoFox!~SnoFox@SnoFox.net JOIN #clueirc
                        Event::emit( $this, 'join', array( $src, $ircData['target'] ) );
                        break;
                    case 'part':
                        // :SnoFox!~SnoFox@SnoFox.net PART #clueirc :Too many lamers
                        Event::emit( $this, 'part', array( $src, $ircData['target'], $ircData['pieces'][0] ) );
                        break;
                    case 'quit':
                        // :SnoFox!~SnoFox@SnoFox.net QUIT :Lamers everywhere!
                        // Note: oddity in the IRC splitter here; target becomes the quit reason
                        Event::emit( $this, 'quit', array( $src, $ircData['target'] ) );
                        break;
                    case 'mode':
                        // :SnoFox!~SnoFox@SnoFox.net MODE #clueirc +mbte *!*@*.eu nathan!*@*
                        Event::emit( $this, 'raw_mode', array( $src, $ircData['target'], $ircData['pieces'] ) );
                        break;
                    case 'kick':
                        // :SnoFox!~SnoFox@SnoFox.net KICK #clueirc MJ94 :Quit being a lamer!
                        Event::emit( $this, 'kick', array( $src, $ircData['target'], $ircData['pieces'][0], $ircData['pieces'][1] ) );
                        break;
                    case 'topic':
                        // :SnoFox!~SnoFox@SnoFox.net TOPIC #clueirc :Giggity ClueNet! | Bots! Bots everywhere!
                        Event::emit( $this, 'topic', array( $src, $ircData['target'], $ircData['pieces'][0] ) );
                        break;
                    case 'privmsg':
                        // :SnoFox!~SnoFox@SnoFox.net PRIVMSG #clueirc :Du-nu-nu-nu-nu-nu-du-nu-nu-nu-nu-nu BAT MAN!
                        // :SnoFox!~SnoFox@SnoFox.net PRIVMSG #clueirc :\x01slaps Cobi around a bit with a large trout!\x01
                        if( $ircData['pieces'][0][0] === "\x01" ) {
                            // CTCPs are just modified PRIVMSGs, so make specialness for them
                            $ctcp = explode( ' ', trim( $ircData['pieces'][0], "\x01" ), 2);
                            $ctcpMsg = $ctcp[1];
                            $ctcp = strtoupper($ctcp[0]);
                            if( $ctcp == 'ACTION' ) {
                                // Every client treats ACTION specially...
                                Event::emit( $this, 'action', array( $src, $ircData['target'], $ctcpMsg ) );
                                break;
                            }
                            Event::emit( $this, 'ctcp', array( $src, $ircData['target'], $ctcp, $ctcpMsg ) );
                            break;
                        }
                        if( isset( $this->Session->isupport['CHANTYPES'] ) ) {
                            if( strpos( $ircData['target'], $this->Session->isupport['CHANTYPES'] ) === '0' ) {
                                Event::emit( $this, 'chan_msg', array( $src, $ircData['target'], $ircData['pieces'][0] ) )
                            }
                            else {
                                Event::emit( $this, 'priv_msg', array( $src, $ircData['pieces'][0] ) );
                            }
                            Event::emit( $this, 'msg', array( $ircData['target'], $ircData['pieces'][0] ) );
                        }
                        break;
                    case 'nick':
                        // SnoFox!~SnoFox@SnoFox.net NICK Fox-in-a-Box
                        Event::emit( $this, 'nick', array( $src, $ircData['pieces'][0] ) );
                        break;
                    case 'invite':
                        // SnoFox!~SnoFox@SnoFox.net INVITE netcat :#clueirc
                        Event::emit( $this, 'invite', array( $src, $ircData['pieces'][0] ) );
                        break;
                    case 'notice':
                        if( isset( $this->Session->isupport['CHANTYPES'] ) ) {
                            if( strpos( $ircData['target'], $this->Session->isupport['CHANTYPES'] ) === '0' ) {
                                Event::emit( $this, 'chan_notice', array( $src, $ircData['target'], $ircData['pieces'][0] ) )
                            }
                            else {
                                Event::emit( $this, 'priv_notice', array( $src, $ircData['pieces'][0] ) );
                            }
                        }
                        Event::emit( $this, 'notice', array( $ircData['target'], $ircData['pieces'][0] ) );
                        break;
                    default:
                        $event = $ircData['command'];
                        if( is_numeric( $event ) )
                            $event = 'numeric_' . $event;
                        logit( 'Emitting unparsed event: ' . $event, 'junk' );
                        Event::emit( $this, $event, $ircData['pieces'] );
                        break;
                    }
                } else {
                    // We were disconnected from IRC. Let's reconnect...
                    $this->connected = FALSE;
                    Event::emit( $this, 'disconnect' );
                    $this->Session = NULL;
                    $this->init();
                }
            } // foreach socket...
        } // While true loop
    } // function mainLoop
} // Bot class

?>
