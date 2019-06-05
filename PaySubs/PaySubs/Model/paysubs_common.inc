<?php
/*
 * Copyright (c) 2019 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 * 
 * Released under the GNU General Public License
 */

//// Create user agent string
// User agent constituents (for cURL)
define( 'PaySubs_SOFTWARE_NAME', 'Magento' );
define( 'PaySubs_SOFTWARE_VER', '2.2.5' );
define( 'PaySubs_MODULE_NAME', 'PaySubs-Magento' );
define( 'PaySubs_MODULE_VER', '1.0.0' );

// Features
// - PHP
$pfFeatures = 'PHP ' . phpversion() . ';';

// - cURL
if ( in_array( 'curl', get_loaded_extensions() ) ) {
    define( 'PaySubs_CURL', '' );
    $pfVersion = curl_version();
    $pfFeatures .= ' curl ' . $pfVersion['version'] . ';';
} else {
    $pfFeatures .= ' nocurl;';
}

// Create user agrent
define( 'PaySubs_USER_AGENT', PaySubs_SOFTWARE_NAME . '/' . PaySubs_SOFTWARE_VER . ' (' . trim( $pfFeatures ) . ') ' . PaySubs_MODULE_NAME . '/' . PaySubs_MODULE_VER );

// General Defines
define( 'PaySubs_TIMEOUT', 15 );
define( 'PaySubs_EPSILON', 0.01 );

// Messages
// Error
define( 'PaySubs_ERR_AMOUNT_MISMATCH', 'Amount mismatch' );
define( 'PaySubs_ERR_BAD_ACCESS', 'Bad access of page' );
define( 'PaySubs_ERR_BAD_SOURCE_IP', 'Bad source IP address' );
define( 'PaySubs_ERR_CONNECT_FAILED', 'Failed to connect to PaySubs' );
define( 'PaySubs_ERR_INVALID_SIGNATURE', 'Security signature mismatch' );
define( 'PaySubs_ERR_MERCHANT_ID_MISMATCH', 'Merchant ID mismatch' );
define( 'PaySubs_ERR_NO_SESSION', 'No saved session found for ITN transaction' );
define( 'PaySubs_ERR_ORDER_ID_MISSING_URL', 'Order ID not present in URL' );
define( 'PaySubs_ERR_ORDER_ID_MISMATCH', 'Order ID mismatch' );
define( 'PaySubs_ERR_ORDER_INVALID', 'This order ID is invalid' );
define( 'PaySubs_ERR_ORDER_PROCESSED', 'This order has already been processed' );
define( 'PaySubs_ERR_PDT_FAIL', 'PDT query failed' );
define( 'PaySubs_ERR_PDT_TOKEN_MISSING', 'PDT token not present in URL' );
define( 'PaySubs_ERR_SESSIONID_MISMATCH', 'Session ID mismatch' );
define( 'PaySubs_ERR_UNKNOWN', 'Unkown error occurred' );

// General
define( 'PaySubs_MSG_OK', 'Payment was successful' );
define( 'PaySubs_MSG_FAILED', 'Payment has failed' );
define( 'PaySubs_MSG_PENDING',
    'The payment is pending. Please note, you will receive another Instant' .
    ' Transaction Notification when the payment status changes to' .
    ' "Completed", or "Failed"' );

// {{{ paysubsLog
/**
 * paysubslog
 *
 * Log function for logging output.
 *
 * @param $msg String Message to log
 * @param $close Boolean Whether to close the log file or not
 */
function paysubslog( $msg = '', $close = false )
{
    static $fh = 0;
    global $module;

    // Only log if debugging is enabled
    if ( PaySubs_DEBUG ) {
        if ( $close ) {
            fclose( $fh );
        } else {
            // If file doesn't exist, create it
            if ( !$fh ) {
                $pathinfo = pathinfo( __FILE__ );
                $fh       = fopen( $pathinfo['dirname'] . '/paysubs.log', 'a+' );
            }

            // If file was successfully created
            if ( $fh ) {
                $line = date( 'Y-m-d H:i:s' ) . ' : ' . $msg . "\n";

                fwrite( $fh, $line );
            }
        }
    }
}
// }}}
// {{{ paysubsGetData
/**
 * paysubsGetData
 */
function paysubsGetData()
{
    // Posted variables from ITN
    $paysubsData = $_POST;

    // Strip any slashes in data
    foreach ( $paysubsData as $key => $val ) {
        $paysubsData[$key] = stripslashes( $val );
    }

    // Return "false" if no data was received
    if ( sizeof( $paysubsData ) == 0 ) {
        return [];
    } else {
        return ( $paysubsData );
    }

}
// }}}
// {{{ paysubsAmountsEqual
/**
 * paysubsAmountsEqual
 *
 * Checks to see whether the given amounts are equal using a proper floating
 * point comparison with an Epsilon which ensures that insignificant decimal
 * places are ignored in the comparison.
 *
 * eg. 100.00 is equal to 100.0001
 *
 * @param $amount1 Float 1st amount for comparison
 * @param $amount2 Float 2nd amount for comparison
 */
function paysubsAmountsEqual( $amount1, $amount2 )
{
    if ( abs( floatval( $amount1 ) - floatval( $amount2 ) ) > PaySubs_EPSILON ) {
        return ( false );
    } else {
        return ( true );
    }

}
// }}}
