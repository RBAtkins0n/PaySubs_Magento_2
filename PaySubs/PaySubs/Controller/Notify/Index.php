<?php
/*
 * Copyright (c) 2020 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 *
 * Magento v2.3.0+ implement CsrfAwareActionInterface but not earlier versions
 */

namespace PaySubs\PaySubs\Controller\Notify;

/**
 * Check for existence of CsrfAwareActionInterface - only v2.3.0+
 */
if ( interface_exists( "Magento\Framework\App\CsrfAwareActionInterface" ) ) {
    class_alias( 'PaySubs\PaySubs\Controller\Notify\Indexm230', 'PaySubs\PaySubs\Controller\Notify\Index' );
} else {
    class_alias( 'PaySubs\PaySubs\Controller\Notify\Indexm220', 'PaySubs\PaySubs\Controller\Notify\Index' );
}
