<?php
namespace Firework\Firework\Observer;

use Magento\Framework\Event\ObserverInterface;
use Firework\Firework\Model\WebhookFactory;
use Firework\Firework\Model\Config\Source\Status;
use Firework\Firework\Helper\Webhook as WebhookHelper;
use Psr\Log\LoggerInterface;

class ProductDeleteAfter implements ObserverInterface
{
    /**
     * @var WebhookFactory
     */
    protected $webhookFactory;

    /**
     * @var WebhookHelper
     */
    protected $webhookHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param WebhookFactory $webhookFactory
     * @param WebhookHelper $webhookHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        WebhookFactory $webhookFactory,
        WebhookHelper $webhookHelper,
        LoggerInterface $logger
    ) {
        $this->webhookFactory = $webhookFactory;
        $this->webhookHelper = $webhookHelper;
        $this->logger = $logger;
    }

    /**
     * Product delete Observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->webhookHelper->isEnable()) {
            return $this;
        }

        try {
            $product = $observer->getProduct();

            $data=[
                'product_id' => $product->getId(),
                'product_sku' => $product->getSku(),
                'action_code' => 'delete',
                'status' => Status::STATUS_ENABLE
            ];

            $log = $this->webhookFactory->create();
            $log->setData($data);
            $log->save();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
        return $this;
    }
}
