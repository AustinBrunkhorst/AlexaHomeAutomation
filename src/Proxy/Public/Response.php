<?php

function respond($response) {
    echo json_encode( $response );
}

function respondError($errorCode, $errorMessage = '') {
    $error = [
        'error' => [
            'code' => $errorCode,
            'message' => $errorMessage
        ]
    ];

    respond( $error );

    exit;
}