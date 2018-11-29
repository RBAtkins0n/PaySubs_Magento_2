<?php
/*
 * Copyright (c) 2018 PayGate (Pty) Ltd
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

        $page_object = $this->pageFactory->create();

        // Get the user session
        $this->_order = $this->_checkoutSession->getLastRealOrder();

        try
        {

            if ( isset( $_POST['p3'] ) ) {
                if ( !empty( $_POST['p3'] ) && strpos( $_POST['p3'], 'APPROVED' ) !== false ) {
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
                    
                    $order = $this->_order;
                    $model                  = $this->_paymentMethod;
                    $order_successful_email = $model->getConfigData( 'order_email' );

                    if ( $order_successful_email != '0' ) {
                        $this->OrderSender->send( $order );
                        $order->addStatusHistoryComment( __( 'Notified customer about order #%1.', $order->getId() ) )->setIsCustomerNotified( true )->save();
                    }

                    // Capture invoice when payment is successfull
                    $invoice = $this->_invoiceService->prepareInvoice( $order );
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
                        $order->addStatusHistoryComment( __( 'Notified customer about invoice #%1.', $invoice->getId() ) )->setIsCustomerNotified( true )->save();
                    }
                    
                    $this->_redirect( 'checkout/onepage/success' );
                    break;
                case 2:
                    $this->messageManager->addNotice( 'Transaction has been declined.' );
                    $this->_order->registerCancellation( 'Redirect Response, Transaction has been declined, Pay_Request_Id: ' . $_POST['PAY_REQUEST_ID'] )->save();
                    $this->_checkoutSession->restoreQuote();
                    $this->_redirect( 'checkout/cart' );
                    break;
            }

        } catch ( \Magento\Framework\Exception\LocalizedException $e ) {
            $this->_logger->error( $pre . $e->getMessage() );

            $this->messageManager->addExceptionMessage( $e, $e->getMessage() );
            $this->_redirect( 'checkout/cart' );
        } catch ( \Exception $e ) {
            $this->_logger->error( $pre . $e->getMessage() );
            $this->messageManager->addExceptionMessage( $e, __( 'We can\'t start PaySubs Checkout.' ) );
            $this->_redirect( 'checkout/cart' );
        }

        return '';
    }

}
