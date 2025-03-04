<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bc extends CI_Controller {
	public function index($code = null) {
		echo 'boom!';
	}
 
	public function code128($sym='', $stream=false) {
		$this->load->helper('barcode');
		barcode_create($sym, $stream);
	}

	public function code39($sym='', $stream=false) {
		$this->load->helper('barcode');
		barcode_create($sym, $stream, 'code39');
	}

	public function qrcode($sym='', $stream=false) {
		$this->load->helper('qrcode');

		qrcode_create($sym, $stream);
	}
}