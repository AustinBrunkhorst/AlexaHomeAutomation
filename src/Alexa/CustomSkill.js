'use strict';

var http = require( 'http' );
var queryString = require( 'querystring' );
var alexaSdk = require( 'alexa-sdk' );

var Config = {
    // general stuff, revisioned
    Public: require( './Config/Public.js' ),
    // private keys and such, not revisioned - check out ./Config/Private-Skeleton.js
    Private: require( './Config/Private.js' )
};

exports.handler = function(event, context, callback) {
    var alexa = alexaSdk.handler( event, context );

    alexa.registerHandlers( Handlers );
    alexa.execute( );
};

var Handlers = {
    'LaunchRequest': function () {
        this.emit( ':tell', 'hey man whats up' );
    },
    'ButtonIntent': function() {
        var self = this;

        var payload = queryString.stringify( {
            payload: JSON.stringify( self.event.request )
        } );

        var options = {
            host: Config.Public.IntentPayload.Host,
            port: Config.Public.IntentPayload.Port,
            path: Config.Public.IntentPayload.Path,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Content-Length': Buffer.byteLength( payload )
            }
        };

        var request = http.request( options, function(response) {
            var speechOutput = 'Sure thing my brother man dude';

            // @@@TODO: handle reported errors
            self.emit( ':tell', speechOutput );
        } );

        request.write( payload );
        request.end( );
    },
    'AMAZON.HelpIntent': function () {
        // @@@TODO:
    },
    'AMAZON.CancelIntent': function () {
        // @@@TODO:
    },
    'AMAZON.StopIntent': function () {
        // @@@TODO:
    }
};
