<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once (APPPATH .'third_party'. DIRECTORY_SEPARATOR .'vendor'. DIRECTORY_SEPARATOR .'autoload.php');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class Reports extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->helper('url');
		$this->load->model('Reports_model');
	}

	
	public function index() {
		$dd['title'] = 'Hold Report';
		$this->load->helper('form');
		$dd['action'] = base_url('Reports/doHoldReport');
		$dd['dtSta'] = date('Y') .'-01-01';
		$dd['dtEnd'] = date('Y-m-d');
		$this->load->view('rephold', $dd);
	} 


	public function doHoldReport() {
		$strEnd = $this->input->post_get('dtEnd');
		$strSta = $this->input->post_get('dtSta');
		$dtEnd = strtotime($strEnd);
		$dtSta = strtotime($strSta);
		$dtRef = strtotime('2000-01-01');
		if ($dtRef > $dtSta) $dtSta = $dtRef;
		if ($dtRef > $dtEnd) $dtEnd = strtotime('today');

		$outFile = sprintf("C:\\Work\\ascentria\\uploads\\repHold.%s.xlsx", uniqid() );
		//$filt = sprintf(" AND txn.[create_at] >= '%s' AND txn.[create_at] <= '%s' "
		$filt = sprintf(" AND txn.[invoice_dt] >= '%s' AND txn.[invoice_dt] <= '%s' "
					, date('Y-m-d', $dtSta)
					, date('Y-m-d', $dtEnd)
				);
		$txns = $this->Reports_model->fetchTransactions($filt);
		// echo '<table style="width: 100%;"><thead></thead><tbody>';
		if ($txns !== FALSE) {
			$tplName = APPPATH .'helpers'. DIRECTORY_SEPARATOR .'repHold.xlsx';
			$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tplName);
			$sheet = $spreadsheet->getActiveSheet();
			$row = 1;

			foreach($txns as $t) {
				$stat = ($t->hold == 1) ? "HOLD" : "pending";
				$sheet->setCellValueByColumnAndRow( 1, $row, $t->txn_no);
				$sheet->setCellValueByColumnAndRow( 2, $row, date('m/d/y', strtotime($t->date_created)) );
				$sheet->setCellValueByColumnAndRow( 3, $row, date('m/d/y', strtotime($t->date_due)) );
				$sheet->setCellValueByColumnAndRow( 4, $row, $t->entity_id );
				$sheet->setCellValueByColumnAndRow( 5, $row, $t->region_id );
				$sheet->setCellValueByColumnAndRow( 6, $row, $t->site_id );
				$sheet->setCellValueByColumnAndRow( 7, $row, $t->dept_id );
				$sheet->setCellValueByColumnAndRow( 8, $row, $t->serv_id );
				$sheet->setCellValueByColumnAndRow( 9, $row, $t->gla_id );
				$sheet->setCellValueByColumnAndRow(10, $row, $t->fy ); 											// J
				$sheet->setCellValueByColumnAndRow(11, $row, $t->payer_id );
				$sheet->setCellValueByColumnAndRow(12, $row, $t->person_id );
				$sheet->setCellValueByColumnAndRow(13, $row, $t->vendor_id );
				$sheet->setCellValueByColumnAndRow(14, $row, number_format($t->amount, 2, '.', '') );
				$sheet->setCellValueByColumnAndRow(15, $row, $t->invoice_no );
				$sheet->setCellValueByColumnAndRow(16, $row, date('m/d/y', strtotime($t->invoice_date)) );
				$sheet->setCellValueByColumnAndRow(17, $row, $t->requester );
				$sheet->setCellValueByColumnAndRow(18, $row, $stat );
				$sheet->setCellValueByColumnAndRow(19, $row, str_replace(array('"', '\r', '\n', '\t'), '', $t->note) );
				$sheet->setCellValueByColumnAndRow(20, $row, $t->hold_userid ); 								// T
				$sheet->setCellValueByColumnAndRow(21, $row, str_replace(array('"', '\r', '\n', '\t'), '', $t->holdreason) );
				if ($t->ulen > 100) {
					$url = 'https://aprequest.ascentria.org/helpers/dld.aspx?guid='. $t->guid;
					$sheet->setCellValueByColumnAndRow(22, $row, '[click for attachment]' );
					$sheet->getHyperlink('V'. $row)->setURL($url);
				}
				$row++;
			}
			$name = sprintf( 'HoldReport_%s_to_%s.xlsx', date('y-m-d', $dtSta), date('y-m-d', $dtEnd) );
			/*
			$sheet->setCellValueByColumnAndRow(24, 1, 'REPORT GENERATED:');
			$sheet->setCellValueByColumnAndRow(25, 1, date('n/j/y H:i'));
			$sheet->setCellValueByColumnAndRow(26, 1, 'Inclusive Dates:');
			$sheet->setCellValueByColumnAndRow(27, 1, sprintf('%s to %s', date('n/j/y', $dtSta), date('n/j/y', $dtEnd) ) );
			*/
			$writer = new Xlsx($spreadsheet);
			$writer->save($outFile);
			$this->download($outFile, $name);
		} else {
			show_error('No data to export for the HOLD report', 500, 'Reports/Generate');
		}

	}


	public function doHoldReportCSV() {
		$strEnd = $this->input->post_get('dtEnd');
		$strSta = $this->input->post_get('dtSta');
		$dtEnd = strtotime($strEnd);
		$dtSta = strtotime($strSta);
		$dtRef = strtotime('2000-01-01');
		if ($dtRef > $dtSta) $dtSta = $dtRef;
		if ($dtRef > $dtEnd) $dtEnd = strtotime('today');

		$filename = sprintf("C:\\Work\\ascentria\\uploads\\repHold.%s.txt", uniqid() );
		$file = fopen($filename, "w");

		$filt = sprintf(" AND txn.[create_at] >= '%s' AND txn.[create_at] <= '%s' "
					, date('Y-m-d', $dtSta)
					, date('Y-m-d', $dtEnd)
				);
		$txns = $this->Reports_model->fetchTransactions($filt);
		// echo '<table style="width: 100%;"><thead></thead><tbody>';
		if ($txns !== FALSE) {
			foreach($txns as $t) {
				/*
				echo sprintf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>".
							"<td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>"
							, $t->txn_no
							, date('m/d/y', strtotime($t->date_created))
							, date('m/d/y', strtotime($t->date_due))
							, $t->entity_id
							, $t->region_id
							, $t->site_id
							, $t->dept_id
							, $t->serv_id
							, $t->gla_id
							, $t->fy
							, $t->payer_id
							, $t->person_id
							, $t->vendor_id
							, number_format($t->amount, 2, '.', '')
							, $t->invoice_no
							, date('m/d/y', strtotime($t->invoice_date))
							, $t->requester
						);
				*/
				if($t->hold) {
					$stat = "HOLD";
				} else {
					$stat = "pending";
				}
				$line = sprintf('"%s","%s","%s",'.
							'"%s","%s","%s","%s","%s","%s",'.
							'"%s","%s","%s","%s",'.
							'%0.2f,"%s","%s","%s"'.
							',"%s","%s"'. PHP_EOL
							, $t->txn_no, date('m/d/y', strtotime($t->date_created)), date('m/d/y', strtotime($t->date_due))
							, trim($t->entity_id), trim($t->region_id), trim($t->site_id), trim($t->dept_id), trim($t->serv_id), trim($t->gla_id)
							, $t->fy, trim($t->payer_id), trim($t->person_id), trim($t->vendor_id)
							, $t->amount, trim($t->invoice_no), date('m/d/y', strtotime($t->invoice_date)), $t->requester
							, $stat, str_replace(array('"', '\r', '\n', '\t'), '', $t->note)
						);
				fwrite($file, $line);
			}
		} else {
			fwrite($file, 'no records to export');
		}
		//echo PHP_EOL .'</tbody></table>';
		fclose($file);

		$this->download($filename);
	}


	protected function download($fulpath, $name = 'report.csv') {
		if(!file_exists($fulpath)) {
			show_404('file not found! ' . $fullPath);
		}
		if ($fd = fopen ($fulpath, "rb")) {
			$fsize = filesize($fulpath);
			$path_parts = pathinfo($fulpath);

			header("Content-type: application/octet-stream");
			header("Content-Disposition: filename=\"".$name."\"");
			header("Content-length: $fsize");
			header("Cache-control: private"); //use this to open files directly
			while(!feof($fd)) {
				$buffer = fread($fd, 2048);
				echo $buffer;
			}
			fclose ($fd);
		}
	}


}