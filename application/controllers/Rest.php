<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rest extends CI_Controller {
    public $loginfile;
    public $rest_url;
    public $acc_tokn;
    public $login_dt;
    public $debug;
    public $responses;

    public function __construct() {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model('Mip_model');
        $this->loginfile = $this->config->item('loginfile');
        $this->acc_tokn = '';
        $this->rest_url = '';
        $this->debug = '';
        $this->responses = array();

        if(file_exists($this->loginfile)) {
            $this->loadlogindata();
        }
    }


    private function dumpResponses() {
        if (count( $this->responses ) > 0 ) {
            return FALSE;
        } else {
            return($this->responses);
        }
    }


    private function mkGUID ($trim = true) {
        // Windows
        if (function_exists('com_create_guid') === true) {
            if ($trim === true)
                return trim(com_create_guid(), '{}');
            else
                return com_create_guid();
        }

        // OSX/Linux
        if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        // Fallback (PHP 4.2+)
        mt_srand((float)microtime() * 10000);
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);                  // "-"
        $lbrace = $trim ? "" : chr(123);    // "{"
        $rbrace = $trim ? "" : chr(125);    // "}"
        $guidv4 = $lbrace.
                  substr($charid,  0,  8).$hyphen.
                  substr($charid,  8,  4).$hyphen.
                  substr($charid, 12,  4).$hyphen.
                  substr($charid, 16,  4).$hyphen.
                  substr($charid, 20, 12).
                  $rbrace;
        return $guidv4;
    }


    private function loadlogindata() {
        $dtOld = strtotime('-1 week');
        $this->load->model('Login_model');
        $logrecs = $this->Login_model->getLoginAccessToken();
        if ($logrecs === FALSE) {
            return FALSE;
        }
        $latest = $logrecs[0];
        $dtFn = strtotime($latest['ts']);
        if ($dtFn < $dtOld) {
            return FALSE;
        } else {
            $this->rest_url = $latest['RestApiUrl'];
            $this->acc_tokn = $latest['AccessToken'];
            $this->login_dt = $dtFn;
        }
        return TRUE;
    }


    public function getLoginData() {
        $this->load->model('Login_model');
        $ret = '';
        $logrecs = $this->Login_model->getLoginAccessToken();
        if ($logrecs === FALSE) {
            $ret = array(
                    'msg'   => 'no previous logins'
                    , 'err' => 1
                );
        } else {
            $dtFn = strtotime($logrecs[0]['ts']);
            //echo '<pre>';
            $ts = 'Timestamp: '. date('Y-m-d H:i:s e', $dtFn) . PHP_EOL. PHP_EOL;
            $ts .= print_r($logrecs[0], TRUE);
            $ret = array(
                      'msg' => $ts
                    , 'err' => 0
                    , 'ts'  => date('Y-m-d H:i:s e', $dtFn)
                    , 'acctokn'     => $logrecs[0]['AccessToken']
                    , 'statuscode'  => $logrecs[0]['login_statuscode']
                );
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($ret);
    }


    public function index() {
        $this->load->model('Login_model');
        $logrecs = $this->Login_model->getLoginAccessToken();
        if ($logrecs === FALSE) {
            $dat = array('logfile'  => 'none'
                        , 'resturl' => ''
                        , 'acctokn' => ''
                        , 'logtime' => ''
                    );
        } else {
            $dtFn = strtotime($logrecs[0]['ts']);
            $dat = array('logfile' => 'DB'
                        , 'resturl' => $logrecs[0]['RestApiUrl']
                        , 'acctokn' => $logrecs[0]['AccessToken']
                        , 'logtime' => $dtFn
                    );
        }

        $this->load->view('rest-page.php', $dat);
    }


    public function login() {
        $this->load->library('Guzzle');
        $this->logout();
        $debug = '*** LOGIN START *** LOGIN START ***'. PHP_EOL .PHP_EOL;
        
        $cli = new GuzzleHttp\Client( [ GuzzleHttp\RequestOptions::VERIFY => FALSE ] ); // [ GuzzleHttp\RequestOptions::VERIFY => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() ] );

        $url = 'https://login.mip.com/api/v1/sso/mipadv/login';
        $bod =  json_encode(
                    array('username'    => ''
                        , 'password'    => ''
                        , 'org'         => ''
                    )
                );
        $debug .= 'do login()..'. PHP_EOL;
        header("Content-Type: text/plain");

        try {
            $response = $cli->request( 'POST'
                    , $url
                    , [    'allow_redirects' =>  false
                            , 'body' => $bod
                            , 'headers' => [ 'Content-type' => 'application/json' ]
                        ]
                    );
            $sta = $response->getStatusCode();
            $debug .= PHP_EOL .'Statuscode: '. $sta; // 200
            $debug .= PHP_EOL .'Reason: '. $response->getReasonPhrase(); // OK
            $debug .= PHP_EOL .'Protocol: '. $response->getProtocolVersion(); // 1.1
            $tmpRes = $response->getBody();
            array_push($this->responses, $tmpRes);
            $json = $tmpRes;
            
            $debug .= PHP_EOL .'Body: '. $json . PHP_EOL . PHP_EOL;
            
            $aryR = json_decode($json, TRUE);
            if($sta < 300) {
                $aryR['login_statuscode'] = $sta;
                $aryR['rawJSON'] = $json;
                $aryR['guid'] = $this->mkGUID();
                $this->load->model('Login_model');
                $this->Login_model->create_logRow($aryR);
            }
                        
            $intRet = file_put_contents($this->loginfile, $json);
            $debug .= '** login OK. save returns: '. $intRet .'   '. PHP_EOL;
        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            #guzzle repose for future use
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            // print_r($responseBodyAsString);
            $debug .= '*** ERROR!(G)'. PHP_EOL . PHP_EOL . PHP_EOL . $responseBodyAsString . PHP_EOL . PHP_EOL;
            echo '*** ERROR ERROR ERROR GUZZLE ERROR'. PHP_EOL . '*** GUZZLE ERROR'. PHP_EOL. PHP_EOL;
            var_dump($response);
            echo '-----------------------------------------------------------------'. PHP_EOL . 'DEBUG INFO:'. PHP_EOL . 
                    $debug;
                    '-----------------------------------------------------------------'. PHP_EOL;
        } catch(Exception $e) {
            echo '** ERROR! ERROR ERROR'. PHP_EOL . '*** ERROR (GENERAL)'. PHP_EOL. PHP_EOL;
            var_dump($e);
            echo '-----------------------------------------------------------------'. PHP_EOL . 'DEBUG INFO:'. PHP_EOL . 
                    $debug;
                    '-----------------------------------------------------------------'. PHP_EOL;
        } finally {
            $debug .= '*** DONE LOGIN: '. date('Y-m-d H:i:s') . PHP_EOL;
        }
        $this->debug .= $debug;
        // echo PHP_EOL .'</pre>';
    }


    public function raw__logout($accesstoken = '') {
        $debug = '*** RAW LOGOUT (accesstoken) START'. PHP_EOL;
        $accesstoken = trim($accesstoken);
        if (strlen($accesstoken) < 10) return;
        
        $this->load->library('Guzzle');
        $cli = new GuzzleHttp\Client( [ GuzzleHttp\RequestOptions::VERIFY => FALSE] ); // \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() ] );
        $url = $this->rest_url .'api/security/logout';
        $bod = '';
        $debug .= PHP_EOL .'URL: '. $url .PHP_EOL;
        try {
            $response = $cli->request( 'POST'
                    , $url
                    , [
                            'allow_redirects' => true
                            , 'headers' => [ 
                                'Authorization-Token' => $accesstoken
                                ]
                        ]
                    );
            $sta = $response->getStatusCode();
            $debug .= PHP_EOL .'Statuscode: '. $sta; // 200
            $debug .= PHP_EOL .'Reason: '. $response->getReasonPhrase(); // OK

            $json = $response->getBody();
            array_push($this->responses, $json);
            $debug .= PHP_EOL .'Response: '. PHP_EOL . $json . PHP_EOL . PHP_EOL;
            $aryR = json_decode($json);
            $debug .= PHP_EOL . print_r($aryR, TRUE);
        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $debug .= '*** ERROR(G)!'. PHP_EOL . PHP_EOL . PHP_EOL . $responseBodyAsString . PHP_EOL . PHP_EOL;
            echo '*** ERROR ERROR ERROR GUZZLE ERROR'. PHP_EOL . '*** GUZZLE ERROR'. PHP_EOL. PHP_EOL;
            var_dump($response);
            echo '-----------------------------------------------------------------'. PHP_EOL . 'DEBUG INFO:'. PHP_EOL . 
                    $debug;
                    '-----------------------------------------------------------------'. PHP_EOL;
            echo '*** ERROR!'. PHP_EOL . PHP_EOL . PHP_EOL . $responseBodyAsString . PHP_EOL . PHP_EOL;
        } catch(Exception $e) {
            echo '** ERROR! ERROR ERROR'. PHP_EOL . '*** ERROR (GENERAL)'. PHP_EOL. PHP_EOL;
            var_dump($e);
            echo '-----------------------------------------------------------------'. PHP_EOL . 'DEBUG INFO:'. PHP_EOL . 
                    $debug;
                    '-----------------------------------------------------------------'. PHP_EOL;
        } finally {
            $debug .= '*** DONE RAW LOGOUT (accesstoken): '. date('Y-m-d H:i:s') . PHP_EOL;
        }
        $this->debug .= $debug;
        return;
    }


    public function lastlogins($qty = 5) {
        $this->load->model('Login_model');
        $logrecs = $this->Login_model->getLoginAccessToken();
        if ($logrecs !== FALSE) {
            $cnt = 0;
            foreach($logrecs as $l) {
                $guid = $l['guid'];
                if ($l['login_statuscode'] == 200) {
                    echo date('m/d H:i', strtotime($l['ts'])) . '-- '. $l['AccessToken'] .'<br />'. PHP_EOL;            
                }
                $cnt++;
                if ($cnt >= $qty) break;
            }
        }
    }


    private function logout_actual($rec) {
        $debug = '*** LOGOUT_ACTUAL (rec) START'. PHP_EOL;
        $acc_tokn = $rec['AccessToken'];

        $this->load->library('Guzzle');
        $cli = new GuzzleHttp\Client( [ GuzzleHttp\RequestOptions::VERIFY => FALSE ] ); //\Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() ] );
        $url = $this->rest_url .'api/security/logout';
        $url = 'https://api.mip.com/api/security/logout';
        $bod = '';
        // $rec = array();
        try {
            $response = $cli->request( 'POST'
                    , $url
                    , [     'allow_redirects' =>  false
                            , 'headers' => [
                                    'Authorization-Token' => $acc_tokn
                                ]
                        ]
                    );
            $sta = $response->getStatusCode();
            $debug .= PHP_EOL .'Statuscode: '. $sta; // 200
            $debug .= PHP_EOL .'Reason: '. $response->getReasonPhrase(); // OK

            $json = $response->getBody();
            array_push($this->responses, $json);
            $debug .= PHP_EOL .'Response: '. PHP_EOL . $json . PHP_EOL . PHP_EOL;
            $aryR = json_decode($json);
            $debug .= PHP_EOL . print_r($aryR, TRUE);
            $rec['logout_response'] = $json;
            $rec['logout_statuscode'] = $sta;
        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $rec['logout_response'] = '** ERROR! '. $responseBodyAsString;
            $rec['logout_statuscode'] = 500;
            $debug .= '*** ERROR(G)!'. PHP_EOL . PHP_EOL . PHP_EOL . $responseBodyAsString . PHP_EOL . PHP_EOL;
            /*
            echo '*** ERROR ERROR ERROR GUZZLE ERROR'. PHP_EOL . '*** GUZZLE ERROR'. PHP_EOL. PHP_EOL;
            var_dump($response);
            echo '-----------------------------------------------------------------'. PHP_EOL . 'DEBUG INFO:'. PHP_EOL . 
                    $debug;
                    '-----------------------------------------------------------------'. PHP_EOL;
            echo '*** ERROR!'. PHP_EOL . PHP_EOL . PHP_EOL . $responseBodyAsString . PHP_EOL . PHP_EOL;
            */
        } catch(Exception $e) {
            $debug .= '** ERROR! ERROR ERROR'. PHP_EOL . '*** ERROR (GENERAL)'. PHP_EOL. PHP_EOL;
            $debug .= print_r($e, TRUE);
        } finally {
            $debug .= '*** DONE LOGOUT_ACTUAL (rec): '. date('Y-m-d H:i:s') . PHP_EOL;
        }
        $this->debug .= $debug;

        return($rec);
    }


    public function logout() {
        $debug = '*** LOGOUT START'. PHP_EOL;
        $this->load->model('Login_model');
        $logrecs = $this->Login_model->getLoginAccessToken();
        if ($logrecs !== FALSE) {
            $debug .= 'attempting to dispose of old logins --'. PHP_EOL;
            $cnt = 0;
            foreach($logrecs as $l) {
                $debug .= 'trying: '. $l['ix'] .' ['. $l['guid'] .'] * * * * * * * * * *'. PHP_EOL;
                $guid = $l['guid'];
                if ($l['logout_statuscode'] !== 200) {
                    //echo 'GO'. PHP_EOL;
                    $ary = $this->logout_actual($l);
                    unset($ary['ix']);
                    unset($ary['ts']);
                    unset($ary['guid']);
                    $this->Login_model->update_LogRow($guid, $ary);
                } else {
                    $debug .= '** INFO: skipping: '. $l['ix'] .PHP_EOL;
                }
                $cnt++;
                if ($cnt > 3) break;
            }
        } else {
            $debug .= 'INFO: no rows'. PHP_EOL;
        }
        $this->debug .= $debug;
    }


    public function getVendors() {
        set_time_limit(18000); // 3 minutes = 18,000
        $dtSta = strtotime(date('Y-m-d H:i:s'));
        $debug = PHP_EOL .'*** getVendors started *** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * '. PHP_EOL . PHP_EOL;
        $msgs = '';
        if (strlen($this->rest_url) < 7) {
            // needs login!
            $debug .= ' -- Relogin REQUIRED '. PHP_EOL;
            $this->login();
            $this->loadlogindata();
        }

        if (strlen($this->rest_url) < 7) {
            $debug .= 'ERR!';
            $this->debug .= $debug;
            show_error('Login failure calling Vendor List<br /><br /><pre>'. $this->debug .'</pre>', 500, $heading = 'An Error Was Encountered');
        }
        $debug .= 'Token: '. $this->acc_tokn .PHP_EOL;

        $this->load->library('Guzzle');
        $cli = new GuzzleHttp\Client( [ GuzzleHttp\RequestOptions::VERIFY => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() ] );

        $sz = 60;
        $pg_num = 0;
        $ctr = 0;
        $total_rows = 0;

        $base = $this->rest_url .'api/v1/maintain/vendors?page[size]=' .$sz;
        $url = $base . '&page[number]='. $pg_num;
        $bod = '';

        try {
            $blnFetch = TRUE;
            $response = FALSE;
            $sta_code = 0;
            while($blnFetch) {
                $response = $cli->request( 'GET'
                            , $url
                            , [
                                    'allow_redirects'   => false
                                    , 'http_errors'     => false
                                    , 'timeout'         => 20
                                    , 'headers' => [ 'Content-type' => 'application/json'
                                            , 'Authorization-Token' => $this->acc_tokn
                                        ]
                                ]
                            );
                $sta_code = $response->getStatusCode();
                $ctr++;
                if ($ctr > 2) {
                    $blnFetch = FALSE;
                } else {
                    if ($sta_code != 200) {
                        unset( $response );
                        unset( $cli );
                        if ($sta_code >= 500) {
                            $blnFetch = FALSE; // don't bother to re-authenticate
                        } else {
                            $debug .= PHP_EOL .'*** RELOGIN ***' . PHP_EOL . PHP_EOL;
                            $this->login();
                            $this->loadlogindata();
                            $cli = new GuzzleHttp\Client( [ GuzzleHttp\RequestOptions::VERIFY => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() ] );
                        }
                    } else {
                        $blnFetch = FALSE;
                    }
                }
            }
            
            if ($sta_code == 200) {
                $debug .= PHP_EOL .'URL: '. $url; // 200
                $debug .= PHP_EOL .'Statuscode: '. $response->getStatusCode(); // 200
                $debug .= PHP_EOL .'Reason: '. $response->getReasonPhrase(); // OK
                $json = $response->getBody();
                array_push($this->responses, $json);
                $aryR = json_decode($json);
                $debug .= PHP_EOL;
                if (property_exists($aryR, 'total')) {
                    $total_rows = $aryR->total;
                    $msgs .=  PHP_EOL .'**** total rows to process: '. $total_rows .' ***' . PHP_EOL;
                } else {
                    $total_rows = 0;
                    $debug .=  PHP_EOL .'*** ERROR!'. PHP_EOL .'REST call returned unexpected schema:'. PHP_EOL . PHP_EOL;
                    $debug .=  $json . PHP_EOL . PHP_EOL . PHP_EOL;
                    $this->debug .= $debug;
                    echo '*** ERROR!'. PHP_EOL .'REST call returned unexpected schema:'. PHP_EOL . PHP_EOL;
                    echo $debug;
                    return FALSE;
                }
                $msgs .= PHP_EOL. 'Type: '. $aryR->type;
                $msgs .= PHP_EOL .'got: '. ($sz * $pg_num) .' of '. $total_rows .' in '. $sz . '-row chunks'. PHP_EOL;
                $this->Mip_model->truncate_Vendor();
                $batch = $this->mkGUID();
                set_time_limit(600);
                flush();
                while ($ctr < $total_rows) {
                    foreach($aryR->data as $objR) {
                        $aryI = array('batch'   => $batch
                                    , 'guid'    => $this->mkGUID()
                                    , 'id'      => $objR->id
                                    , 'classId' => $objR->classId
                                    , 'typeId'  => $objR->typeId
                                    , 'name'    => $objR->name
                                    , 'status'  => $objR->status
                                    , 'printedMainAddress' => $objR->printedMainAddress
                                    , 'vendorAddDate' => $objR->vendorAddDate
                                );
                        
                        $this->Mip_model->create_Vendor($aryI);
                        $ctr++;
                        unset($aryI);
                    }
                    $msgs .= 'done page: '. $pg_num . PHP_EOL;
                    $pg_num++;

                    $url = $base . '&page[number]='. $pg_num ;
                    if($ctr < $total_rows) {
                        $response = $cli->request( 'GET'
                            , $url
                            , [
                                    'allow_redirects' =>  false
                                    , 'body' => $bod
                                    , 'headers' => [ 'Content-type' => 'application/json'
                                            , 'Authorization-Token' => $this->acc_tokn
                                        ]
                                ]
                            );
                        $json = $response->getBody();
                        $aryR = json_decode($json);
                        $total_rows = $aryR->total;
                    } else {
                        unset($aryR);
                        $objR = NULL;
                    }
                    flush();
                } 
                $msgs .= '*** pages done: '. $ctr .' rows'. PHP_EOL;
                $msgs .= 'mip_tblAPVendor refresh done'. PHP_EOL;
            } else {
                $msgs .= '*** FAILED: unexpected status code'. PHP_EOL;
                $debug .= '*** FAILED: unexpected status code'. PHP_EOL;
            }
        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            #guzzle repose for future use
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $debug .= '**** ERROR! (GUZZLE)'. print_r($responseBodyAsString, TRUE);
        } catch (Exception $x) {
            $debug .= PHP_EOL .'*** ERROR! (general)'. PHP_EOL .
                    print_r($x, TRUE) .
                    PHP_EOL .'*** *** *** ***'. PHP_EOL;
        }
        $dtEnd = strtotime(date('Y-m-d H:i:s'));
        $msgs .= PHP_EOL .'time elapsed: '. ($dtEnd - $dtSta) .'s'. PHP_EOL;
        $msgs .=  date('Y-m-d H:i:s');
        $this->debug = $debug;
        echo $msgs;
    }


    public function clrCombi() {
        $this->Mip_model->truncate_Combo();
    }

    public function getCombi()  {
        $debug = PHP_EOL .'*** getCombi() started *** * * * * * * * * * * * * * * * * * * * * *'. PHP_EOL. PHP_EOL;
        $msgs = '';
        ob_start();
        set_time_limit(18000); // 3 minutes = 18,000
        //echo '<pre>';
        $debug .= 'URL: '. $this->rest_url .PHP_EOL;
        if (strlen($this->rest_url) < 7) {
            // needs login!
            $debug .= ' -- Relogin REQUIRED '. PHP_EOL;
            $this->login();
            $this->loadlogindata();
        }

        if (strlen($this->rest_url) < 7) {
            $debug .= 'ERR!';
            $this->debug .= $debug;
            show_error('Login failure calling Combination List<br /><br /><pre>'. $this->debug .'</pre>', 500, $heading = 'An Error Was Encountered');
        }
        $debug .= 'Token: '. $this->acc_tokn .PHP_EOL;

        $this->load->library('Guzzle');
        $cli = new GuzzleHttp\Client( [ GuzzleHttp\RequestOptions::VERIFY => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() ] );

        $sz = 50;
        $ctr = 0;
        $total_rows = 0;
        $base = $this->rest_url .'api/v1/maintain/accountcodecombinations?page[size]='. $sz;
        $url  = $this->rest_url .'api/v1/maintain/accountcodecombinations?page[size]=2';
        $bod = '';
        //echo '<pre>';
        $dtSta = strtotime(date('Y-m-d H:i:s'));

        try {
            $blnFetch = TRUE;
            $response = FALSE;
            $sta_code = 0;
            while($blnFetch) {
                $response = $cli->request( 'GET'
                            , $url
                            , [
                                    'allow_redirects'   => false
                                    , 'http_errors'     => false
                                    , 'timeout'         => 20
                                    , 'headers' => [ 'Content-type' => 'application/json'
                                            , 'Authorization-Token' => $this->acc_tokn
                                        ]
                                ]
                            );
                $sta_code = $response->getStatusCode();
                $ctr++;
                if ($ctr > 2) {
                    $blnFetch = FALSE;
                } else {
                    if ($sta_code != 200) {
                        unset( $response );
                        unset( $cli );
                        if ($sta_code >= 500) {
                            $blnFetch = FALSE; // don't bother to re-authenticate
                        } else {
                            $debug .= PHP_EOL .'*** RELOGIN ***' . PHP_EOL . PHP_EOL;
                            $this->login();
                            $this->loadlogindata();
                            $cli = new GuzzleHttp\Client( [ GuzzleHttp\RequestOptions::VERIFY => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() ] );
                        }
                    } else {
                        $blnFetch = FALSE;
                    }
                }
            }

            if ($sta_code == 200) {
                $debug .= PHP_EOL .'URL: '. $url . PHP_EOL .'Statuscode: '. $response->getStatusCode() . PHP_EOL .'Reason: '. $response->getReasonPhrase() .PHP_EOL; // OK
                $json = $response->getBody();
                array_push($this->responses, $json);
                $aryR = json_decode($json);
                if (property_exists($aryR, 'total')) {
                    $total_rows = $aryR->total;
                    $msgs .= PHP_EOL .'**** total rows to process: '. $total_rows .' ***' .
                            PHP_EOL .'Type: '. $aryR->type . PHP_EOL;
                } else {
                    $total_rows = 0;
                    $msgs .= PHP_EOL .'*** ERROR!'. PHP_EOL .'REST call returned unexpected schema:'. PHP_EOL . PHP_EOL . $json . PHP_EOL;
                    $debug .= PHP_EOL .'*** ERROR!'. PHP_EOL .'REST call returned unexpected schema:'. PHP_EOL . PHP_EOL;
                    $debug .= $json . PHP_EOL . PHP_EOL . PHP_EOL;
                    $this->debug .= $debug;
                    return FALSE;
                }

                $msgs .= PHP_EOL .'should get: '. $total_rows .' rows in '. $sz . '-row chunks'. PHP_EOL . PHP_EOL;

                $this->Mip_model->truncate_Combo();
                
                $batch = $this->mkGUID();
                set_time_limit(600);
                
                $ctr = 0; $pg_num = 0;
                while ($ctr < $total_rows) {
                    $url = $base . '&page[number]='. $pg_num;
                    $response = $cli->request( 'GET'
                            , $url
                            , [     'allow_redirects' =>  false
                                    , 'body' => $bod
                                    , 'headers' => [ 'Content-type' => 'application/json'
                                            , 'Authorization-Token' => $this->acc_tokn
                                        ]
                                ]
                            );
                    $json = $response->getBody();
                    array_push($this->responses, $json);

                    $aryR = json_decode($json);
                    if (property_exists($aryR, 'total')) {
                        // if ($pg_num > 24) { var_dump($aryR) . PHP_EOL; }
                        if ($aryR->total <= 0) {
                            $msgs .= PHP_EOL. 'last URL: '. $url . PHP_EOL;
                            $msgs .= 'JSON: '. $json . PHP_EOL;
                            break;
                        }
                    } else {
                        $msgs .= 'last URL: '. $url . PHP_EOL;
                        $msgs .= print_r($aryR, TRUE). PHP_EOL;
                        break;
                    }

                    $msgs .= $ctr .' :: '. count($aryR->data) .' :: ';
                    foreach($aryR->data as $objR) {
                        $aryI = (array)$objR;
                        $aryI['batch'] = $batch;
                        $aryI['guid'] = $this->mkGUID();

                        $this->Mip_model->create_Combo($aryI);
                        $ctr++;
                        unset($aryI);
                    }
                    $msgs .= 'done page: '. $pg_num .' ('. $ctr .'/'. $total_rows .')'. PHP_EOL;
                    $pg_num++;

                    unset($aryR);
                    $objR = NULL;
                    
                }
                // echo PHP_EOL. $json .PHP_EOL.PHP_EOL;
                $msgs .=  '*** pages done: '. $ctr .' rows'. PHP_EOL;
                $msgs .=  'mip_tblComboEdit :: '. date('Y-m-d H:i:s');
            } else {
                $msgs .=  '*** FAILED: unexpected status code'. PHP_EOL;
                $debug .= '*** FAILED: unexpected status code'. PHP_EOL;
            }
        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            #guzzle repose for future use
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $debug .= '**** ERROR! (Guzzle)'. PHP_EOL . PHP_EOL . print_r($responseBodyAsString, TRUE);
        } catch (Exception $x) {
            $debug .= PHP_EOL .'*** ERROR!'. PHP_EOL . print_r($x, TRUE) . PHP_EOL .'*** *** *** ***'. PHP_EOL;
            $msgs .= '*** ERROR!'. PHP_EOL . print_r($x, TRUE);
        }
        $dtEnd = strtotime(date('Y-m-d H:i:s'));
        $msgs .=  PHP_EOL .'Time elapsed: '. ($dtEnd - $dtSta) .'s'. PHP_EOL;
        $this->debug .= $debug;
        echo $msgs;
        if($this->input->is_cli_request()) {
            $out = ob_get_clean();
        } else {
            ob_flush();
        }
    }


    public function getChOA()  {
        $debug = '*** getChOA() **** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *'. PHP_EOL. PHP_EOL;
        $msgs = '';
        set_time_limit(30000); // 5 minutes 
        $debug .= 'URL: '. $this->rest_url .PHP_EOL;
        
        if (strlen($this->rest_url) < 7) {
            // needs login!
            $debug .= PHP_EOL .'*** Relogin'. PHP_EOL;
            $this->login();
            $this->loadlogindata();
        }

        if (strlen($this->rest_url) < 7) {
            $this->debug .= $debug;
            show_error('Login failure calling Chart-of-Accounts<br /><br /><pre>'. $this->debug .'</pre>', 500, $heading = 'An Error Was Encountered');
        }
        $debug .= 'Token: '. $this->acc_tokn .PHP_EOL;

        $this->load->library('Guzzle');
        $cli = new GuzzleHttp\Client( [ GuzzleHttp\RequestOptions::VERIFY => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() ] );

        $sz = 50;
        $pg_num = 0;
        $ctr = 0;
        $total_rows = 0;

        $base = $this->rest_url .'api/v1/maintain/ChartOfAccounts?page[size]=' .$sz;
        $url = $base . '&page[number]='. $pg_num;
        $bod = '';
        $dtSta = strtotime(date('Y-m-d H:i:s'));

        try {
            $blnFetch = TRUE;
            $response = FALSE;
            $sta_code = 0;
            while($blnFetch) {
                $response = $cli->request( 'GET'
                            , $url
                            , [
                                    'allow_redirects'   => false
                                    , 'http_errors'     => false
                                    , 'timeout'         => 20
                                    , 'headers' => [ 'Content-type' => 'application/json'
                                            , 'Authorization-Token' => $this->acc_tokn
                                        ]
                                ]
                            );
                $sta_code = $response->getStatusCode();
                $ctr++;
                if ($ctr > 2) {
                    $blnFetch = FALSE;
                } else {
                    if ($sta_code != 200) {
                        unset( $response );
                        unset( $cli );
                        if ($sta_code >= 500) {
                            $blnFetch = FALSE; // don't bother to re-authenticate
                        } else {
                            $debug .= PHP_EOL .'*** RELOGIN ***' . PHP_EOL . PHP_EOL;
                            $this->login();
                            $this->loadlogindata();
                            $cli = new GuzzleHttp\Client( [ GuzzleHttp\RequestOptions::VERIFY => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() ] );
                        }
                    } else {
                        $blnFetch = FALSE;
                    }
                }
            }

            if ($sta_code == 200) {
                $debug .= PHP_EOL .'URL: '. $url;
                $debug .= PHP_EOL .'Statuscode: '. $response->getStatusCode(); // 200
                $debug .= PHP_EOL .'Reason: '. $response->getReasonPhrase(); // OK

                $json = $response->getBody();
                array_push($this->responses, $json);
                $aryR = json_decode($json);

                if (property_exists($aryR, 'total')) {
                    $total_rows = $aryR->total;
                    $msgs .= PHP_EOL .'**** total rows to process: '. $total_rows .' ***' . PHP_EOL;
                } else {
                    $total_rows = 0;
                    $msgs .= PHP_EOL .'*** ERROR!'. PHP_EOL .'REST call returned unexpected schema:'. PHP_EOL . PHP_EOL;
                    $debug .= PHP_EOL .'*** ERROR!'. PHP_EOL .'REST call returned unexpected schema:'. PHP_EOL . PHP_EOL .
                            $json . PHP_EOL . PHP_EOL . PHP_EOL;
                    $this->debug .= $debug;
                    return FALSE;
                }
                
                $msgs .= PHP_EOL .'got: '. ($sz * $pg_num) .' of '. $total_rows .' in '. $sz . '-row chunks'. PHP_EOL;
                $this->Mip_model->truncate_AllMIP();
                $batch = $this->mkGUID();
                set_time_limit(600);
                flush();
                while ($ctr < $total_rows) {
                    foreach($aryR->data as $objR) {
                        $aryI = (array)$objR;
                        $aryI['batch'] = $batch;
                        $aryI['guid'] = $this->mkGUID();

                        $this->Mip_model->create_COA($aryI);
                        $seg = intval($objR->segmentId);
                        if ( ($seg != 0) && ($seg != 1) && ($seg != 10) && ($seg != 2) && ($seg != 3) && ($seg != 4) && ($seg != 5) && ($seg != 7) && ($seg != 9) ) {
                            //show_error('Unknown segment!', 500, $heading = 'An Error Was Encountered');
                            $msgs .= 'discarding: '. $objR->accountTitle .' [seg=' . $seg .']'. PHP_EOL;
                        } else {
                            $this->Mip_model->create_tblRow($aryI, $seg);
                        }
                        $ctr++;
                    }
                    $msgs .= 'done page: '. $pg_num . PHP_EOL;
                    $pg_num++;

                    $url = $base . '&page[number]='. $pg_num ;
                    if($ctr < $total_rows) {
                        $response = $cli->request( 'GET'
                            , $url
                            , [
                                    'allow_redirects' =>  false
                                    , 'body' => $bod
                                    , 'headers' => [ 'Content-type' => 'application/json'
                                            , 'Authorization-Token' => $this->acc_tokn
                                        ]
                                ]
                            );
                        $json = $response->getBody();
                        array_push($this->responses, $json);

                        $aryR = json_decode($json);
                        $total_rows = $aryR->total;
                    } else {
                        unset($aryR);
                        $objR = NULL;
                    }
                    flush();
                } 
                $msgs .= '*** pages done :: got '. $ctr .' rows'. PHP_EOL;
                $msgs .= 'mip_tblAcctCode_xx (all) refresh done :: '. date('Y-m-d H:i:s');
            } else {
                $msgs .= '*** FAILED: unexpected status code'. PHP_EOL;
                $debug .= '*** FAILED: unexpected status code'. PHP_EOL;
            }
        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            $debug .= PHP_EOL .'**** ERROR!!'. PHP_EOL;
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $debug .= print_r($responseBodyAsString, TRUE);
        } catch (Exception $x) {
            $debug .= PHP_EOL .'*** ERROR!'. PHP_EOL .
                    print_r($x, TRUE) .
                    PHP_EOL .'*** *** *** ***'. PHP_EOL;
        }
        $dtEnd = strtotime(date('Y-m-d H:i:s'));
        $msgs .= PHP_EOL .'Time elapsed: '. ($dtEnd - $dtSta);
        $this->debug = $debug;

        echo $msgs;
    }


    public function getTAC($seg = 0)  {
        $debug = '*** getTAC() started **** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *'. PHP_EOL. PHP_EOL;
        $msgs = '';
        set_time_limit(18000); // 3 minutes = 18,000
        $debug .= 'URL: '. $this->rest_url .PHP_EOL;
        if (strlen($this->rest_url) < 7) {
            $debug .= '<p>*** Relogin</p>'. PHP_EOL;
            $this->login();
            $this->loadlogindata();
        }

        if (strlen($this->rest_url) < 7) {
            $this->debug .= $debug;
            show_error('Login failure calling tblAcctCode-equivalent<br /><br /><pre>'. $this->debug .'</pre>', 500, $heading = 'An Error Was Encountered');
        }
        $debug .= 'Token: '. $this->acc_tokn .PHP_EOL;
        $seg = intval($seg);
        if ( ($seg != 0) && ($seg != 1) && ($seg != 10) && ($seg != 2) && ($seg != 3) && ($seg != 4) && ($seg != 5) && ($seg != 7) && ($seg != 9) ) {
            $this->debug .= $debug;
            show_error('Unknown segment!<br /><br /><pre>'. $this->debug .'</pre>', 500, $heading = 'An Error Was Encountered');
        }

        $this->load->library('Guzzle');
        $cli = new GuzzleHttp\Client( [ GuzzleHttp\RequestOptions::VERIFY => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() ] );

        $sz = 40;
        $pg_num = 0;
        $ctr = 0;
        $total = 0;

        $base = $this->rest_url .'api/v1/maintain/ChartOfAccounts?filter[segmentId][Eq]='. $seg .'&page[size]='. $sz;
        $url = $base . '&page[number]='. $pg_num;
        
        $bod = '';
        $debug .= 'Started: '. date('Y-m-d H:i:s') . PHP_EOL;
        $dtSta = strtotime(date('Y-m-d H:i:s'));

        try {
            $blnFetch = TRUE;
            $response = FALSE;
            $sta_code = 0;
            while($blnFetch) {
                $response = $cli->request( 'GET'
                            , $url
                            , [
                                    'allow_redirects'   => false
                                    , 'body'            => $bod
                                    , 'http_errors'     => false
                                    , 'timeout'         => 15
                                    , 'headers' => [ 'Content-type' => 'application/json'
                                            , 'Authorization-Token' => $this->acc_tokn
                                        ]
                                ]
                            );
                $sta_code = $response->getStatusCode();
                $ctr++;
                if ($ctr > 2) {
                    $blnFetch = FALSE;
                } else {
                    if ($sta_code != 200) {
                        unset( $response );
                        unset( $cli );
                        if ($sta_code >= 500) {
                            $blnFetch = FALSE; // don't bother to re-authenticate
                        } else {
                            $debug .= PHP_EOL .'*** RELOGIN ***' . PHP_EOL . PHP_EOL;
                            $this->login();
                            $this->loadlogindata();
                            $cli = new GuzzleHttp\Client( [ GuzzleHttp\RequestOptions::VERIFY => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() ] );
                        }
                    } else {
                        $blnFetch = FALSE;
                    }
                }
            }

            if ($sta_code == 200) {
                $debug .= PHP_EOL .'Statuscode: '    . $response->getStatusCode();       // 200
                $debug .= PHP_EOL .'Reason: '        . $response->getReasonPhrase();     // OK
                $debug .= PHP_EOL .'Protocol: '      . $response->getProtocolVersion();  // 1.1
                
                $json = $response->getBody();
                array_push($this->responses, $json);
                $aryR = json_decode($json);

                $total_rows = $aryR->total;
                $msgs .= PHP_EOL .'**** total rows to process: '. $total_rows .' ***' . PHP_EOL;
                $batch = $this->mkGUID();
                set_time_limit(600);
                while ($ctr < $total_rows) {
                    foreach($aryR->data as $objR) {
                        $aryI = (array)$objR;
                        $aryI['batch'] = $batch;
                        $aryI['guid'] = $this->mkGUID();

                        $this->Mip_model->create_tblRow($aryI, $seg);
                        $ctr++;
                    }
                    $msgs .= 'done page: '. $pg_num . PHP_EOL;
                    $pg_num++;

                    $url = $base . '&page[number]='. $pg_num;
                    if ($ctr < $total_rows) {
                        $response = $cli->request( 'GET'
                            , $url
                            , [
                                    'allow_redirects' => false
                                    , 'body' => $bod
                                    , 'http_errors' => false
                                    , 'headers' => [ 'Content-type' => 'application/json'
                                            , 'Authorization-Token' => $this->acc_tokn
                                        ]
                                ]
                            );
                        $json = $response->getBody();
                        array_push($this->responses, $json);
                        $aryR = json_decode($json);
                    }
                    flush();
                } 
                $msgs .= '*** pages done :: got: '. $ctr .' rows'. PHP_EOL;
                $msgs .= PHP_EOL .'mip_tblAcctCode_'. $seg .' refresh done :: '. date('Y-m-d H:i:s');
            } else {
                $msgs .= '*** FAILED: unexpected status code'. PHP_EOL;
                $debug .= '*** FAILED: unexpected status code'. PHP_EOL;
            }
        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            #guzzle repose for future use
            $debug .= PHP_EOL .'**** ERROR!! (Guzzle)'. PHP_EOL;
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $debug .= print_r($responseBodyAsString, TRUE);
        } catch (Exception $x) {
            $debug .= PHP_EOL .'*** ERROR!'. PHP_EOL .
                    print_r($x, TRUE). PHP_EOL .'*** *** *** ***'. PHP_EOL;
        }
        $this->debug .= $debug;
        $dtEnd = strtotime(date('Y-m-d H:i:s'));
        $msgs .= PHP_EOL .'Time elapsed: '. ($dtEnd - $dtSta);

        echo $msgs;
    }

}
