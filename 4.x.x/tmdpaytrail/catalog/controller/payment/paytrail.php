<?php

namespace Opencart\Catalog\Controller\Extension\Tmdpaytrail\Payment;
use \Opencart\System\Helper as Helper;
require_once (DIR_EXTENSION.'/tmdpaytrail/system/library/tmd/paytrail/autoload.php');
class Paytrail extends \Opencart\System\Engine\Controller {
	public function index() {
     $ACCOUNT = $this->config->get('payment_paytrail_account_no');
     $SECRET  = $this->config->get('payment_paytrail_secret_key');
   
     $METHOD  = 'POST';


    $this->load->model('checkout/order');
	$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
	 
	$order_product_info   = $this->model_checkout_order->getProducts($this->session->data['order_id']);
	$items = [];
	$total=0;
	 foreach ($order_product_info as $value) {
     
      // Convert the price to EUR
     $priceInEUR = round($this->currency->convert($value['price']*100, $this->config->get('config_currency'), 'EUR'));
     $total     += $priceInEUR*$value['quantity'];

	 $items[] =[
        'unitPrice'     => $priceInEUR,
        'units'         => (int)$value['quantity'],
        'vatPercentage' => 0,
        'productCode'   => $value['model'],
        'deliveryDate'  => date('Y-m-d')

	 	];
	
	
	 }
   
  $ordertotals = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE  order_id='".$this->session->data['order_id']."'");
	  foreach($ordertotals->rows as $ordertotal){
	  if($ordertotal['code']!='total' &&   $ordertotal['code']!='sub_total'){
	  $shippriceInEUR = round($this->currency->convert($ordertotal['value']*100, $this->config->get('config_currency'), 'EUR'));
		
	  $total +=$shippriceInEUR;
	  $items[]=array(
			'unitPrice'=>$shippriceInEUR,
			'units'=>1,
			'vatPercentage'=>0,
			'productCode'=>$ordertotal['code'],
			'deliveryDate'=>date('Y-m-d'),
		 );
	  }	
	  }

	  
   $checkoutTimestamp = gmdate('Y-m-d\TH:i:s.v\Z');
   $checkoutNonce     = bin2hex(random_bytes(16)); // 32 characters long

    $headers = array(
    'checkout-account'   => $ACCOUNT,
    'checkout-algorithm' => 'sha256',
    'checkout-method'    => $METHOD,
    'checkout-nonce'     => $checkoutNonce,
    'checkout-timestamp' => $checkoutTimestamp,
    'content-type' => 'application/json; charset=utf-8'
    );

		
   $body = json_encode(        
        [
        'stamp'     =>  uniqid(),
        'reference' => $order_info['order_id'],
        'amount'    =>  $total,
        'currency'  => 'EUR',
        'language'  => 'FI',
        'items'     => $items,
        'customer'  => [
            'email' => $order_info['email']
        ],
        'redirectUrls' => [
            'success' => $this->url->link('extension/tmdpaytrail/payment/paytrail.callback'),
            'cancel'  => $this->url->link('checkout/checkout')
        ]
    ],
    JSON_UNESCAPED_SLASHES
  );



	// string(64) "9a4a7735279de4c99268e4566a5526ae887e73e6e58f2918cb2309ccac366129"
	$headers['signature'] = $this->calculateHmac($SECRET, $headers, $body);

	$client = new \GuzzleHttp\Client([ 'headers' => $headers ]);
	$response = null;
	try {
	    $response = $client->post('https://services.paytrail.com/payments', [ 'body' => $body ]);
	} catch (\GuzzleHttp\Exception\ClientException $e) {
	    if ($e->hasResponse()) {
	        $response = $e->getResponse();
	       $data['warning'] =  "Unexpected HTTP status code: {$response->getStatusCode()}\n\n";
	    }
	}

	$responseBody = $response->getBody()->getContents();
	// Flatten Guzzle response headers
	$responseHeaders = array_column(array_map(function ($key, $value) {
	    return [ $key, $value[0] ];
	}, array_keys($response->getHeaders()), array_values($response->getHeaders())), 1, 0);

	$responseHmac = $this->calculateHmac($SECRET, $responseHeaders, $responseBody);
	if (!empty($response->getHeader('signature')[0]) && $responseHmac !== $response->getHeader('signature')[0]) {
	    $data['warning'] = "Response HMAC signature mismatch!";
	} else {
	    //echo(json_encode(json_decode($responseBody), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

	     $paymentdata=json_decode($responseBody,true);
		if(!empty($paymentdata['status']) && $paymentdata['status']=='error'){
			 $data['warning']= $paymentdata['message'];
		}else{
		     $data['href']=$paymentdata['href'];
		}
	}
	


		return $this->load->view('extension/tmdpaytrail/payment/paytrail',$data);
	}


   public function calculateHmac($secret, $params, $body = ''){
    // Keep only checkout- params, more relevant for response validation. Filter query
    // string parameters the same way - the signature includes only checkout- values.
    $includedKeys = array_filter(array_keys($params), function ($key) {
        return preg_match('/^checkout-/', $key);
    });

    // Keys must be sorted alphabetically
    sort($includedKeys, SORT_STRING);

    $hmacPayload =
        array_map(
            function ($key) use ($params) {
                return join(':', [ $key, $params[$key] ]);
            },
            $includedKeys
        );

    array_push($hmacPayload, $body);

    return hash_hmac('sha256', join("\n", $hmacPayload), $secret);
   }


    public function callback() {
		
		$this->load->model('checkout/order');
		if(!empty($this->request->get['checkout-status'])){
		if($this->request->get['checkout-status'] == 'fail') {
		
			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_paytrail_order_statusfailed_id'));
			$this->response->redirect($this->url->link('checkout/checkout'));
			
		}else{
			
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
			$ordertotal = $this->currency->convert($order_info['total']*100, $this->config->get('config_currency'), 'EUR');
			if($ordertotal==$this->request->get['checkout-amount']){
			   $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_paytrail_order_status_id'));
			}
			
		}
		}
		
		$this->response->redirect($this->url->link('checkout/checkout'));
		
			
	}
	
}
