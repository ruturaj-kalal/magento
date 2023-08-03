<?php
namespace Firework\Firework\Block\Adminhtml\Widget;

use Magento\Backend\Block\Template\Context;
use Firework\Firework\Helper\Data as FireworkHelper;

class Videos extends \Magento\Backend\Block\Template
{
    /**
     * @var FireworkHelper
     */
    protected $fireworkHelper;

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
     * Get Channel Id
     *
     * @return string
     */
    public function getChannel()
    {
        return $this->getRequest()->getParam('channel_id');
    }

    /**
     * Get Play list Id
     *
     * @return string
     */
    public function getPlaylist()
    {
        return $this->getRequest()->getParam('playlist_id');
    }

    /**
     * Get Selected Video Id
     *
     * @return string
     */
    public function getSelectedVideo()
    {
        return $this->getRequest()->getParam('video_id');
    }

    /**
     * Get Videos
     *
     * @return array
     */
    public function getVideos()
    {
        $videos = [];
        $businessId = $this->fireworkHelper->getBussinessId();
        $channelId = $this->getChannel();
        if ($businessId && $channelId) {
            $endpoint = FireworkHelper::BUSAPI . "/$businessId/videos?channel_id=$channelId";
            $playlistId = $this->getPlaylist();
            if ($playlistId && $playlistId != "all" && $playlistId != "specific") {
                $endpoint = FireworkHelper::BUSAPI . "/$businessId/channels/$channelId/playlists/$playlistId/videos";
            }

            $business_id =  $this->fireworkHelper->getBussinessId();
            $accessToken = $this->fireworkHelper->getAccessToken();

            $headers = [
                'Authorization: Bearer ' . $accessToken
            ];
            $options = [
                'http' => [
                    'header'  => $headers,
                    'method'  => 'GET',
                ]
            ];
            
            try {
                $context  = stream_context_create($options);
                $response = file_get_contents($endpoint, false, $context);
                if ($response !== false) {
                    $result = json_decode($response, 1);
                    if (isset($result['videos']) && count($result['videos']) > 0) {
                        $videos = $result['videos'];
                    }
                }
            } catch (\Exception $e) {
                return '';
            }
        }

        return $videos;
    }
}
