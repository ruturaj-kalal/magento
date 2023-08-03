<?php
namespace Firework\Firework\Block\Checkout;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Checkout\Model\Session;
use Firework\Firework\Helper\Data;
 
class Analytics extends Template
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Data
     */
    protected $dataHelper;
    
    /**
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param Session $checkoutSession
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        OrderRepositoryInterface $orderRepository,
        Session $checkoutSession,
        Data $dataHelper,
        array $data = []
    ) {
        $this->orderRepository = $orderRepository;
        $this->checkoutSession = $checkoutSession;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get Order
     *
     * @return OrderRepositoryInterface|bool
     */
    public function getOrder()
    {
        if (!$this->dataHelper->isTrackingEnabled()) {
            return false;
        }

        $orderId = $this->checkoutSession->getLastOrderId();
        if ($orderId) {
            try {
                $order = $this->orderRepository->get($orderId);
                return $order;
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }
}
