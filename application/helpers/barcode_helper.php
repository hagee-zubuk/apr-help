<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

function barcode_create($code='', $stream=false, $type='code128') {
	require_once(FCPATH . join(DIRECTORY_SEPARATOR, array('application', 'helpers', '2dbarcode', '2dbarcode.php')));
	
    if ($code == '') $code = "INVALID";
 
	$marge = 8; // between barcode and hri in pixel
	$x = 150; // barcode center
	$height = 20; // barcode height in 1D ; module size in 2D
	$width = 2; // barcode height in 1D ; not use in 2D
	$angle = 0; // rotation in degrees : nb : non horizontable barcode might not be usable because of pixelisation

	$im = imagecreatetruecolor($x*2, $height);
	$black = ImageColorAllocate($im, 0x00, 0x00, 0x00);
	$white = ImageColorAllocate($im, 0xff, 0xff, 0xff);
	imagefilledrectangle($im, 0, 0, $x*2, $height, $white);
	$data = Barcode::gd($im, $black, $x, $height/2, 0, $type, array('code' => $code), $width, $height);
    
	header('Content-Type: image/gif');
	if ($stream) {
		date_default_timezone_set('Asia/Manila');
		header('Content-Disposition: attachment; filename="sym-'. $code .'"');
	    header('Content-Transfer-Encoding: binary');
	    header('Accept-Ranges: bytes');
	    header('Cache-Control: private');
	    header('Pragma: private');
	    header('Expires: '. date('D, j M Y H:i:s e'));	// Mon, 26 Jul 1997 05:00:00 GMT
	}
	imagegif($im);
	imagedestroy($im);
}
?>