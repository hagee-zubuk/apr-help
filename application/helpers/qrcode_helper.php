<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
function qrcode_create($code='', $stream=false) {
    require_once(FCPATH . join(DIRECTORY_SEPARATOR, array('application', 'helpers', 'qrcode', 'qrlib.php')));
	
    if ($code == '') $code = "INVALID";
	$blnDL = $stream;
	header('Content-Type: image/png');
	if ($blnDL) {
		date_default_timezone_set('Asia/Manila');
		header('Content-Disposition: attachment; filename="sym-'. $code .'"');
	    header('Content-Transfer-Encoding: binary');
	    header('Accept-Ranges: bytes');
	    header('Cache-Control: private');
	    header('Pragma: private');
	    header('Expires: '. date('D, j M Y H:i:s e'));	// Mon, 26 Jul 1997 05:00:00 GMT
	}
	QRcode::png($code, null, QR_ECLEVEL_Q, 10, 2); 
}
?>