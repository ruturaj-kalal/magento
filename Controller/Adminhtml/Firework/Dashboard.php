<?php
namespace Firework\Firework\Controller\Adminhtml\Firework;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Firework\Firework\Helper\Data;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;
use Firework\Firework\Model\Integration;

class Dashboard extends Action
{
    /**
     * @var PageFactory
     */
    public $resultPageFactory;

    /**
     * @var Integration
     */
    public $integration;

    /**
     * @var Data
     */
    public $fireworkHelper;

    /**
     * @var RedirectFactory
     */
    public $resultRedirectFactory;

    /**
     * @param Context $context
     * @param Data $fireworkHelper
     * @param RedirectFactory $resultRedirectFactory
     * @param PageFactory $resultPageFactory
     * @param Integration $integration
     */
    public function __construct(
        Context $context,
        Data $fireworkHelper,
        RedirectFactory $resultRedirectFactory,
        PageFactory $resultPageFactory,
        Integration $integration
    ) {
        $this->fireworkHelper = $fireworkHelper;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->integration = $integration;
        parent::__construct($context);
    }

    /**
     * Firework Dashborad
     *
     * @return void
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultPage = $this->resultPageFactory->create();
        $storeInfo = $this->fireworkHelper->getScopeId();
        $this->integration->getIntegration();
        $resultPage->setActiveMenu('Firework_Firework::firework_dashboard');
        return $resultPage;
    }
}
