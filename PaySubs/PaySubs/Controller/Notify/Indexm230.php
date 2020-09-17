<?php
/*
 * Copyright (c) 2020 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

namespace PaySubs\PaySubs\Controller\Notify;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use PaySubs\PaySubs\Controller\AbstractPaySubs;

class Indexm230 extends AbstractPaySubs implements CsrfAwareActionInterface
{
    private $storeId;

    /**
     * indexAction
     */
    public function execute()
    {
        echo "OK";
        $errors       = false;
        $paysubs_data = array();

        $notify_data = array();
        $post_data   = '';
        // Get notify data
        if ( !$errors ) {
            $paysubs_data = $this->getPostData();
            if ( $paysubs_data === false ) {
                $errors = true;
                throw new Exception( 'Request doesn\'t contain POST elements.', 20 );
            }
        }

        if ( !$errors ) {
            if ( empty( $paysubs_data['m_3'] ) || strlen( $paysubs_data['m_3'] ) > 50 ) {
                $errors = true;
                throw new Exception( 'Missing or invalid order ID', 40 );
            }

            // Load order for further validation
            $orderId       = $paysubs_data['m_3'];
            $this->_order  = $this->_orderFactory->create()->loadByIncrementId( $orderId );
            $this->storeId = $this->_order->getStoreId();

            // Check transaction password
            if ( $this->_paymentInst->getConfigData( 'pam' ) ) {
                if ( $this->_paymentInst->getConfigData( 'pam' ) != $paysubs_data['pam'] ) {
                    $errors = true;
                    throw new Exception( 'Transaction password wrong' );
                }
                if ( $paysubs_data['m_1'] != md5( $paysubs_data['pam'] . '::' . $paysubs_data['p2'] ) ) {
                    $errors = true;
                    throw new Exception( 'Checksum mismatch' );
                }
            }
            // Check transaction amount
            if ( number_format( $this->_order->getBaseGrandTotal(), 2, '.', '' ) != $paysubs_data['p6'] ) {
                $errors = true;
                throw new Exception( 'Transaction amount doesn\'t match.' );
            }
            // Check transaction status
            if ( !empty( $paysubs_data['p3'] ) && substr( $paysubs_data['p3'], 6, 8 ) != 'APPROVED' ) {
                $errors = true;
                $this->_order->setStatus( \Magento\Sales\Model\Order::STATE_CANCELED );
                $this->_order->save();
                $paysubs_paymentstatus = '';
                if ( !empty( $paysubs_data['p3'] ) ) {
                    $paysubs_paymentstatus = substr( $paysubs_data['p3'], 6, 8 );
                }
                $this->_order->addStatusHistoryComment( "Notify Response, The transaction was declined due to: " . $paysubs_paymentstatus, \Magento\Sales\Model\Order::STATE_PROCESSING )->setIsCustomerNotified( false )->save();
                throw new Exception( 'Transaction was not successfull.' );
            } else {
                if ( !$errors == true ) {
                    $this->_order->setStatus( \Magento\Sales\Model\Order::STATE_PROCESSING );
                    $this->_order->save();
                } else {
                    throw new Exception( 'Transaction was not successfull.' );
                }
            }
        }
    }

    // Retrieve post data
    public function getPostData()
    {
        // Posted variables from ITN
        $nData = $_POST;

        // Strip any slashes in data
        foreach ( $nData as $key => $val ) {
            $nData[$key] = stripslashes( $val );
        }

        // Return "false" if no data was received
        if ( sizeof( $nData ) == 0 ) {
            return ( false );
        } else {
            return ( $nData );
        }

    }

    /**
     * saveInvoice
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function saveInvoice()
    {
        paysubslog( 'Saving invoice' );

        // Check for mail msg
        $invoice = $this->_order->prepareInvoice();

        $invoice->register()->capture();

        /**
         * @var \Magento\Framework\DB\Transaction $transaction
         */
        $transaction = $this->_transactionFactory->create();
        $transaction->addObject( $invoice )
            ->addObject( $invoice->getOrder() )
            ->save();

        $this->_order->addStatusHistoryComment( __( 'Notified customer about invoice #%1.', $invoice->getIncrementId() ) );
        $this->_order->setIsCustomerNotified( true );
        $this->_order->save();
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException( RequestInterface $request ):  ? InvalidRequestException
    {
        // TODO: Implement createCsrfValidationException() method.
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf( RequestInterface $request ) :  ? bool
    {
        return true;
    }
}
