<?php
class Event {

    private static $hooks; // Array of registered events
    private static $modules; // Array of registered modules

    static function emit() {
        $args = func_get_args();
        $type = array_shift( $args );
        $modResult = NULL;
        $hookResult = NULL;

        logit( 'Event ' . $type . ' fired with args: ' . implode( ', ', $args ), 'debug' );

        // Hooks
        if( isset($hooks[$type]) ) {
            foreach( $hooks[$type] as $hook ) {
                $hookResult[] = call_user_func( $hook, $args );
            }
        } else {
            logit( 'No hooks attached to event ' . $type, 'debug' );
        }

        // Modules
        foreach( self::modules as $module ) {
            if( method_exists( $module, $type ) ) {
                $modResult[] = call_user_func( array( $module, $type ), $args );
            }
        }
    }

    static function addHook( $type, $callback ) {
        self::hooks[$type][] = $callback;
    }

    static function destroyHook( $type, $callback ) {
        if( !isset( self::$hooks[$type] ) ) {
            return FALSE;
        }
        searchAndDestroy( self::$hooks[$type], $callback );
    }

    static function destroyAllHooks( $type ) {
        if( !isset( self::$hooks[$type] ) )
            return FALSE;
        unset( self::$hooks[$type] );
    }

    static function registerModule( $callBackObj ) {
        self::modules[] = $callBackObj;
    }

    static function destroyModule( $callBackObj ) {
        searchAndDestroy( self::$modules, $callBackObj );
    }
}
