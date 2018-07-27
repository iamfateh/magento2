<?php 

/**
 * Created by Sublime.
 * User: Fateh
 * Date: 08/7/2018
 * Time: 1:30 PM
 */

 
namespace Vikas\ShipwayTracking\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Psr\Log\LoggerInterface as Logger;

class ConfigObserver implements ObserverInterface
{
    protected $logger;
    private $_storeManager = NULL;
    private $_productMetadata = NULL;
    private $_scopeConfig = NULL;
    private $_messageManager  = NULL;

    public function __construct(
        Logger $logger,    
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->logger = $logger;
        $this->_storeManager = $storeManager;
        $this->_productMetadata = $productMetadata;
        $this->_scopeConfig = $scopeConfig;
        $this->_messageManager  = $messageManager;
    }

    public function execute(EventObserver $observer)
    {
        $store_url = $this->_storeManager->getStore()->getBaseUrl();
        
        $store_code = $this->_storeManager->getStore()->getCode();
        
        $version = $this->_productMetadata->getVersion();
        
        $shipway_username = $this->_scopeConfig->getValue('shipway_tracking_section/general/text_shipway_username',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $shipway_licence_key = $this->_scopeConfig->getValue('shipway_tracking_section/general/text_shipway_licence_key',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        $request = array(
            'username' => $shipway_username,
            'password' => $shipway_licence_key,
            'group' => 'stores',
            'key' => 'magento2',
            'value' => array(
                'store_url'         => $store_url,
                'store_code'         => $store_code,
                'version'             => '2.2.3',
                'fetch_from_api'    => 1
            ),
        );

        $ch = curl_init();
        
        $options = array(
            CURLOPT_URL => 'https://shipway.in/api/updateUserSettings',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_POST    => true,
            CURLOPT_POSTFIELDS => json_encode($request),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array('Content-Type:application/json')
        );
        
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        
        if($curl_errno){
            //echo "Shipway settings are not saved.".$curl_error;
        }
        
        $response = json_decode($response,true);
        
        
        if(isset($response['status']) && $response['status'] == 'success'){
            $this->_messageManager->addSuccess('Shipway settings has been saved.');
        }else{
            $this->_messageManager->addError('Shipway settings not saved,Please contact to shipway administrator.');
        }
    }
}
?>