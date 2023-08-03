<?php
namespace Firework\Firework\Controller\Adminhtml\Webhook;

class Save extends \Magento\Backend\App\Action
{
    /**
     * @var \Firework\Firework\Model\WebhookFactory
     */
    protected $webhookFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Firework\Firework\Model\WebhookFactory $webhookFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Firework\Firework\Model\WebhookFactory $webhookFactory
    ) {
        parent::__construct($context);
        $this->webhookFactory = $webhookFactory;
    }

    /**
     * Will chec and redirect to router page
     *
     * @return void
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            $this->_redirect('firework/webhook/edit');
            return;
        }
        try {
            $rowData = $this->webhookFactory->create();
            $rowData->setData($data);
            $rowData->save();
            $this->messageManager->addSuccess(__('Data has been successfully saved.'));

            if ($this->getRequest()->getParam('back')) {
                return $this->_redirect('*/*/edit', ['entity_id' => $rowData->getEntityId(), '_current' => true]);
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        
        $this->_redirect('firework/webhook/index');
    }
}
