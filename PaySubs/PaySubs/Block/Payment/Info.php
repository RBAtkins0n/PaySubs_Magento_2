<?php
/*
 * Copyright (c) 2018 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 * 
 * Released under the GNU General Public License
 */

namespace PaySubs\PaySubs\Block\Payment;

/**
 * PaySubs common payment info block
 * Uses default templates
 */
class Info extends \Magento\Payment\Block\Info
{
    /**
     * @var \PaySubs\PaySubs\Model\InfoFactory
     */
    protected $_paysubsInfoFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \PaySubs\PaySubs\Model\InfoFactory $paysubsInfoFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \PaySubs\PaySubs\Model\InfoFactory $paysubsInfoFactory,
        array $data = []
    ) {
        $this->_paysubsInfoFactory = $paysubsInfoFactory;
        parent::__construct( $context, $data );
    }

}
