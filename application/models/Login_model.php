<?php
class Login_model extends CI_Model {
	public $errMesg;

	public function __construct() {
		parent::__construct();
		$this->load->database();
		$this->errMesg = '';
	}

	public function create_logRow($ary) {
		$this->errMesg = 'invalid input array';
		if (!is_array($ary)) return FALSE;
		
		$this->errMesg = 'no guids found';
		if (strlen(trim($ary['guid'])) < 10) return FALSE;

		$tabl = 'mip_tblAcctCode_'. $seg;
		if(!isset($ary['ts'])) 	$ary['ts'] 	= date( 'Y-m-d H:i:s' );

		$ret = $this->db->insert('mip_Logins', $ary);
		$this->errMesg = 'ok '. $ret;

		return ($ret);
	}


	public function getLoginAccessToken($as_array = TRUE) {
		$sql =<<<GREENEGGSANDSPAMISGOOD
SELECT *
FROM [mip_Logins]
ORDER BY [ts] DESC
GREENEGGSANDSPAMISGOOD;
		$qry = $this->db->query($sql);
		if ($qry->num_rows() > 0 ) {
			if ($as_array) {
				return $qry->result_array();
			} else {
				return $qry->result();
			}
		} else {
			return FALSE;
		}
	}


	public function update_LogRow($guid, $ary) {
		$this->errMesg = 'invalid update array';
		if (!is_array($ary)) return FALSE;
		
		$this->errMesg = 'no guids found';
		if (strlen($guid) < 10) return FALSE;

		if (isset($ary['guid'])) unset($ary['guid']);

		
		$ret =  $this->db->update('mip_Logins', $ary);
		$this->errMesg = 'ok '. $ret;

		return ($ret);
	}


}