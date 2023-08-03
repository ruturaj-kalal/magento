<?php
namespace Firework\Firework\Controller\Adminhtml\Widget;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Framework\App\ObjectManager;

class LoadVideos extends \Magento\Backend\App\Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var \Firework\Firework\Helper\Data
     */
    protected $fireworkHelper;

    /**
     * @var LayoutFactory
     */
    protected $resultLayoutFactory;

    /**
     * @var ResultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Firework\Firework\Helper\Data $fireworkHelper
     * @param LayoutFactory $resultLayoutFactory
     * @param ResultJsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Firework\Firework\Helper\Data $fireworkHelper,
        LayoutFactory $resultLayoutFactory,
        ResultJsonFactory $resultJsonFactory
    ) {
        $this->fireworkHelper = $fireworkHelper;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Load All Widget Videos
     *
     * @return array
     */
    public function execute()
    {
        $resultLayout = $this->resultLayoutFactory->create();
        $html = $resultLayout->addHandle('firework_widget_videos')->getLayout()->getOutput();
        
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($html);
        return $resultJson;
    }
}
