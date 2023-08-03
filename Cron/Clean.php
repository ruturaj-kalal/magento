<?php
namespace Firework\Firework\Cron;

use Firework\Firework\Model\Config\Source\Status;
use Firework\Firework\Model\WebhookFactory;
use Firework\Firework\Helper\Webhook as WebhookHelper;

class Clean
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
     * @param WebhookFactory $webhookFactory
     * @param WebhookHelper $webhookHelper
     */
    public function __construct(
        WebhookFactory $webhookFactory,
        WebhookHelper $webhookHelper
    ) {
        $this->webhookFactory = $webhookFactory;
        $this->webhookHelper = $webhookHelper;
    }

    /**
     * Clean Cron
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->webhookHelper->isEnable()) {
            return $this;
        }
        $date = new \DateTime('-'.$this->webhookHelper->getLogCleanDays().'day');
        $collection = $this->webhookFactory->create()->getCollection()
                            ->addFieldToFilter('status', Status::STATUS_COMPLETED)
                            ->addFieldToFilter('updated_at', ['lteq' => $date->format('Y-m-d H:i:s')]);
        if ($collection->getSize()) {
            try {
                foreach ($collection as $key => $log) {
                    $item = $this->webhookFactory->create()->load($log->getId());
                    $item->delete();
                }
            } catch (\Exception $e) {
                return '';
            }
        }
           
        return $this;
    }
}
