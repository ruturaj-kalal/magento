<?php
namespace Firework\Firework\Block\Adminhtml\Widget;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\Factory as ElementFactory;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Data extends \Magento\Backend\Block\Template
{
    /**
     * @var ElementFactory
     */
    protected $elementFactory;

    /**
     * @param Context $context
     * @param ElementFactory $elementFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        ElementFactory $elementFactory,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }
    
    /**
     * Prepare chooser element HTML
     *
     * @param AbstractElement $element Form Element
     * @return AbstractElement
     */
    public function prepareElementHtml(AbstractElement $element)
    {
        $element->setValue(json_encode($element->getValue()));

        $input = $this->elementFactory->create("textarea", ['data' => $element->getData()]);
        $input->setId($element->getId());
        $input->setForm($element->getForm());
        $input->setClass("widget-option input-textarea admin__control-text");
        $input->addClass('firework-widget-data hidden');
        if ($element->getRequired()) {
            $input->addClass('required-entry');
        }
        
        $html = $input->getElementHtml();
        $html .= $this->getLayout()
                ->createBlock(\Firework\Firework\Block\Adminhtml\Widget\Type::class)
                ->setElement($element)
                ->setTemplate("Firework_Firework::widget/type.phtml")
                ->toHtml();

        $element->setData('after_element_html', $html);
        $element->setValue(''); // Hides the additional label that gets added.
        
        return $element;
    }
}
