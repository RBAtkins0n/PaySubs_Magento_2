<?php
/*
 * Copyright (c) 2020 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

namespace PaySubs\PaySubs\Controller;

if ( interface_exists( "Magento\Framework\App\CsrfAwareActionInterface" ) ) {
    class_alias( 'PaySubs\PaySubs\Controller\AbstractPaySubsm230', 'PaySubs\PaySubs\Controller\AbstractPaySubs' );
} else {
    class_alias( 'PaySubs\PaySubs\Controller\AbstractPaySubsm220', 'PaySubs\PaySubs\Controller\AbstractPaySubs' );
}
