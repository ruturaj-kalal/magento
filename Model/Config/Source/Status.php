<?php
namespace Firework\Firework\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Status implements ArrayInterface
{
    public const STATUS_DISABLE = 0;
    public const STATUS_ENABLE = 1;
    public const STATUS_PROCESSING = 2;
    public const STATUS_COMPLETED = 3;

    /**
     * Create value and label
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = [];
        foreach ($this->getOptions() as $value => $label) {
            $result[] = [
                 'value' => $value,
                 'label' => $label,
             ];
        }

        return $result;
    }

    /**
     * Create Label
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            self::STATUS_DISABLE => __('Disable'),
            self::STATUS_ENABLE => __('Enable'),
            self::STATUS_PROCESSING => __('Processing'),
            self::STATUS_COMPLETED => __('Complete'),
        ];
    }
}
