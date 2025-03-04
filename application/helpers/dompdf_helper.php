<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
// define('DOMPDF_ENABLE_AUTOLOAD', false);
require_once(FCPATH . join(DIRECTORY_SEPARATOR, array('application', 'third_party', 'dompdf', 'autoload.inc.php')));
use Dompdf\Dompdf;
use Dompdf\Options;

function pdf_create($html, $filename='', $stream=TRUE) {
    $dompdf = new Dompdf();
    
    $options = $dompdf->getOptions();
    $options->set(array('isRemoteEnabled' => true
                        , 'tempDir' => 'C:\\Work\\helpers\\tmp'
                        , 'isHtml5ParserEnabled' => true
                        , 'logOutputFile' => 'C:\\Work\\helpers\\tmp\\log.pdf.txt'
                        , 'chroot' => 'C:\\Work\\aprequest\\builds\\active\\'
                    ));
    $dompdf->setOptions($options);
    // die('<pre>'. var_dump($options) . '</pre>');

    $dompdf->load_html($html);
    $dompdf->render();

    if ($filename == '') $filename = 'aaa';
    if ($stream) {
        header_remove();
        $dompdf->stream($filename.".pdf");
    } else {
        return $dompdf->output();
    }
}
?>