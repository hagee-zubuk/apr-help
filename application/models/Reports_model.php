<?php
class Reports_model extends CI_Model {

	public function __construct() {
		parent::__construct();
		$this->load->database();
	}


	public function fetchTransactions($filt = '') {
		$sql = <<<IHAVENOTSEASONEDTHEMEAT
SELECT txn.[guid], txn.[txn_no]
	, txn.[create_at] AS [date_created]
	, txn.[due_dt] AS [date_due]
	, txn.[entity_id]
	, CASE WHEN txn.[ismultiregn] = 1 THEN det.[regn_id]
		ELSE txn.[region_id]
		END AS [region_id]
	, CASE WHEN txn.[ismultisite] = 1 THEN det.[site_id]
		ELSE txn.[site_id]
		END AS [site_id]
	, COALESCE(det.[deptcode], '') AS [dept_id]
	, COALESCE(det.[servcode], '') AS [serv_id]
	, COALESCE(det.[glacctcode], '') AS [gla_id]
	, txn.[fy_id] AS [fy]
	, COALESCE(det.[PayerCode], '') AS [payer_id]
	, COALESCE(det.[person], '') AS [person_id]
	, txn.[vendor_id]
	, COALESCE(det.[amount], 0.00) AS [amount]
	, COALESCE(txn.[invoice_no], '') AS [invoice_no]
	, txn.[invoice_dt] AS [invoice_date]
	, txn.[create_user] AS [requester]
	, txn.[ismultiregn] AS [isMRegn]
	, txn.[ismultisite] AS [isMSite]
	, txn.[hold]
	, txn.[hash]
	, txn.[detailhash]
	, ( txn.[note] ) AS [note]
	, hld.[ts] AS [hr_ts]
	, hld.[userid] AS [hold_userid]
	, hld.[note] AS [holdreason]
	, COALESCE(DATALENGTH(txn.[upload]),0) AS ulen
FROM [transactions] AS txn
	LEFT JOIN [requestdetails] AS det ON txn.[guid]=det.[Inv_ID] AND det.[Deleted]=0
	LEFT JOIN [holdreasons] AS hld ON txn.[guid]=hld.[guid]
WHERE txn.[txn_no] > 100 
	AND (	
		txn.[hash] <> 'approved'
		OR 
		txn.[hold] = 1
		)
$filt
ORDER BY txn.[txn_no]
IHAVENOTSEASONEDTHEMEAT;
		$qry = $this->db->query($sql);
		if ($qry->num_rows() > 0 ) {
			return $qry->result();
		} else {
			return FALSE;
		}
	}
}