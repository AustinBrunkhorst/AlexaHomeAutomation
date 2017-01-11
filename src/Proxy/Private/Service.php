<?php

require_once 'Config.php';
require_once 'Logging.php';
require_once 'IntentProxyService.php';

// allow us to run indefinitely
set_time_limit( 0 );

// ignore disconnection
ignore_user_abort( 1 );

// real time output
ob_implicit_flush( );

$lockFile = @fopen( INTENT_SERVICE_LOCK_FILE, 'a' );

// start the lock
if (!@flock( $lockFile, LOCK_EX | LOCK_NB, $wouldBlock )) {
    fatalError( $wouldBlock ? 'service already running' : 'lock failure' );
}

// make sure the payload socket isn't in use
@unlink( INTENT_PAYLOAD_ADDRESS );

function cleanup() {
    global $lockFile, $service;

    try {
        if (isset( $service ))
            $service->shutdown( );
    } catch (\Socket\Raw\Exception $e) {
        logMessage( "failed to shutdown service: {$e->getMessage( )}" );
    }

    // we're not running anymore
    @flock( $lockFile, LOCK_UN );
    @fclose( $lockFile );
    @unlink( INTENT_SERVICE_LOCK_FILE );

    @unlink( INTENT_PAYLOAD_ADDRESS );
}

register_shutdown_function( 'cleanup' );

try {
    $service = new IntentProxyService(
        INTENT_PAYLOAD_PROTOCOL . INTENT_PAYLOAD_ADDRESS,
        INTENT_PROXY_PROTOCOL . INTENT_PROXY_ADDRESS .':'. INTENT_PROXY_PORT
    );
} catch (\Socket\Raw\Exception $e) {
    fatalError( "unable to start service: {$e->getMessage( )}" );
}

logMessage( 'started server' );

try {
    $service->start( );
} catch (\Socket\Raw\Exception $e) {
    fatalError( "service uncaught exception: {$e->getMessage( )}" );
}
