<?php
/*
 * Copyright (c) 2018 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

namespace PaySubs\PaySubs\Block;

use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use PaySubs\PaySubs\Model\Config;

class Form extends \Magento\Payment\Block\Form
{
    /** @var string Payment method code */
    protected $_methodCode = Config::METHOD_CODE;

    /** @var \PaySubs\PaySubs\Helper\Data */
    protected $_paysubsData;

    /** @var \PaySubs\PaySubs\Model\ConfigFactory */
    protected $paysubsConfigFactory;

    /** @var ResolverInterface */
    protected $_localeResolver;

    /** @var \PaySubs\PaySubs\Model\Config */
    protected $_config;

    /** @var bool */
    protected $_isScopePrivate;

    /** @var CurrentCustomer */
    protected $currentCustomer;

    /** @var Logging */
    protected $_logger;

    /**
     * @param Context $context
     * @param \PaySubs\PaySubs\Model\ConfigFactory $paysubsConfigFactory
     * @param ResolverInterface $localeResolver
     * @param \PaySubs\PaySubs\Helper\Data $paysubsData
     * @param CurrentCustomer $currentCustomer
     * @param array $data
     */
    public function __construct(
        Context $context,
        \PaySubs\PaySubs\Model\ConfigFactory $paysubsConfigFactory,
        ResolverInterface $localeResolver,
        \PaySubs\PaySubs\Helper\Data $paysubsData,
        CurrentCustomer $currentCustomer,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        $this->_logger = $logger;
        $pre           = __METHOD__ . " : ";
        $this->_logger->debug( $pre . 'bof' );
        $this->_paysubsData         = $paysubsData;
        $this->paysubsConfigFactory = $paysubsConfigFactory;
        $this->_localeResolver      = $localeResolver;
        $this->_config              = null;
        $this->_isScopePrivate      = true;
        $this->currentCustomer      = $currentCustomer;
        parent::__construct( $context, $data );
        $this->_logger->debug( $pre . "eof" );
    }

    /**
     * Set template and redirect message
     *
     * @return null
     */
    protected function _construct()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug( $pre . 'bof' );
        $this->_config = $this->paysubsConfigFactory->create()->setMethod( $this->getMethodCode() );
        parent::_construct();
    }

    /**
     * Payment method code getter
     *
     * @return string
     */
    public function getMethodCode()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug( $pre . 'bof' );

        return $this->_methodCode;
    }

}
