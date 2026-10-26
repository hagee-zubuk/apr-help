<?php
class Mip_model extends CI_Model {
	public $errMesg;

	public function __construct() {
		parent::__construct();
		$this->load->database();
		$this->errMesg = '';
	}

	public function truncate_AllMIP() {
		$this->db->truncate('mip_ChartOfAccounts');
		$this->db->truncate('mip_tblAcctCode_0');
		$this->db->truncate('mip_tblAcctCode_1');
		$this->db->truncate('mip_tblAcctCode_10');
		$this->db->truncate('mip_tblAcctCode_2');
		$this->db->truncate('mip_tblAcctCode_3');
		$this->db->truncate('mip_tblAcctCode_4');
		$this->db->truncate('mip_tblAcctCode_5');
		$this->db->truncate('mip_tblAcctCode_7');
		$this->db->truncate('mip_tblAcctCode_9');
	}


	public function truncate_Vendor() {
		$this->db->truncate('mip_tblAPVendor');
	}


	public function truncate_COA() {
		$this->db->truncate('mip_ChartOfAccounts');
	}


	public function truncate_tblRow($seg) {
		$tabl = 'mip_tblAcctCode_'. $seg;
		$this->db->truncate($tabl);
	}


	public function count_tblRows() {
		$sql =<<<SPAGHETTIANDMEATBALLSAFORYOU
SELECT COUNT([accountCodeID]) AS cnt, [segmentTitle]
FROM [dbo].[mip_tblAcctCode_0]
GROUP BY [segmentTitle]

UNION


SELECT COUNT([accountCodeID]) AS cnt, [segmentTitle]
FROM [dbo].[mip_tblAcctCode_1]
GROUP BY [segmentTitle]

UNION 

SELECT COUNT([accountCodeID]) AS cnt, [segmentTitle]
FROM [dbo].[mip_tblAcctCode_10]
GROUP BY [segmentTitle]

UNION 

SELECT COUNT([accountCodeID]) AS cnt, [segmentTitle]
FROM [dbo].[mip_tblAcctCode_2]
GROUP BY [segmentTitle]

UNION 

SELECT COUNT([accountCodeID]) AS cnt, [segmentTitle]
FROM [dbo].[mip_tblAcctCode_3]
GROUP BY [segmentTitle]

UNION 

SELECT COUNT([accountCodeID]) AS cnt, [segmentTitle]
FROM [dbo].[mip_tblAcctCode_4]
GROUP BY [segmentTitle]

UNION 

SELECT COUNT([accountCodeID]) AS cnt, [segmentTitle]
FROM [dbo].[mip_tblAcctCode_5]
GROUP BY [segmentTitle]

UNION 

SELECT COUNT([accountCodeID]) AS cnt, [segmentTitle]
FROM [dbo].[mip_tblAcctCode_7]
GROUP BY [segmentTitle]

UNION 

SELECT COUNT([accountCodeID]) AS cnt, [segmentTitle]
FROM [dbo].[mip_tblAcctCode_9]
GROUP BY [segmentTitle]

UNION 

SELECT COUNT ([id]) AS cnt, 'VEN' AS [segmentTitle] FROM [dbo].[mip_tblAPVendor]
SPAGHETTIANDMEATBALLSAFORYOU;
		$qry = $this->db->query($sql);
		if ($qry->num_rows() > 0 ) {
			return $qry->result_array();
			// return $qry->result();
		} else {
			return FALSE;
		}
	}


	public function truncate_Combo() {
		$this->db->truncate('mip_tblComboEdit');
	}


	public function create_COA($ary) {
		$this->errMesg = 'invalid input array';
		if (!is_array($ary)) return FALSE;
		
		$this->errMesg = 'no guids found';
		if (strlen(trim($ary['batch'])) < 10) return FALSE;
		if (strlen(trim($ary['guid'])) < 10) return FALSE;

		
		if(isset($ary['dateOfCreation'])) $ary['dateOfCreation'] = date('Y-m-d', strtotime($ary['dateOfCreation']));
		if(isset($ary['dateOfUpdate'])) $ary['dateOfUpdate'] = date('Y-m-d', strtotime($ary['dateOfUpdate']));

		$ret = $this->db->insert('mip_ChartOfAccounts', $ary);
		$this->errMesg = 'ok '. $ret;

		return ($ret);
	}


	public function create_Vendor($ary) {
		$this->errMesg = 'invalid input array';
		if (!is_array($ary)) return FALSE;
		
		$this->errMesg = 'no guids found';
		if (strlen(trim($ary['batch'])) < 10) return FALSE;
		if (strlen(trim($ary['guid'])) < 10) return FALSE;

		$ret = $this->db->insert('mip_tblAPVendor', $ary);
		$this->errMesg = 'ok '. $ret;

		return ($ret);
	}


	public function create_Combo ($ary) {
		$this->errMesg = 'invalid input array';
		if (!is_array($ary)) return FALSE;
		
		$this->errMesg = 'no guids found';
		if (strlen(trim($ary['batch'])) < 10) return FALSE;
		if (strlen(trim($ary['guid'])) < 10) return FALSE;

		$ret = $this->db->insert('mip_tblComboEdit', $ary);
		$this->errMesg = 'ok '. $ret;

		return ($ret);
	}


	public function create_tblRow($ary, $seg) {
		$this->errMesg = 'invalid input array';
		if (!is_array($ary)) return FALSE;
		
		$this->errMesg = 'no guids found';
		if (strlen(trim($ary['batch'])) < 10) return FALSE;
		if (strlen(trim($ary['guid'])) < 10) return FALSE;

		$tabl = 'mip_tblAcctCode_'. $seg;
		if(isset($ary['dateOfCreation'])) 	$ary['dateOfCreation'] 	= date( 'Y-m-d', strtotime($ary['dateOfCreation'] 	) );
		if(isset($ary['dateOfUpdate']))  	$ary['dateOfUpdate'] 	= date( 'Y-m-d', strtotime($ary['dateOfUpdate'] 	) );

		$ret = $this->db->insert($tabl, $ary);
		$this->errMesg = 'ok '. $ret;

		return ($ret);
	}


}