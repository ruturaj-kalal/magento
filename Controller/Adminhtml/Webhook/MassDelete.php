<?php
namespace Firework\Firework\Controller\Adminhtml\Webhook;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Firework\Firework\Model\WebhookFactory;
use Firework\Firework\Model\ResourceModel\Webhook\CollectionFactory;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

class MassDelete extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * Massactions filter.​_
     * @var Filter
     */
    protected $filter;

    /**
     * @var WebhookFactory
     */
    protected $webhookFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param WebhookFactory $webhookFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        WebhookFactory $webhookFactory,
        CollectionFactory $collectionFactory
    ) {

        $this->filter = $filter;
        $this->webhookFactory = $webhookFactory;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

     /**
      * Will load a collection and process it
      *
      * @return object
      */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $count = $collection->getSize();
            foreach ($collection as $record) {
                $deleteItem = $this->webhookFactory->create()->load($record->getEntityId());
                $deleteItem->delete();
            }
            $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $count));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('firework/webhook/index');
    }
}
