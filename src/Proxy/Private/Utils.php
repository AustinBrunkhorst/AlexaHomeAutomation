<?php

require_once 'Config.php';

/**
 * Determines whether not the service is currently running
 *
 * @return bool true if running, false if not
 */
function serviceIsRunning() {
    $lockFile = @fopen( INTENT_SERVICE_LOCK_FILE, 'r' );

    // doesn't exist or error, so assume not running
    if (!$lockFile)
        return false;

    // if we can obtain a lock, the service isn't running
    if (@flock( $lockFile, LOCK_SH | LOCK_NB ))
    {
        // make sure we release the lock
        @flock( $lockFile, LOCK_UN );

        return false;
    }

    return true;
}

/**
 * Starts the service, only if it's not currently running
 *
 * @return bool whether or not the service was running before calling this
 */
function serviceRestartIfStopped() {
    $isRunning = serviceIsRunning( );

    if (!$isRunning)
        exec( INTENT_SERVICE_START_SCRIPT );

    return $isRunning;
}