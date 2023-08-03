<?php
namespace Firework\Firework\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Firework\Firework\Model\WebhookFactory;
use Firework\Firework\Model\Config\Source\Status;
use Firework\Firework\Helper\Webhook as WebhookHelper;
use Psr\Log\LoggerInterface;

class ProductSaveAfter implements ObserverInterface
{
    /**
     * @var HttpRequest
     */
    protected $request;

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
     * @param HttpRequest $request
     * @param WebhookFactory $webhookFactory
     * @param WebhookHelper $webhookHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        HttpRequest $request,
        WebhookFactory $webhookFactory,
        WebhookHelper $webhookHelper,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->webhookFactory = $webhookFactory;
        $this->webhookHelper = $webhookHelper;
        $this->logger = $logger;
    }

    /**
     * Product save after observer
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
            if ($this->request->getParam('id') && $this->request->getParam('id') == $product->getId()) {
                $data=[
                    'product_id' => $product->getId(),
                    'product_sku' => $product->getSku(),
                    'action_code' => 'update',
                    'status' => Status::STATUS_ENABLE
                ];
            }
            $log = $this->webhookFactory->create();
            $log->setData($data);
            $log->save();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return $this;
    }
}
