<?php
namespace Firework\Firework\Block\Adminhtml\Widget;

use Magento\Backend\Block\Template\Context;
use Firework\Firework\Helper\Data as FireworkHelper;

class Type extends \Magento\Backend\Block\Template
{
    /**
     * @var FireworkHelper
     */
    protected $fireworkHelper;

    /**
     * @var string
     */
    protected $channels = '{}';

    /**
     * @param Context $context
     * @param FireworkHelper $fireworkHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        FireworkHelper $fireworkHelper,
        array $data = []
    ) {
        $this->fireworkHelper = $fireworkHelper;
        parent::__construct($context, $data);
    }

    /**
     * Will return array of types
     *
     * @return array
     */
    public function getTypes()
    {
        $types = [
            [
                'label' => __('Story Block'),
                'value' => 'story_block'
            ],
            [
                'label' => __('Floating Player'),
                'value' => 'floating_player'
            ],
            [
                'label' => __('Grid'),
                'value' => 'grid'
            ],
            [
                'label' => __('Carausel'),
                'value' => 'carausel'
            ],
        ];

        return $types;
    }
    
    /**
     * Get channel and playlist data from API
     *
     * @return string
     */
    public function getChannelConfig()
    {
        $channelConfig = [];
        $business_id =  $this->fireworkHelper->getBussinessId();
        $accessToken = $this->fireworkHelper->getAccessToken();

        $query = 'query {
            business(id: "'.$business_id.'") {
                ... on Business {
                    id
                    owner {
                        id
                    }
                    name
                    channelsConnection(first: 10) {
                        pageInfo {
                            startCursor
                            endCursor
                            hasNextPage
                            hasPreviousPage
                        }
                        edges {
                            node {
                                id
                                name
                                country
                                locale
                                username
                                playlistsConnection(first: 10) {
                                    edges {
                                        node {
                                            id
                                            name
                                            displayName
                                            description
                                            videosConnection(first: 10) {
                                                edges {
                                                    node {
                                                        id
                                                        videoType
                                                        duration
                                                        thumbnailUrl
                                                        caption
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }';

        $data = ['query' => $query];
        $data = http_build_query($data);

        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer ' . $accessToken
        ];
        
        $options = [
            'http' => [
                'header'  => $headers,
                'method'  => 'POST',
                'content' => $data
            ]
        ];

        try {
            $context  = stream_context_create($options);
            $response = file_get_contents(FireworkHelper::ENDPOINT, false, $context);
            
            if ($response !== false) {
                return json_decode($response, 1);
            }
        } catch (\Exception $e) {
            return '';
        }

        return $channelConfig;
    }

    /**
     * Will return element values
     *
     * @return array
     */
    public function getElementValues()
    {
        $element = $this->getElement();
        $element->getValue();
        $data = str_replace(['[',']','`'], ['{','}','"'], html_entity_decode($element->getValue()));
        $values = json_decode(trim($data, '"'), 1);
        
        return $values;
    }
}
