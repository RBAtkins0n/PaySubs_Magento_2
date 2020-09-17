<?php
/*
 * Copyright (c) 2020 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

namespace PaySubs\PaySubs\Model;

include_once dirname( __FILE__ ) . '/../Model/paysubs_common.inc';

use Magento\Framework\Data\Form\FormKey;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaySubs extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    protected $_code = Config::METHOD_CODE;

    /**
     * @var string
     */
    protected $_formBlockType = 'PaySubs\PaySubs\Block\Form';

    /**
     * @var string
     */
    protected $_infoBlockType = 'PaySubs\PaySubs\Block\Payment\Info';

    /** @var string */
    protected $_configType = 'PaySubs\PaySubs\Model\Config';

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isGateway = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canOrder = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canVoid = false;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseInternal = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canFetchTransactionInfo = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_canReviewPayment = true;

    /**
     * Website Payments Pro instance
     *
     * @var \PaySubs\PaySubs\Model\Config $config
     */
    protected $_config;

    /**
     * Payment additional information key for payment action
     *
     * @var string
     */
    protected $_isOrderPaymentActionKey = 'is_order_action';

    /**
     * Payment additional information key for number of used authorizations
     *
     * @var string
     */
    protected $_authorizationCountKey = 'authorization_count';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_formKey;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\Exception\LocalizedExceptionFactory
     */
    protected $_exception;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var Transaction\BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \PaySubs\PaySubs\Model\ConfigFactory $configFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \PaySubs\PaySubs\Model\CartFactory $cartFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Exception\LocalizedExceptionFactory $exception
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param Transaction\BuilderInterface $transactionBuilder
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct( \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        ConfigFactory $configFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Exception\LocalizedExceptionFactory $exception,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [] ) {
        parent::__construct( $context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data );
        $this->_storeManager         = $storeManager;
        $this->_urlBuilder           = $urlBuilder;
        $this->_formKey              = $formKey;
        $this->_checkoutSession      = $checkoutSession;
        $this->_exception            = $exception;
        $this->transactionRepository = $transactionRepository;
        $this->transactionBuilder    = $transactionBuilder;

        $parameters = ['params' => [$this->_code]];

        $this->_config = $configFactory->create( $parameters );

        if ( !defined( 'PaySubs_DEBUG' ) ) {
            define( 'PaySubs_DEBUG', $this->getConfigData( 'debug' ) );
        }

    }

    /**
     * Store setter
     * Also updates store ID in config object
     *
     * @param \Magento\Store\Model\Store|int $store
     *
     * @return $this
     */
    public function setStore( $store )
    {
        $this->setData( 'store', $store );

        if ( null === $store ) {
            $store = $this->_storeManager->getStore()->getId();
        }
        $this->_config->setStoreId( is_object( $store ) ? $store->getId() : $store );

        return $this;
    }

    /**
     * Whether method is available for specified currency
     *
     * @param string $currencyCode
     *
     * @return bool
     */
    public function canUseForCurrency( $currencyCode )
    {
        return $this->_config->isCurrencyCodeSupported( $currencyCode );
    }

    /**
     * Payment action getter compatible with payment model
     *
     * @see \Magento\Sales\Model\Payment::place()
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return $this->_config->getPaymentAction();
    }

    /**
     * Check whether payment method can be used
     *
     * @param \Magento\Quote\Api\Data\CartInterface|Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable( \Magento\Quote\Api\Data\CartInterface $quote = null )
    {
        return parent::isAvailable( $quote ) && $this->_config->isMethodAvailable();
    }

    /**
     * @return mixed
     */
    protected function getStoreName()
    {
        $pre = __METHOD__ . " : ";
        paysubslog( $pre . 'bof' );

        $storeName = $this->_scopeConfig->getValue(
            'general/store_information/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        paysubslog( $pre . 'store name is ' . $storeName );

        return $storeName;
    }

    /**
     * Place an order with authorization or capture action
     *
     * @param Payment $payment
     * @param float $amount
     *
     * @return $this
     */
    protected function _placeOrder( Payment $payment, $amount )
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug( $pre . 'bof' );

    }

    /**
     * this where we compile data posted by the form to PaySubs
     * @return array
     */
    public function getStandardCheckoutFormFields()
    {
        $pre = __METHOD__ . ' : ';
        // Variable initialization
        $order       = $this->_checkoutSession->getLastRealOrder();
        $amount      = number_format( $this->getTotalAmount( $order ), 2 );
        $currency    = $order->getOrderCurrencyCode();
        $order_id    = $order->getRealOrderId();
        $terminal_id = $this->getConfigData( 'terminal_id' );
        $description = $this->getConfigData( 'description' );
        $settlement  = $this->getConfigData( 'delayed_settlement' );
        if ( $settlement == '1' ) {
            $settlement = 'y';
        } else {
            $settlement = 'n';
        }
        $budget = $this->getConfigData( 'budget' );
        if ( $budget == '1' ) {
            $budget = 'y';
        } else {
            $budget = 'n';
        }

        $pam         = $this->getConfigData( 'pam' );
        $send_email  = $this->getConfigData( 'holder_email' );
        $send_msg    = $this->getConfigData( 'sms_message' );
        $recurring   = $this->getConfigData( 'recurring' );
        $occur_email = $this->getConfigData( 'occurance_email' );
        $return_url  = $this->getConfigData( 'paysubs_return_url' );

        if ( $return_url ) {
            $cancelled_url = $this->getConfigData( 'paysubs_cancelled_url' ) . '?form_key=' . $this->_formKey->getFormKey();
            $approved_url  = $this->getConfigData( 'paysubs_approved_url' ) . '?form_key=' . $this->_formKey->getFormKey();
            $declined_url  = $this->getConfigData( 'paysubs_declined_url' ) . '?form_key=' . $this->_formKey->getFormKey();
        } else {
            $cancelled_url = $this->getPaidCancelUrl();
            $approved_url  = $this->getPaidSuccessUrl();
            $declined_url  = $this->getPaidCancelUrl();
        }
        if ( $recurring ) {
            $occur_freq   = $this->getConfigData( 'occur_frequency' );
            $occur_count  = $this->getConfigData( 'occur_count' );
            $occur_amount = $this->getConfigData( 'occur_amount' );
            $occur_date   = $this->getConfigData( 'occur_date' );
        } else {
            $occur_freq   = '';
            $occur_count  = '';
            $occur_amount = '';
            $occur_date   = '';
        }
        if ( $send_msg ) {
            $message = $this->getConfigData( 'message' );
        } else {
            $message = '';
        }
        if ( $send_email ) {
            $customerAddressId = $order->getBillingAddress();

            $email = $customerAddressId->getEmail();
        } else {
            $email = '';
        }
        $customerAddressId = $order->getBillingAddress();
        $phone             = $customerAddressId->getTelephone();
        $hashParams        = ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8', 'p9', 'p10', 'p11', 'p12', 'p13', 'NextOccurDate', 'Budget', 'CardholderEmail', 'm_1',
            'm_2', 'm_3', 'm_4', 'm_5', 'm_6', 'm_7', 'm_8', 'm_9', 'm_10', 'CustomerID', 'RecurReference', 'MerchantToken'];
        $params = array(
            'p1'              => $terminal_id,
            'p2'              => $order->getRealOrderId() . ' ' . date( "h:i:s" ),
            'p3'              => $description,
            'p4'              => $amount,
            'p5'              => $currency,
            'p6'              => $occur_freq,
            'p7'              => $occur_count,
            'p8'              => $phone,
            'p9'              => $message,
            'p10'             => $cancelled_url,
            'p11'             => $occur_email,
            'p12'             => $settlement,
            'p13'             => $occur_amount,
            'm_3'             => $order->getRealOrderId(),
            'NextOccurDate'   => $occur_date,
            'Budget'          => $budget,
            'CardholderEmail' => $email,
            'UrlsProvided'    => 'Y',
            'ApprovedUrl'     => $approved_url,
            'DeclinedUrl'     => $declined_url,
        );
        if ( $pam != '' ) {
            $hashString = '';
            foreach ( $hashParams as $hashParam ) {
                $hashString .= isset( $params[$hashParam] ) ? $params[$hashParam] : '';
            }
            $params['Hash'] = md5( $hashString . $pam );
        }
        paysubslog( $pre . 'params are :' . print_r( $params, true ) );
        return ( $params );
    }

    /**
     * getTotalAmount
     */
    public function getTotalAmount( $order )
    {
        if ( $this->getConfigData( 'use_store_currency' ) ) {
            $price = $this->getNumberFormat( $order->getGrandTotal() );
        } else {
            $price = $this->getNumberFormat( $order->getBaseGrandTotal() );
        }

        return $price;
    }

    /**
     * getNumberFormat
     */
    public function getNumberFormat( $number )
    {
        return number_format( $number, 2, '.', '' );
    }

    /**
     * getPaidSuccessUrl
     */
    public function getPaidSuccessUrl()
    {
        return $this->_urlBuilder->getUrl( 'paysubs/redirect/success', array( '_secure' => true ) ) . '?form_key=' . $this->_formKey->getFormKey();
    }

    /**
     * Get transaction with type order
     *
     * @param OrderPaymentInterface $payment
     *
     * @return false|\Magento\Sales\Api\Data\TransactionInterface
     */
    protected function getOrderTransaction( $payment )
    {
        return $this->transactionRepository->getByTransactionType( Transaction::TYPE_ORDER, $payment->getId(), $payment->getOrder()->getId() );
    }

    /*
     * called dynamically by checkout's framework.
     */
    public function getOrderPlaceRedirectUrl()
    {
        $pre = __METHOD__ . " : ";
        paysubslog( $pre . 'bof' );

        return $this->_urlBuilder->getUrl( 'paysubs/redirect' ) . '?form_key=' . $this->_formKey->getFormKey();

    }

    /**
     * Checkout redirect URL getter for onepage checkout (hardcode)
     *
     * @see \Magento\Checkout\Controller\Onepage::savePaymentAction()
     * @see Quote\Payment::getCheckoutRedirectUrl()
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        $pre = __METHOD__ . " : ";
        paysubslog( $pre . 'bof' );

        return $this->_urlBuilder->getUrl( 'paysubs/redirect' ) . '?form_key=' . $this->_formKey->getFormKey();
    }

    /**
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return $this
     */
    public function initialize( $paymentAction, $stateObject )
    {
        $pre = __METHOD__ . " : ";
        paysubslog( $pre . 'bof' );

        $stateObject->setState( \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT );
        $stateObject->setStatus( 'pending_payment' );
        $stateObject->setIsNotified( false );

        return parent::initialize( $paymentAction, $stateObject ); // TODO: Change the autogenerated stub

    }

    /**
     * getPaidCancelUrl
     */
    public function getPaidCancelUrl()
    {
        return $this->_urlBuilder->getUrl( 'paysubs/redirect/cancel', array( '_secure' => true ) ) . '?form_key=' . $this->_formKey->getFormKey();
    }

    /**
     * getPaidNotifyUrl
     */
    public function getPaidNotifyUrl()
    {
        return $this->_urlBuilder->getUrl( 'paysubs/notify', array( '_secure' => true ) ) . '?form_key=' . $this->_formKey->getFormKey();
    }

    /**
     * getPaySubsUrl
     *
     * Get URL for form submission to PaySubs.
     */
    public function getPaySubsUrl()
    {
        // return ( 'https://core3.directpay.online/vcs/pay' );
        return ( 'https://www.vcs.co.za/vvonline/vcspay.aspx' );
    }

    /**
     * @param $serverMode
     *
     * @return string
     */
    public function getPaySubsHost()
    {
        $paysubsHost = "www.vcs.co.za";

        return $paysubsHost;
    }
}
