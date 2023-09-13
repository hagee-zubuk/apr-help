<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pdf extends CI_Controller {
	public function index($code = null) {
		echo 'blam!';
	}
 
	public function t($txn='', $save=TRUE) {
		//error_reporting(E_ERROR);

		if ($txn == '') {
			$txn = $this->input->get_post('txn', FALSE);
		}
		if ($txn == '') {

		}
		$url = "https://aprequest.ascentria.org/aprequest/singletransaction.aspx?txn=" . $txn;
		
		$html = file_get_contents( ($url) );
		if(strlen($html) < 16) {
			$url = "http://acamboweb0/aprequest/singletransaction.aspx?txn=" . $txn;
			$html = file_get_contents( ($url) );
		}

		if(strlen($html) < 16) {
			show_404('file not found! ' . $url);
		} else {
			$this->load->helper('dompdf');
			pdf_create($html, $txn, $save);
		}
	}
}