<?php
namespace Firework\Firework\Controller\Adminhtml\System;

use Exception;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Firework\Firework\Helper\Data as FireworkHelper;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Call API and send OAuth data to API
 */
class ConnectButton extends Action implements HttpPostActionInterface
{
    private const JSON_RESPONSE = 'firework/general/json_response';
    private const REDIRECT_URIS = 'firework/general/redirect_uris';
    private const FIRST_API_URL = 'https://fireworktv.com/oauth/register';
    private const SECOND_API_URL = 'https://fireworktv.com/oauth/authorize';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    
    /**
     * @var Data
     */
    protected $jsonHelper;
    
    /**
     * @var ScopeConfigInterface
     */
    protected $configWriter;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var FireworkHelper
     */
    protected $fireworkHelper;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Json
     */
    protected $json;
    
    /**
     * Construct function called
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Data $jsonHelper
     * @param WriterInterface $configWriter
     * @param LoggerInterface $logger
     * @param FireworkHelper $fireworkHelper
     * @param Curl $curl
     * @param Json $json
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Data $jsonHelper,
        WriterInterface $configWriter,
        LoggerInterface $logger,
        FireworkHelper $fireworkHelper,
        Curl $curl,
        Json $json
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->fireworkHelper = $fireworkHelper;
        $this->curl = $curl;
        $this->json = $json;
        parent::__construct($context);
    }

    /**
     * Call API and return json response
     *
     * @return string
     */
    public function execute()
    {
        try {
            $resultRedirect = $this->resultPageFactory->create();
            $data = $this->getRequest()->getParams();
            $storeData = $this->fireworkHelper->getStoreAndWebsite($data);

            $callBackUrl = $this->fireworkHelper->getCallBackUrl(
                $storeData['scope_store'],
                $storeData['store_id']
            );
            
            $storeName = $this->fireworkHelper->getStoreName();
            $ownerEmail = $this->fireworkHelper->getOwnerEmail();

            /*  Check AccessToken,BussinessStoreId,OauthBussinessStoreId not exit.
                If AccessToken is expire create new AccessToken
            */
            if ((null != $this->fireworkHelper->getNewAccessToken()) &&
                (null != $this->fireworkHelper->getBussinessStoreId(
                    $storeData['scope_store'],
                    $storeData['store_id']
                )) &&
                (null != $this->fireworkHelper->getBussinessId($storeData['scope_store'], $storeData['store_id']))
                ) {
                if ($this->fireworkHelper->checkAccessTokenValid($this->fireworkHelper->getNewAccessToken())) {
                    $response['message'] = 'successUrl';
                    $response['redirectUrl'] = $this->fireworkHelper->getBackendUrl(
                        $storeData['scope_store'],
                        $storeData['store_id']
                    );
                    return $this->jsonResponse($response);
                }
            }
            
            // Start api calling
            $firstApiUrl = self::FIRST_API_URL;
            $data = [
                'client_name' => $storeName,
                'redirect_uris' => ["$callBackUrl"],
                'contacts' => ["$ownerEmail"],
                'scope' => 'openid'
            ];
            
            // Convert array to json
            $params = $this->getJsonEncode($data);

            $header = [
                "Content-Type" => "application/json"
            ];

            $this->curl->setHeaders($header);
            $this->curl->post($firstApiUrl, $params);
            $body = $this->curl->getBody();
            $response = $this->jsonHelper->jsonDecode($body);

            if (!isset($response['errors'])) {
                // $resultObjts = json_decode($response);
                $this->configWriter->save(
                    self::JSON_RESPONSE,
                    $body,
                    $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
                );
                $this->configWriter->save(
                    self::REDIRECT_URIS,
                    $response['redirect_uris'][0],
                    $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
                );

                $apiResultApi2 = $this->callSecondApi($response['client_id'], $response['redirect_uris'][0]);
                $this->fireworkHelper->flushCache();

                return $this->jsonResponse($apiResultApi2);
            } else {
                $this->messageManager->addError(
                    __("Something went wrong. Please contact to store owner.")
                );
            }
        } catch (Exception $e) {
            $this->messageManager->addError("Firework Service is not available.");
            $response['message'] = 'false';
            return $this->jsonResponse($response);
        }
    }

    /**
     * Call Get Method Second API
     *
     * @param [type] $clientId
     * @param [type] $redirectUrl
     * @return array
     */
    public function callSecondApi($clientId, $redirectUrl)
    {
        try {
            if (!empty($clientId) && !empty($redirectUrl)) {
                $response['message'] = 'success';
                $urlParam = "?client=business&business_onboard=true&response_type=code&redirect_uri=".
                $redirectUrl."&client_id=$clientId&state=STATE";
                $response['second_api_url'] = self::SECOND_API_URL . $urlParam;
                
            } else {
                $response['message'] = 'error';
            }
            return $response;
        } catch (\Exception $e) {
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create json response
     *
     * @param string $response
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }

    /**
     * Will return json array
     *
     * @param array $data
     * @return bool|false|string
     */
    public function getJsonEncode($data)
    {
        return $this->json->serialize($data); // it's same as like json_encode
    }

    /**
     * Will return array
     *
     * @param array $data
     * @return array|bool|float|int|mixed|string|null
     */
    public function getJsonDecode($data)
    {
        return $this->json->unserialize($data); // it's same as like json_decode
    }
}
