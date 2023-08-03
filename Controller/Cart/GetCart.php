<?php

namespace Firework\Firework\Controller\Cart;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\CustomerData\Cart as CustomerCart;

class GetCart extends Action implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CustomerCart
     */
    protected $customerCart;

    /**
     * Created a construct object
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CustomerCart $customerCart
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CustomerCart $customerCart
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerCart = $customerCart;
        return parent::__construct($context);
    }

    /**
     * Return Json encode cart response
     *
     * @return void
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        try {
            $result = $this->customerCart->getSectionData();
        } catch (\Exception $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        return $resultJson->setData($result);
    }
}
