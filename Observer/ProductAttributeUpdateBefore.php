<?php
namespace Firework\Firework\Observer;

use Magento\Framework\Event\ObserverInterface;
use Firework\Firework\Model\WebhookFactory;
use Firework\Firework\Model\Config\Source\Status;
use Firework\Firework\Helper\Webhook as WebhookHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class ProductAttributeUpdateBefore implements ObserverInterface
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
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param WebhookFactory $webhookFactory
     * @param WebhookHelper $webhookHelper
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        WebhookFactory $webhookFactory,
        WebhookHelper $webhookHelper,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        $this->webhookFactory = $webhookFactory;
        $this->webhookHelper = $webhookHelper;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * Product Attribute save before observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->webhookHelper->isEnable()) {
            return $this;
        }

        $productIds = $observer->getProductIds();
        if ($productIds) {
            foreach ($productIds as $id) {
                $product = $this->productRepository->getById($id);
                $data = [
                    'product_id' => $id,
                    'product_sku' => $product->getSku(),
                    'action_code' => 'update',
                    'status' => Status::STATUS_ENABLE
                ];

                try {
                    $log = $this->webhookFactory->create();
                    $log->setData($data);
                    $log->save();
                } catch (\Exception $e) {
                    $this->logger->critical($e->getMessage());
                }
            }
        }

        return $this;
    }
}
