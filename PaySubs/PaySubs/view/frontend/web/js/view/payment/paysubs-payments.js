/*
 * Copyright (c) 2018 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 * 
 * Released under the GNU General Public License
 */
/*browser:true*/
/*global define*/
define(

    [

        'uiComponent',

        'Magento_Checkout/js/model/payment/renderer-list'

    ],

    function (
	Component,

        rendererList

    ){

        'use strict';


        rendererList.push(

            {

                type: 'paysubs',

                component: 'PaySubs_PaySubs/js/view/payment/method-renderer/paysubs-method'

            }

        );

        /** Add view logic here if needed */

        return Component.extend({});

    }
);