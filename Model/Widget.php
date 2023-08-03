<?php
namespace Firework\Firework\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Widget\Helper\Conditions;
use Magento\Widget\Model\Config\Data;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Asset\Source;
use Magento\Framework\View\FileSystem;
use Magento\Framework\App\Request\Http;

class Widget extends \Magento\Widget\Model\Widget
{

    /**
     * @var Data
     */
    protected $dataStorage;

    /**
     * @var Config
     */
    protected $configCacheType;

    /**
     * @var Repository
     */
    protected $assetRepo;

    /**
     * @var Source
     */
    protected $assetSource;

    /**
     * @var FileSystem
     */
    protected $viewFileSystem;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var Conditions
     */
    protected $conditionsHelper;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var string[]
     */
    private $reservedChars = ['}', '{'];

    /**
     * @param Escaper $escaper
     * @param Conditions $conditionsHelper
     * @param Data $dataStorage
     * @param Repository $assetRepo
     * @param Source $assetSource
     * @param FileSystem $viewFileSystem
     * @param Http $request
     */
    public function __construct(
        Escaper $escaper,
        Conditions $conditionsHelper,
        Data $dataStorage,
        Repository $assetRepo,
        Source $assetSource,
        FileSystem $viewFileSystem,
        Http $request
    ) {
        $this->escaper = $escaper;
        $this->conditionsHelper = $conditionsHelper;
        $this->request = $request;
        parent::__construct(
            $escaper,
            $dataStorage,
            $assetRepo,
            $assetSource,
            $viewFileSystem,
            $conditionsHelper
        );
    }

    /**
     * Return widget presentation code in WYSIWYG editor
     *
     * @param string $type Widget Type
     * @param array $params Pre-configured Widget Params
     * @param bool $asIs Return result as widget directive(true) or as placeholder image(false)
     * @return string Widget directive ready to parse
     */
    public function getWidgetDeclaration($type, $params = [], $asIs = true)
    {
        $widget = $this->getConfigAsObject($type);

        $params = array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        });

        $directiveParams = '';
        foreach ($params as $name => $value) {
            // Retrieve default option value if pre-configured
            $directiveParams .= $this->getDirectiveParam($widget, $name, $value);
        }

        $directive = sprintf('{{widget type="%s"%s%s}}', $type, $directiveParams, $this->getWidgetPageVarName($params));

        if ($asIs) {
            return $directive;
        }

        return sprintf(
            '<img id="%s" src="%s" title="%s">',
            $this->idEncode($directive),
            $this->getPlaceholderImageUrl($type),
            $this->escaper->escapeUrl($directive)
        );
    }

    /**
     * Returns directive param with prepared value
     *
     * @param DataObject $widget
     * @param string $name
     * @param string|array $value
     * @return string
     */
    private function getDirectiveParam(DataObject $widget, string $name, $value): string
    {
        $isFireworkWidget = false;
        if ($name === 'conditions') {
            $name = 'conditions_encoded';
            $value = $this->conditionsHelper->encode($value);
        } elseif (is_array($value)) {
            if ($widget->getType() == \Firework\Firework\Block\Widget\Video::class && $this->request->getPostValue()) {
                $data = $value;
                $data['settings'] = [];
                if (isset($value['settings'][$data['type']])) {
                    $data['settings'][$data['type']] = $value['settings'][$data['type']];
                }
                $value = json_encode($data);
                $isFireworkWidget = true;
            } else {
                $value = implode(',', $value);
            }
        } elseif (trim($value) === '') {
            $parameters = $widget->getParameters();
            if (isset($parameters[$name]) && is_object($parameters[$name])) {
                $value = $parameters[$name]->getValue();
            }
        } else {
            $value = $this->getPreparedValue($value);
        }

        if ($isFireworkWidget) {
            return sprintf(' %s="%s"', $name, str_replace(['{','}','"'], ['[',']','`'], $value));
        }
        return sprintf(' %s="%s"', $name, $this->escaper->escapeHtmlAttr($value, false));
    }

    /**
     * Get widget page varname
     *
     * @param array $params
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getWidgetPageVarName($params = [])
    {
        $pageVarName = '';
        if (array_key_exists('show_pager', $params) && (bool)$params['show_pager']) {
            $pageVarName = sprintf(
                ' %s="%s"',
                'page_var_name',
                'p' . $this->getMathRandom()->getRandomString(5, \Magento\Framework\Math\Random::CHARS_LOWERS)
            );
        }
        return $pageVarName;
    }

    /**
     * Returns encoded value if it contains reserved chars
     *
     * @param string $value
     * @return string
     */
    private function getPreparedValue(string $value): string
    {
        $pattern = sprintf('/%s/', implode('|', $this->reservedChars));

        return preg_match($pattern, $value) ? rawurlencode($value) : $value;
    }
}
