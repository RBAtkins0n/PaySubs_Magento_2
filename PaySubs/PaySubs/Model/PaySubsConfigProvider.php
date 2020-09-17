<?php
/*
 * Copyright (c) 2020 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

namespace PaySubs\PaySubs\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use PaySubs\PaySubs\Helper\Data as PaySubsHelper;

class PaySubsConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var PaySubsHelper
     */
    protected $paysubsHelper;

    /**
     * @var string[]
     */
    protected $methodCodes = [
        Config::METHOD_CODE,
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @param ConfigFactory $configFactory
     * @param ResolverInterface $localeResolver
     * @param CurrentCustomer $currentCustomer
     * @param PaySubsHelper $paymentHelper
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ConfigFactory $configFactory,
        ResolverInterface $localeResolver,
        CurrentCustomer $currentCustomer,
        PaySubsHelper $paysubsHelper,
        PaymentHelper $paymentHelper
    ) {
        $this->_logger = $logger;
        $pre           = __METHOD__ . ' : ';
        $this->_logger->debug( $pre . 'bof' );

        $this->localeResolver  = $localeResolver;
        $this->config          = $configFactory->create();
        $this->currentCustomer = $currentCustomer;
        $this->paysubsHelper   = $paysubsHelper;
        $this->paymentHelper   = $paymentHelper;

        foreach ( $this->methodCodes as $code ) {
            $this->methods[$code] = $this->paymentHelper->getMethodInstance( $code );
        }

        $this->_logger->debug( $pre . 'eof and this  methods has : ', $this->methods );
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug( $pre . 'bof' );
        $config = [
            'payment' => [
                'paysubs' => [
                    'paymentAcceptanceMarkSrc'  => $this->config->getPaymentMarkImageUrl(),
                    'paymentAcceptanceMarkHref' => $this->config->getPaymentMarkWhatIsPaySubs(),
                ],
            ],
        ];

        foreach ( $this->methodCodes as $code ) {
            if ( $this->methods[$code]->isAvailable() ) {
                $config['payment']['paysubs']['redirectUrl'][$code]          = $this->getMethodRedirectUrl( $code );
                $config['payment']['paysubs']['billingAgreementCode'][$code] = $this->getBillingAgreementCode( $code );

            }
        }
        $this->_logger->debug( $pre . 'eof', $config );
        return $config;
    }

    /**
     * Return redirect URL for method
     *
     * @param string $code
     * @return mixed
     */
    protected function getMethodRedirectUrl( $code )
    {
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug( $pre . 'bof' );

        $methodUrl = $this->methods[$code]->getCheckoutRedirectUrl();

        $this->_logger->debug( $pre . 'eof' );
        return $methodUrl;
    }

    /**
     * Return billing agreement code for method
     *
     * @param string $code
     * @return null|string
     */
    protected function getBillingAgreementCode( $code )
    {

        $pre = __METHOD__ . ' : ';
        $this->_logger->debug( $pre . 'bof' );

        $customerId = $this->currentCustomer->getCustomerId();
        $this->config->setMethod( $code );

        $this->_logger->debug( $pre . 'eof' );

        // Always return null
        return $this->paysubsHelper->shouldAskToCreateBillingAgreement( $this->config, $customerId );
    }
}
