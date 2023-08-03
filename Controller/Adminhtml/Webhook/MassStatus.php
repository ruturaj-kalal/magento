<?php
namespace Firework\Firework\Controller\Adminhtml\Webhook;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Firework\Firework\Model\WebhookFactory;
use Firework\Firework\Model\ResourceModel\Webhook\CollectionFactory;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

class MassStatus extends Action implements HttpPostActionInterface
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
     * Created construct object
     *
     * @param Context $context
     * @param Filter $filter
     * @param WebhookFactory $WebhookFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        WebhookFactory $WebhookFactory,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->WebhookFactory = $WebhookFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Will load collection and process it
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */

    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            foreach ($collection as $item) {
                $model = $this->WebhookFactory->create()->load($item['entity_id']);
                $model->setData('status', $this->getRequest()->getParam('status'));
                $model->save();
            }
            $this->messageManager->addSuccess(__('A total of %1 record(s) were updated.', $collection->getSize()));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}
