<?php

/**
 * Path to the home folder for this server
 */
define( 'HOME_PATH', $_SERVER[ 'HOME' ] );

/**
 * Absolute path to the service directory
 */
define( 'INTENT_SERVICE_DIR', HOME_PATH .'/services/xena' );

/**
 * Lock file used for determining if the service is running
 */
define( 'INTENT_SERVICE_LOCK_FILE', INTENT_SERVICE_DIR .'/service.lock' );

/**
 * Path to the script used for starting the service
 */
define( 'INTENT_SERVICE_START_SCRIPT', INTENT_SERVICE_DIR .'/StartService.sh' );

/**
 * Protocol to use for the payload socket
 */
define( 'INTENT_PAYLOAD_PROTOCOL', 'udg://' );

/**
 * Address in which we receive incoming payloads (unix socket file)
 */
define( 'INTENT_PAYLOAD_ADDRESS', INTENT_SERVICE_DIR .'/payload-server.sock' );

/**
 * Protocol to use for the proxy socket
 */
define( 'INTENT_PROXY_PROTOCOL', 'tcp://' );

/**
 * Address in which this service is running
 */
define( 'INTENT_PROXY_ADDRESS', 'austinbrunkhorst.com' );

/**
 * Port in which this service is running
 */
define( 'INTENT_PROXY_PORT', 1337 );