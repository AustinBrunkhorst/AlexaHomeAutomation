<?php

require_once 'lib/Socket/Socket.php';
require_once 'lib/Socket/Factory.php';
require_once 'lib/Socket/Exception.php';

require_once 'Logging.php';

class IntentProxyService {
    /**
     * Local socket used for receiving intent payloads from HTTP requests
     *
     * @var Socket\Raw\Socket
     */
    private $m_payloadSocket = null;

    /**
     * Socket used for forwarding intent payloads
     *
     * @var Socket\Raw\Socket
     */
    private $m_proxySocket = null;

    /**
     * Clients listening to the proxy
     *
     * @var array<Socket\Raw\Socket>
     */
    private $m_proxyListeners = [ ];

    /**
     * @var bool whether or not we've called shutdown
     */
    private $m_hasShutdown = false;

    function __construct($payloadAddress, $proxyAddress) {
        $factory = new \Socket\Raw\Factory( );

        $this->m_payloadSocket = $factory->createServer( $payloadAddress );
        {
            // make sure we can re-bind the port if for whatever reason the script stops
            $this->m_payloadSocket->setOption( SOL_SOCKET, SO_REUSEADDR, 1 );
        }

        $this->m_proxySocket = $factory->createServer( $proxyAddress );
        {
            // make sure we can re-bind the port if for whatever reason the script stops
            $this->m_proxySocket->setOption( SOL_SOCKET, SO_REUSEADDR, 1 );
        }
    }

    function __destruct() {
        try {
            $this->shutdown( );
        } catch (\Socket\Raw\Exception $e) {
            // do nothing
        }
    }

    /**
     * Starts the proxy service
     */
    public function start() {
        while (true) {
            $this->acceptListeners( );

            $this->readListeners( );

            $this->readPayloads( );

            // 50 ms - avoid wasted CPU cycles
            usleep( 1000 * 50 );
        }
    }

    /**
     * Shuts down and cleans up all resources
     */
    public function shutdown() {
        // calling twice will be problematic
        if ($this->m_hasShutdown)
            return;

        $this->m_hasShutdown = true;

        foreach ($this->m_proxyListeners as $listener)
            $listener->close( );

        if ($this->m_proxySocket != null) {
            $socketPath = $this->m_proxySocket->getSockName( );

            $this->m_proxySocket->shutdown( 2 );
            $this->m_proxySocket->close( );

            // remove the socket file
            @unlink( $socketPath );
        }

        if ($this->m_payloadSocket != null) {
            $this->m_payloadSocket->shutdown( 2 );
            $this->m_payloadSocket->close( );
        }
    }

    /**
     * Accepts any new listeners wanting to connect
     */
    private function acceptListeners() {
        // nobody trying to connect
        if ($this->m_proxySocket->selectRead( 0 ) !== true)
            return;

        // client connecting
        $listener = $this->m_proxySocket->accept( );

        // it's bad practice to use resources as array keys, but this is the simplest solution
        $this->m_proxyListeners[ $listener->getResource( ) ] = $listener;

        logMessage( "{$listener->getPeerName( )} connected ({$listener->getResource( )})" );

        // @@@TODO: initiate protocol to identify listener
    }

    /**
     * Accepts data from the listeners and handles disconnection
     */
    private function readListeners() {
        foreach ($this->m_proxyListeners as $listener) {
            // nothing for us here
            if (!$listener->selectRead( ))
                continue;

            try {
                $buffer = '';

                // read as much as we can
                while (($data = $listener->read( 4096, PHP_BINARY_READ )) != '')
                    $buffer .= $data;

                // empty buffer indicates disconnection
                if ($buffer == '') {
                    $this->disconnectListener( $listener );
                } else {
                    // @@@TODO: notify message received, forward back to alexa
                    logMessage( "{$listener->getPeerName( )} (". strlen( $buffer )."): ${buffer}" );
                }
            } catch (\Socket\Raw\Exception $e) {
                $this->disconnectListener( $listener );
            }
        }
    }

    /**
     * Reads incoming intent payloads
     */
    private function readPayloads() {
        // nothing for us here
        if (!$this->m_payloadSocket->selectRead( ))
            return;

        $payload = $this->m_payloadSocket->read( 99999, PHP_BINARY_READ );

        if ($payload != '')
            $this->notifyListeners( $payload );
    }

    /**
     * Forwards incoming payloads to all listeners
     *
     * @param $payload string incoming payload
     */
    private function notifyListeners($payload) {
        logMessage( "payload: ${payload}" );

        foreach ($this->m_proxyListeners as $listener) {
            try {
                $listener->send( $payload, 0 );
            } catch (\Socket\Raw\Exception $e) {
                $this->disconnectListener( $listener );
            }
        }
    }

    /**
     * Removes a listener
     *
     * @param $listener \Socket\Raw\Socket socket to disconnect
     */
    private function disconnectListener($listener) {
        unset( $this->m_proxyListeners[ $listener->getResource( ) ] );

        try {
            logMessage( "{$listener->getPeerName( )} disconnected ({$listener->getResource( )})" );

            $listener->close( );
        } catch (\Socket\Raw\Exception $e) { }
    }
}

