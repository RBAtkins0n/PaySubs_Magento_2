<?php
/*
 * Copyright (c) 2019 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 * 
 * Released under the GNU General Public License
 */

namespace PaySubs\PaySubs\Controller\Redirect;

class Cancel extends \PaySubs\PaySubs\Controller\AbstractPaySubs
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    public function execute()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug( $pre . 'bof' );
        $page_object = $this->pageFactory->create();

        try
        {
            // Get the user session
            $this->_order = $this->_checkoutSession->getLastRealOrder();

            $this->messageManager->addNotice( 'You have successfully canceled the order using PaySubs Checkout.' );

            if ( $this->_order->getId() && $this->_order->getState() != \Magento\Sales\Model\Order::STATE_CANCELED ) {
                $this->_order->registerCancellation( 'Cancelled by user from ' . $this->_configMethod )->save();
            }

            $this->_checkoutSession->restoreQuote();

            $this->_redirect( 'checkout/cart' );

        } catch ( \Magento\Framework\Exception\LocalizedException $e ) {
            $this->_logger->error( $pre . $e->getMessage() );

            $this->messageManager->addExceptionMessage( $e, $e->getMessage() );
            $this->_redirect( 'checkout/cart' );
        } catch ( \Exception $e ) {
            $this->_logger->error( $pre . $e->getMessage() );
            $this->messageManager->addExceptionMessage( $e, __( 'We can\'t start PaySubs Checkout.' ) );
            $this->_redirect( 'checkout/cart' );
        }

        return $page_object;
    }

}
