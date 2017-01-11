<?php

function logMessage($message) {
    echo $message . PHP_EOL;
}

function fatalError($message) {
    logMessage( $message );

    exit( 1 );
}