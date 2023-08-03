<?php
namespace Firework\Firework\Plugin\Model\Widget;

use Magento\Framework\App\Request\Http;

class Instance
{
    /**
     * @var Http
     */
    protected $request;

    /**
     * @param Http $request
     */
    public function __construct(
        Http $request
    ) {
        $this->request = $request;
    }

    /**
     * Get item row html
     *
     * @param \Magento\Widget\Model\Widget\Instance $subject
     * @param array $result
     * @return string
     */
    public function afterGetWidgetParameters(
        \Magento\Widget\Model\Widget\Instance $subject,
        $result
    ) {

        if ($subject->getType() == \Firework\Firework\Block\Widget\Video::class &&
            isset($result['data']) &&
            is_array($result['data'])
        ) {
            $postData = $this->request->getPostValue();
            if ($postData) {
                $result['data'] = json_encode($result['data']);
            }
        }

        return $result;
    }
}
