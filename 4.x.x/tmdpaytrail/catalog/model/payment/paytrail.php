<?php

namespace Opencart\Catalog\Model\Extension\Tmdpaytrail\Payment;
use \Opencart\System\Helper as Helper;
class Paytrail extends \Opencart\System\Engine\Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/tmdpaytrail/payment/paytrail');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_paytrail_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('payment_paytrail_total') > 0 && $this->config->get('payment_paytrail_total') > $total) {
			$status = false;
		} elseif (!$this->cart->hasShipping()) {
			$status = false;
		} elseif (!$this->config->get('payment_paytrail_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

        $titletext  =  $this->config->get('payment_paytrail');
        if(!empty($titletext[$this->config->get('config_language_id')]['title'])){
          $title = $titletext[$this->config->get('config_language_id')]['title'];
        }else{
          $title = $this->language->get('text_title');
        }

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'paytrail',
				'title'      => $title,
				'terms'      => '',
				'sort_order' => $this->config->get('payment_paytrail_sort_order')
			);
		}

		return $method_data;
	}


	public function getMethods(array $address = []): array {
		$this->language->load('extension/tmdpaytrail/payment/paytrail');

		if ($this->cart->hasSubscription()) {
			$status = false;
		} elseif (!$this->config->get('payment_paytrail_geo_zone_id')) {
			$status = true;
		} else{
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_paytrail_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");
			if ($query->num_rows) {
				$status = true;
			} else {
				$status = false;
			}
		}
        
         $titletext  =  $this->config->get('payment_paytrail');
        if(!empty($titletext[$this->config->get('config_language_id')]['title'])){
          $title = $titletext[$this->config->get('config_language_id')]['title'];
        }else{
          $title = $this->language->get('text_title');
        }
        
		$method_data = [];

		if ($status) {
			$option_data['paytrail'] = [
				'code' => 'paytrail.paytrail',
				'name' => $title
			];

			$method_data = [
				'code'       => 'paytrail',
				'name'       => $title,
				'option'     => $option_data,
				'sort_order' => $this->config->get('payment_paytrail_sort_order')
			];
		}

		return $method_data;
  	}

}
