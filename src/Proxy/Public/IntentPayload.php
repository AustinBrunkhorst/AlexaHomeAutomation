<?php

require_once 'ErrorCodes.php';
require_once 'Response.php';

header( 'Content-Type: application/json' );

$payload = @$_POST[ 'payload' ];

if (!isset( $payload ))
    respondError( PayloadError::MissingPayload );

$privateDir = $_SERVER[ 'HOME' ] .'/services/xena';

require_once $privateDir .'/Config.php';
require_once $privateDir .'/Utils.php';
require_once $privateDir .'/lib/Socket/Socket.php';
require_once $privateDir .'/lib/Socket/Factory.php';
require_once $privateDir .'/lib/Socket/Exception.php';

$factory = new \Socket\Raw\Factory( );

// unix socket file
$clientAddress = __DIR__ .'/'. uniqid( 'socket-client', true ) .'.socket';

try {
    $client = $factory->createUdg( );
    $client->bind( $clientAddress );
    $client->connect( INTENT_PAYLOAD_ADDRESS );

    $client->send( $payload, 0 );

    $client->shutdown( 2 );
    $client->close( );

    // cleanup the socket file
    @unlink( $clientAddress );
} catch(\Socket\Raw\Exception $e) {
    $wasRunning = serviceRestartIfStopped( );

    if ($wasRunning) {
        // because it was running, we can assume there was a transmission error
        respondError( PayloadError::ProxySendError, $e->getMessage( ) );
    } else {
        // the service wasn't even running
        respondError( PayloadError::ProxyServiceNotRunning );
    }
}

respond( [ 'success' => true ] );
