<?php
/*
 * Copyright (c) 2020 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

namespace PaySubs\PaySubs\Controller\Redirect;

class Success extends \PaySubs\PaySubs\Controller\AbstractPaySubs
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    public function execute()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug( $pre . 'bof' );

        try {
            // Get the user session
            $this->_order = $this->_checkoutSession->getLastRealOrder();

            // Posted variables from ITN
            $pgData = $_POST;

            // Strip any slashes in data
            foreach ( $pgData as $key => $val ) {
                $pgData[$key] = stripslashes( $val );
            }

            // Get order from POST if not in session
            if ( empty( $this->_order->getId() ) && isset( $pgData['m_3'] ) ) {
                $orderId      = filter_var( $pgData['m_3'], FILTER_SANITIZE_STRING );
                $this->_order = $this->_orderFactory->create()->loadByIncrementId( $orderId );
                $this->_checkoutSession->setLastOrderId( $this->_order->getId() );
                $this->_checkoutSession->setLastRealOrderId( $orderId );
            }
            if ( isset( $pgData['p3'] ) ) {
                if ( !empty( $pgData['p3'] ) && strpos( $pgData['p3'], 'APPROVED' ) !== false ) {
                    $status = 1;
                } else {
                    $status = 2;
                }
            }

            switch ( $status ) {
                case 1:
                    $status = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    $this->_order->setStatus( $status ); // Configure the status
                    $this->_order->setState( $status )->save(); // Try and configure the status
                    $this->_order->save();

                    $model                  = $this->_paymentMethod;
                    $order_successful_email = $model->getConfigData( 'order_email' );

                    if ( $order_successful_email != '0' ) {
                        $this->OrderSender->send( $this->_order );
                        $this->_order->addStatusHistoryComment( __( 'Notified customer about order #%1.', $this->_order->getId() ) )->setIsCustomerNotified( true )->save();
                    }

                    // Capture invoice when payment is successfull
                    $invoice = $this->_invoiceService->prepareInvoice( $this->_order );
                    $invoice->setRequestedCaptureCase( \Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE );
                    $invoice->register();

                    // Save the invoice to the order
                    $transaction = $this->_objectManager->create( 'Magento\Framework\DB\Transaction' )
                        ->addObject( $invoice )
                        ->addObject( $invoice->getOrder() );

                    $transaction->save();

                    // Magento\Sales\Model\Order\Email\Sender\InvoiceSender
                    $send_invoice_email = $model->getConfigData( 'invoice_email' );
                    if ( $send_invoice_email != '0' ) {
                        $this->invoiceSender->send( $invoice );
                        $this->_order->addStatusHistoryComment( __( 'Notified customer about invoice #%1.', $invoice->getId() ) )->setIsCustomerNotified( true )->save();
                    }
                    $this->_redirect( 'checkout/onepage/success' );
                    break;
                case 2:
                    $this->messageManager->addNotice( 'Transaction has been declined.' );
                    $this->_order->registerCancellation( 'Redirect Response, Transaction has been declined.' )->save();
                    $this->_checkoutSession->restoreQuote();
                    $this->_redirect( 'checkout/cart' );
                    break;
            }

        } catch ( \Exception $e ) {
            $this->_logger->error( $pre . $e->getMessage() );
            $this->messageManager->addExceptionMessage( $e, __( 'We can\'t start PayGate Checkout.' ) );
            $this->_redirect( 'checkout/cart' );
        }

        return '';
    }

}
