<?php

abstract class PayloadError {
    /**
     * The request is missing a payload
     */
    const MissingPayload = 0;

    /**
     * The request is not authenticated
     */
    const Authentication = 1;

    /**
     * Unable to send the payload to the proxy service
     */
    const ProxySendError = 2;

    /**
     * The proxy service is not actually running
     */
    const ProxyServiceNotRunning = 3;
}