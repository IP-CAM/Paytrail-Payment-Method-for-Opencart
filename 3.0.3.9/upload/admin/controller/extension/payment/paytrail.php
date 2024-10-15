<?php
//lib
require_once(DIR_SYSTEM.'library/tmd/system.php');
//lib
class ControllerExtensionPaymentPaytrail extends Controller {
	private $error = array();

	public function index() {
		
		$this->registry->set('tmd', new TMD($this->registry));
		$keydata=array(
		'code'=>'tmdkey_paytrail',
		'eid'=>'NDY1ODA=',
		'route'=>'extension/payment/paytrail',
		);
		$paytrail=$this->tmd->getkey($keydata['code']);
		$data['getkeyform']=$this->tmd->loadkeyform($keydata);
		
		$this->load->language('extension/payment/paytrail');

		$this->document->setTitle($this->language->get('heading_title1'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_paytrail', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->session->data['warning'])) {
			$data['error_warning'] = $this->session->data['warning'];
		
			unset($this->session->data['warning']);
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/paytrail', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/paytrail', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);


		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();


		if (isset($this->request->post['payment_paytrail'])) {
			$data['payment_paytrail'] = $this->request->post['payment_paytrail'];
		} else {
			$data['payment_paytrail'] = $this->config->get('payment_paytrail');
		}

		if (isset($this->request->post['payment_paytrail_total'])) {
			$data['payment_paytrail_total'] = $this->request->post['payment_paytrail_total'];
		} else {
			$data['payment_paytrail_total'] = $this->config->get('payment_paytrail_total');
		}

		if (isset($this->request->post['payment_paytrail_order_status_id'])) {
			$data['payment_paytrail_order_status_id'] = $this->request->post['payment_paytrail_order_status_id'];
		} else {
			$data['payment_paytrail_order_status_id'] = $this->config->get('payment_paytrail_order_status_id');
		}


        if (isset($this->request->post['payment_paytrail_order_statusfailed_id'])) {
			$data['payment_paytrail_order_statusfailed_id'] = $this->request->post['payment_paytrail_order_statusfailed_id'];
		} else {
			$data['payment_paytrail_order_statusfailed_id'] = $this->config->get('payment_paytrail_order_statusfailed_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_paytrail_geo_zone_id'])) {
			$data['payment_paytrail_geo_zone_id'] = $this->request->post['payment_paytrail_geo_zone_id'];
		} else {
			$data['payment_paytrail_geo_zone_id'] = $this->config->get('payment_paytrail_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_paytrail_status'])) {
			$data['payment_paytrail_status'] = $this->request->post['payment_paytrail_status'];
		} else {
			$data['payment_paytrail_status'] = $this->config->get('payment_paytrail_status');
		}

		if (isset($this->request->post['payment_paytrail_sort_order'])) {
			$data['payment_paytrail_sort_order'] = $this->request->post['payment_paytrail_sort_order'];
		} else {
			$data['payment_paytrail_sort_order'] = $this->config->get('payment_paytrail_sort_order');
		}

		if (isset($this->request->post['payment_paytrail_secret_key'])) {
			$data['payment_paytrail_secret_key'] = $this->request->post['payment_paytrail_secret_key'];
		} else {
			$data['payment_paytrail_secret_key'] = $this->config->get('payment_paytrail_secret_key');
		}	


		if (isset($this->request->post['payment_paytrail_account_no'])) {
			$data['payment_paytrail_account_no'] = $this->request->post['payment_paytrail_account_no'];
		} else {
			$data['payment_paytrail_account_no'] = $this->config->get('payment_paytrail_account_no');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/paytrail', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/paytrail')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		$paytrail=$this->config->get('tmdkey_paytrail');
		if (empty(trim($paytrail))) {			
		$this->session->data['warning'] ='Module will Work after add License key!';
		$this->response->redirect($this->url->link('extension/payment/paytrail', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		return !$this->error;
	}
	public function keysubmit() {
		$json = array(); 
		
      	if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
			$keydata=array(
			'code'=>'tmdkey_paytrail',
			'eid'=>'NDY1ODA=',
			'route'=>'extension/payment/paytrail',
			'moduledata_key'=>$this->request->post['moduledata_key'],
			);
			$this->registry->set('tmd', new TMD($this->registry));
            $json=$this->tmd->matchkey($keydata);       
		} 
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}