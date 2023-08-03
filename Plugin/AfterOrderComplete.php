<?php

namespace Firework\Firework\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

class AfterOrderComplete
{
    protected $messageManager;
    protected $_invoiceService;
    protected $_transactionFactory;
    protected $invoiceSender;
    protected $_request;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        InvoiceSender $invoiceSender,
        \Magento\Framework\Webapi\Rest\Request $request
    ) {
        $this->messageManager     = $messageManager;
        $this->_invoiceService = $invoiceService;
        $this->_transactionFactory = $transactionFactory;
        $this->invoiceSender = $invoiceSender;
        $this->_request = $request;
    }

    public function afterPlace(
        OrderManagementInterface $subject,
        OrderInterface $order
    ) {
        $orderId = $order->getId();
        if ($orderId) {
            try {
                  
                if ($this->_request->getParam('comment_note') != '') {
                    $order->addStatusHistoryComment(
                        __($this->_request->getParam('comment_note'))
                    )
                        ->setIsCustomerNotified(true)
                        ->save();
                }
                if ($order->getPayment()->getMethod() == 'firework') {
                    if (!$order->canInvoice()) {
                        return null;
                    }
                    if (!$order->getState() == 'new') {
                        return null;
                    }
                    
                    $invoice = $this->_invoiceService->prepareInvoice($order);
                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                    $invoice->register();
                    $invoice->getOrder()->setIsInProcess(true);
                    $transaction = $this->_transactionFactory->create()
                      ->addObject($invoice)
                      ->addObject($invoice->getOrder());
        
                    $transaction->save();
                    $this->invoiceSender->send($invoice);
                    //Send Invoice mail to customer
                    if ($this->_request->getParam('comment_note') != '') {
                        $order->addCommentToStatusHistory($this->_request->getParam('comment_note'), false, true);
                    }
                    
                    $order->addStatusHistoryComment(
                        __('Notified customer about invoice creation #%1.', $invoice->getId())
                    )
                        ->setIsCustomerNotified(true)
                        ->save();
                }
                
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Your transaction was successful.'));
            }
        }
        return $order;
    }
}
