<?php
/**
 * Created by Sublime.
 * User: Fateh
 * Date: 08/7/2018
 * Time: 1:30 PM
 */

namespace Vikas\ShipwayTracking\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Shipping\Model\Order\Track;


class ShipmentTrackSaveAfterObserver implements ObserverInterface {


    private $_scopeConfig = NULL;
    private $_messageManager  = NULL;

    public function __construct( 
    \Magento\Framework\App\Action\Context $context,
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    \Magento\Framework\Message\ManagerInterface $messageManager
        ){

    $this->_scopeConfig = $scopeConfig;
    $this->_messageManager  = $messageManager;

    }


    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $magentoTrack = $observer->getEvent()->getTrack();

        $magentoOrder = $magentoTrack->getShipment()->getOrder();

        $shipway_username = $this->_scopeConfig->getValue('shipway_tracking_section/general/text_shipway_username',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $shipway_licence_key = $this->_scopeConfig->getValue('shipway_tracking_section/general/text_shipway_licence_key',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);



        $data = array(
            'carrier_id' => '1',
            'order_id' => $magentoOrder->getIncrementId(),
            'awb' => $magentoTrack->getTrackNumber(),
            'username' => $shipway_username,
            'password' => $shipway_licence_key
        );



            $payment = $magentoOrder->getPayment();
            $method = $payment->getMethodInstance();
            $payment_code = $method->getCode();


            $payment = $magentoOrder->getPayment();
            $method = $payment->getMethodInstance();
            $payment_code = $method->getCode();

            $pos = strpos($payment_code, 'cash');
            $payment_type = 'P';
            if( strpos($payment_code, 'cash')){
                $payment_type = 'C';
            }
            $orderdetails['payment_type'] = $payment_type;
            $orderdetails['payment_method'] = $payment_code;
            $orderdetails['return_address'] = 'abc';
            
            $orderTotalValue = number_format ($magentoOrder->getGrandTotal(), 2, '.' , $thousands_sep = '');


            $orderdetails['collectable_amount'] = $orderTotalValue;
            $orderdetails['collectable_amount'] = ($payment_type == 'C') ? $orderTotalValue : 0;
            $ordered_items = $magentoOrder->getAllItems(); 
        
            $itms='';
            $products=array();
            $orderdetails=array();
            foreach($ordered_items as $item){   
                $products[]=array(
                'product_id'=> $item->getProductId(),
                'name'=> $item->getName(),
                'price'=> $item->getPrice(),
                'quantity'=> $item->getQtyOrdered(),
                'url'=> 'abc'
                );

                $itms .= $item->getName().' ';
            }

            $itms=substr($itms,0,35);
            $shipping_address = $magentoOrder->getShippingAddress();
            $orderdetails=array(
                'order_id' => $data['order_id'],
                'order_date' => $magentoOrder->getCreatedAt(),
                'firstname' => $shipping_address->getFirstname(),
                'lastname' =>$shipping_address->getLastname(),
                'email' => $shipping_address->getEmail(),
                'phone' => $shipping_address->getTelephone(),
                'address' => $shipping_address->getCity(),
                'city' => $shipping_address->getCity(),
                'state' => $shipping_address->getRegion(),
                'zipcode' => $shipping_address->getPostcode(),

                'country' => 'India',
                'products' =>  $products,  
                'amount' => $orderTotalValue,
                'payment_type' => $payment_type,
                'payment_method' => $payment_code,
                'collectable_amount' => $orderTotalValue,
                'return_address' => 'abc',
            );
            $company_name= $_SERVER['SERVER_NAME'];
            $data['first_name']     = $shipping_address->getFirstname();
            $data['last_name']      = $shipping_address->getLastname();
            $data['email']          = $shipping_address->getEmail();
            $data['phone']          = $shipping_address->getTelephone();
            $data['products']       = $itms;
            $data['company']     = $company_name;
            $data['order']   = $orderdetails;
    
            $url = "http://shipway.in/api/pushOrderData";

            $data_string = json_encode($data);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json'
            ));
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            $output = curl_exec($curl);
            $output = json_decode($output);
            curl_close($curl); 
            $result=(array)$output;

            if ($result['status'] == 'Failed ') {
                $this->_messageManager->addError($result['message']);

            } else {
                $this->_messageManager->addSuccess($result['message']);
            }
    }
}