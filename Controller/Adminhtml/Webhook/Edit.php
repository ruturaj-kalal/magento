<?php
namespace Firework\Firework\Controller\Adminhtml\Webhook;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Edit extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
   /**
    * @var \Magento\Framework\Registry
    */
    private $coreRegistry;

    /**
     * @var \Firework\Firework\Model\WebhookFactory
     */
    private $webhookFactory;

    /**
     * Created a construct
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Firework\Firework\Model\WebhookFactory $webhookFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Firework\Firework\Model\WebhookFactory $webhookFactory
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->webhookFactory = $webhookFactory;
    }
    
    /**
     * Will load webhook data and process it
     *
     * @return object
     */
    public function execute()
    {
        $rowId = (int) $this->getRequest()->getParam('entity_id');
        $webhookData = $this->webhookFactory->create();
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        if ($rowId) {
            $webhookData = $webhookData->load($rowId);
            $rowTitle = $webhookData->getTitle();
            if (!$webhookData->getEntityId()) {
                $this->messageManager->addError(__('row data no longer exist.'));
                $this->_redirect('firework/webhook/index');
                return;
            }
        }

        $this->coreRegistry->register('webhook_data', $webhookData);
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $title = $rowId ? __('Edit Row Data ').$rowTitle : __('Add Row Data');
        $resultPage->getConfig()->getTitle()->prepend($title);
        return $resultPage;
    }
}
