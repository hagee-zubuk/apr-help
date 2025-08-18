<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rest extends CI_Controller {
    public $loginfile;
    public $rest_url;
    public $acc_tokn;

    public function __construct() {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model('Mip_model');
        $this->loginfile = "C:\\Work\\helpers\\application\\tmp\\rest.json";
        $this->acc_tokn = '';
        $this->rest_url = '';

        if(file_exists($this->loginfile)) {
            $this->loadlogindata();
/*  $json = file_get_contents($this->loginfile, FALSE);
    $aryR = json_decode($json);
    if (isset($aryR['RestApiUrl'])) $this->rest_url = $aryR['RestApiUrl'];
    if (isset($aryR['AccessToken'])) $this->acc_tokn = $aryR['AccessToken'];
*/
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
        mt_srand((double)microtime() * 10000);
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
        if(file_exists($this->loginfile)) {
            $json = file_get_contents($this->loginfile, FALSE);
            if(is_null($json)) return FALSE;
            if(strlen($json) < 3) return FALSE;
            $aryR = json_decode($json);
            try {
                $this->rest_url = $aryR->RestApiUrl;
                $this->acc_tokn = $aryR->AccessToken;
            } catch(Exception $e) {
                return FALSE;
            }
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function index() {
        if(file_exists($this->loginfile)) {
            $dtFn = filectime($this->loginfile); // date("F d Y H:i:s.", filectime($this->loginfile) );
        } else {
            $dtFn = '&lt; none &gt;';
        }
        
        $dat = array('logfile' => $this->loginfile
                    , 'resturl' => $this->rest_url
                    , 'logtime' => $dtFn
                );
        $this->load->view('rest-page.php', $dat);
    }

    public function login() {
        //if (strlen($this->rest_url) > 7) {
        $this->logout();
        //}
        
        $this->load->library('Guzzle');
        $cli = new GuzzleHttp\Client( [ GuzzleHttp\RequestOptions::VERIFY => FALSE ] );
        // [ GuzzleHttp\RequestOptions::VERIFY => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() ] );

        $url = 'https://login.mip.com/api/v1/sso/mipadv/login';
        $bod =  json_encode(
                    array('username'  => 'api.read'
                        , 'password'  => 'blueSt@rt57'
                        , 'org' => 'ACA_13903_TEST'
                    )
                );
        // echo '<pre>';
        echo 'do login()..'. PHP_EOL;
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
            echo PHP_EOL .'Statuscode: '. $sta; // 200
            echo PHP_EOL .'Reason: '. $response->getReasonPhrase(); // OK
            echo PHP_EOL .'Protocol: '. $response->getProtocolVersion(); // 1.1
            $json = $response->getBody();
            echo PHP_EOL .'Body: '. $json . PHP_EOL . PHP_EOL;
            //$aryLog = array('login_statuscode' => $sta, )
            $aryR = json_decode($json, TRUE);
            if($sta < 300) {
                $aryR['login_statuscode'] = $sta;
                $aryR['rawJSON'] = $json;
                $aryR['guid'] = $this->mkGUID();
                $this->load->model('Login_model');
                $this->Login_model->create_logRow($aryR);
            }
            
            // var_dump($aryR);
            
            $intRet = file_put_contents($this->loginfile, $json);
            echo '** login OK. save returns: '. $intRet .'   '. PHP_EOL;
            
            // echo PHP_EOL;

        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            #guzzle repose for future use
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            // print_r($responseBodyAsString);
            echo '*** ERROR!'. PHP_EOL . PHP_EOL . PHP_EOL . $responseBodyAsString . PHP_EOL . PHP_EOL;
            var_dump($response);
        } catch(Exception $e) {
            echo '** ERROR!'. PHP_EOL;
            var_dump($e);
        } finally {
            echo date('Y-m-d H:i:s');
        }
        // echo PHP_EOL .'</pre>';
    }


    private function logout_actual($rec) {
        
        $acc_tokn = $rec['AccessToken'];

        $this->load->library('Guzzle');
        $cli = new GuzzleHttp\Client( [ GuzzleHttp\RequestOptions::VERIFY => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() ] );
        $url = 'https://login.mip.com/api/v1/sso/mipadv/logout';
        $bod = '';
        try {
            # guzzle post request example with form parameter
            $response = $cli->request( 'GET'
                    , $url
                    , [
                            'allow_redirects' =>  false
                            , 'body' => $bod
                            , 'headers' => [ 'Content-type' => 'application/json'
                                    , 'Authorization-Token' => $acc_tokn
                                ]
                        ]
                    );
            #guzzle repose for future use
            // echo PHP_EOL .'URL: '. $url; // 200
            $sta = $response->getStatusCode();
            // echo PHP_EOL .'Statuscode: '. $sta; // 200
            // echo PHP_EOL .'Reason: '. $response->getReasonPhrase(); // OK
            $json = $response->getBody();
            $aryR = json_decode($json);
            // $intRet = file_put_contents($this->loginfile, '');
            $rec['logout_response'] = $json;
            $rec['logout_statuscode'] = $sta;
            // echo PHP_EOL;
            // var_dump($aryR);
        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            #guzzle repose for future use
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $rec['logout_response'] = '** ERROR! '. $responseBodyAsString;
            $rec['logout_statuscode'] = 500;
            // print_r($responseBodyAsString);
            echo '*** ERROR!'. PHP_EOL . PHP_EOL . PHP_EOL . $responseBodyAsString . PHP_EOL . PHP_EOL;
            var_dump($response);
        } catch(Exception $e) {
            echo 'ERROR!'. PHP_EOL;
            var_dump($e);
            $rec['logout_response'] = '* ERROR! '. $e->getMessage();
        } finally {
            echo 'LOGOUT: '. date('Y-m-d H:i:s') . PHP_EOL;
        }
        return($rec);
    }


    public function logout() {
/*        if (strlen($this->rest_url) < 7) {

            echo 'not apparently logged in!';
            return;
        }
*/
        $this->load->model('Login_model');
        $logrecs = $this->Login_model->getLoginAccessToken();
        if ($logrecs !== FALSE) {
            $cnt = 0;
            foreach($logrecs as $l) {
                $guid = $l['guid'];
                if ($l['logout_statuscode'] !== 200) {
                    //echo 'GO'. PHP_EOL;
                    $ary = $this->logout_actual($l);
                    unset($ary['ix']);
                    unset($ary['ts']);
                    unset($ary['guid']);
                    $this->Login_model->update_LogRow($guid, $ary);
                } else {
                    echo 'INFO: skipping: '. $l['ix'] .PHP_EOL;
                }
                $cnt++;
                if ($cnt > 10) break;
            }
        } else {
            echo 'INFO: no rows'. PHP_EOL;
        }
        
    }


    public function getVendors() {
        if (strlen($this->rest_url) < 7) {
            // needs login!
            $this->login();
            $this->loadlogindata();
        }

        if (strlen($this->rest_url) < 7) {
            show_error('Login failure calling Vendor List', 500, $heading = 'An Error Was Encountered');
        }

        $this->load->library('Guzzle');
        $cli = new GuzzleHttp\Client( [ GuzzleHttp\RequestOptions::VERIFY => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() ] );

        $sz = 60;
        $pg_num = 0;
        $ctr = 0;
        $total_rows = 0;

        #This url define speific Target for guzzle
        $base = $this->rest_url .'api/v1/maintain/vendors?page[size]=' .$sz;
        $url = $base . '&page[number]='. $pg_num;
        $bod = '';
//        echo '<pre>';
        echo date('Y-m-d H:i:s') . PHP_EOL;
        $dtSta = strtotime(date('Y-m-d H:i:s'));
        #guzzle
        try {
            # guzzle post request example with form parameter
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
            #guzzle repose for future use
            echo PHP_EOL .'URL: '. $url; // 200
            echo PHP_EOL .'Statuscode: '. $response->getStatusCode(); // 200
            echo PHP_EOL .'Reason: '. $response->getReasonPhrase(); // OK
            $json = $response->getBody();
            $aryR = json_decode($json);
            echo PHP_EOL;
            if (property_exists($aryR, 'total')) {
                $total_rows = $aryR->total;
                echo PHP_EOL .'**** total rows to process: '. $total_rows .' ***' . PHP_EOL;
            } else {
                $total_rows = 0;
                echo PHP_EOL .'*** ERROR!'. PHP_EOL .'REST call returned unexpected schema:'. PHP_EOL . PHP_EOL;
                echo $json . PHP_EOL . PHP_EOL . PHP_EOL;
                return FALSE;
            }
            echo PHP_EOL. 'Type: '. $aryR->type;
            echo PHP_EOL .'got: '. ($sz * $pg_num) .' of '. $total_rows .' in '. $sz . '-row chunks'. PHP_EOL;
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
                            );
                    

                    $this->Mip_model->create_Vendor($aryI);
                    $ctr++;
                    unset($aryI);
                }
                echo 'done page: '. $pg_num . PHP_EOL;
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
            echo '*** pages done: '. $ctr .' rows'. PHP_EOL;
        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            #guzzle repose for future use
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            print_r($responseBodyAsString);
        } catch (Exception $x) {
            echo PHP_EOL .'*** ERROR!'. PHP_EOL;
            var_dump($x);
            echo PHP_EOL .'*** *** *** ***'. PHP_EOL;
        }
        $dtEnd = strtotime(date('Y-m-d H:i:s'));
        echo PHP_EOL .'mip_tblAPVendor refresh :: time elapsed: '. ($dtEnd - $dtSta) .'s'. PHP_EOL;
        echo date('Y-m-d H:i:s');
//        echo PHP_EOL .'</pre>';

        $this->logout();
    }


    public function clrCombi() {
        $this->Mip_model->truncate_Combo();
    }

    public function getCombi()  {
        ob_start();
        if (strlen($this->rest_url) < 7) {
            // needs login!
            $this->login();
            $this->loadlogindata();
        }

        if (strlen($this->rest_url) < 7) {
            show_error('Login failure calling Combination List', 500, $heading = 'An Error Was Encountered');
        }

        $this->load->library('Guzzle');
        $cli = new GuzzleHttp\Client( [ GuzzleHttp\RequestOptions::VERIFY => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() ] );

        $sz = 50;
        $total_rows = 0;

        $base = $this->rest_url .'api/v1/maintain/accountcodecombinations?page[size]='. $sz;
        $url  = $this->rest_url .'api/v1/maintain/accountcodecombinations?page[size]=2';
        $bod = '';
        //echo '<pre>';
        $dtSta = strtotime(date('Y-m-d H:i:s'));

        try {
            $response = $cli->request( 'GET'
                    , $url
                    , [     'allow_redirects' => false, 'body' => $bod, 'headers' => [
                                'Content-type' => 'application/json'
                                , 'Authorization-Token' => $this->acc_tokn
                                ]
                        ]
                    );
            echo PHP_EOL .'URL: '. $url . PHP_EOL .'Statuscode: '. $response->getStatusCode() . PHP_EOL .'Reason: '. $response->getReasonPhrase() .PHP_EOL; // OK
            $json = $response->getBody();
            $aryR = json_decode($json);
            if (property_exists($aryR, 'total')) {
                $total_rows = $aryR->total;
                echo PHP_EOL .'**** total rows to process: '. $total_rows .' ***' .
                        PHP_EOL .'Type: '. $aryR->type . PHP_EOL;
            } else {
                $total_rows = 0;
                echo PHP_EOL .'*** ERROR!'. PHP_EOL .'REST call returned unexpected schema:'. PHP_EOL . PHP_EOL;
                echo $json . PHP_EOL . PHP_EOL . PHP_EOL;
                return FALSE;
            }

            echo PHP_EOL .'should get: '. $total_rows .' rows in '. $sz . '-row chunks'. PHP_EOL . PHP_EOL;

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
                $aryR = json_decode($json);
                if (property_exists($aryR, 'total')) {
                    // if ($pg_num > 24) { var_dump($aryR) . PHP_EOL; }
                    if ($aryR->total <= 0) {
                        echo PHP_EOL. 'last URL: '. $url . PHP_EOL;
                        echo 'JSON: '. $json . PHP_EOL;
                        break;
                    }
                } else {
                    echo 'last URL: '. $url . PHP_EOL;
                    var_dump($aryR);
                    break;
                }

                echo $ctr .' :: '. count($aryR->data) .' :: ';
                foreach($aryR->data as $objR) {
                    $aryI = (array)$objR;
                    $aryI['batch'] = $batch;
                    $aryI['guid'] = $this->mkGUID();

                    $this->Mip_model->create_Combo($aryI);
                    $ctr++;
                    unset($aryI);
                }
                echo 'done page: '. $pg_num .' ('. $ctr .'/'. $total_rows .')'. PHP_EOL;
                $pg_num++;

                unset($aryR);
                $objR = NULL;
                
            }
            // echo PHP_EOL. $json .PHP_EOL.PHP_EOL;
            echo '*** pages done: '. $ctr .' rows'. PHP_EOL;
        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            #guzzle repose for future use
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            print_r($responseBodyAsString);
        } catch (Exception $x) {
            echo PHP_EOL .'*** ERROR!'. PHP_EOL;
            var_dump($x);
            echo PHP_EOL .'*** *** *** ***'. PHP_EOL;
        }
        $dtEnd = strtotime(date('Y-m-d H:i:s'));
        echo PHP_EOL .'Time elapsed: '. ($dtEnd - $dtSta) .'s'. PHP_EOL;
        echo 'mip_tblComboEdit :: '. date('Y-m-d H:i:s');
        // echo PHP_EOL .'</pre>';
        if($this->input->is_cli_request()) {
            $out = ob_get_clean();
        } else {
            ob_flush();
        }

        $this->logout();
    }


    public function getChOA()  {
        if (strlen($this->rest_url) < 7) {
            // needs login!
            $this->login();
            $this->loadlogindata();
        }

        if (strlen($this->rest_url) < 7) {
            show_error('Login failure calling Chart-of-Accounts', 500, $heading = 'An Error Was Encountered');
        }

        $this->load->library('Guzzle');
        $cli = new GuzzleHttp\Client( [ GuzzleHttp\RequestOptions::VERIFY => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath() ] );

        $sz = 50;
        $pg_num = 0;
        $ctr = 0;
        $total_rows = 0;
        #This url define speific Target for guzzle

        $base = $this->rest_url .'api/v1/maintain/ChartOfAccounts?page[size]=' .$sz;
        $url = $base . '&page[number]='. $pg_num;
        $bod = '';
//        echo '<pre>';
        $dtSta = strtotime(date('Y-m-d H:i:s'));
        #guzzle
        try {
            # guzzle post request example with form parameter
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
            #guzzle repose for future use
            echo PHP_EOL .'URL: '. $url; // 200
            echo PHP_EOL .'Statuscode: '. $response->getStatusCode(); // 200
            echo PHP_EOL .'Reason: '. $response->getReasonPhrase(); // OK
            $json = $response->getBody();
            $aryR = json_decode($json);
            echo PHP_EOL;
            if (property_exists($aryR, 'total')) {
                $total_rows = $aryR->total;
                echo PHP_EOL .'**** total rows to process: '. $total_rows .' ***' . PHP_EOL;
            } else {
                $total_rows = 0;
                echo PHP_EOL .'*** ERROR!'. PHP_EOL .'REST call returned unexpected schema:'. PHP_EOL . PHP_EOL;
                echo $json . PHP_EOL . PHP_EOL . PHP_EOL;
                return FALSE;
            }
            
            
            echo PHP_EOL .'got: '. ($sz * $pg_num) .' of '. $total_rows .' in '. $sz . '-row chunks'. PHP_EOL;
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
                        echo 'discarding: '. $objR->accountTitle .' [seg=' . $seg .']'. PHP_EOL;
                    } else {
                        $this->Mip_model->create_tblRow($aryI, $seg);
                    }
                    $ctr++;
                }
                echo 'done page: '. $pg_num . PHP_EOL;
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
            echo '*** pages done :: got '. $ctr .' rows'. PHP_EOL;
        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            #guzzle repose for future use
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            print_r($responseBodyAsString);
        } catch (Exception $x) {
            echo PHP_EOL .'*** ERROR!'. PHP_EOL;
            var_dump($x);
            echo PHP_EOL .'*** *** *** ***'. PHP_EOL;
        }
        $dtEnd = strtotime(date('Y-m-d H:i:s'));
        echo PHP_EOL .'Time elapsed: '. ($dtEnd - $dtSta);
        echo 'mip_tblAcctCode_xx refresh done :: '. date('Y-m-d H:i:s');
//        echo PHP_EOL .'</pre>';

        $this->logout();
    }


    public function getTAC($seg = 0)  {
        if (strlen($this->rest_url) < 7) {
            // needs login!
            echo '<p>*** Relogin</p>'. PHP_EOL;
            $this->login();
            $this->loadlogindata();
        }

        if (strlen($this->rest_url) < 7) {
            show_error('Login failure calling tblAcctCode-equivalent', 500, $heading = 'An Error Was Encountered');
        }

        $seg = intval($seg);
        if ( ($seg != 0) && ($seg != 1) && ($seg != 10) && ($seg != 2) && ($seg != 3) && ($seg != 4) && ($seg != 5) && ($seg != 7) && ($seg != 9) ) {
            show_error('Unknown segment!', 500, $heading = 'An Error Was Encountered');
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
        //echo '<pre>';
        echo 'Started: '. date('Y-m-d H:i:s') . PHP_EOL;
        $dtSta = strtotime(date('Y-m-d H:i:s'));

        try {
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
            //echo PHP_EOL .'URL: '. $url; // 200
            echo PHP_EOL .'Statuscode: '. $response->getStatusCode(); // 200
            echo PHP_EOL .'Reason: '. $response->getReasonPhrase(); // OK
            echo PHP_EOL .'Protocol: '. $response->getProtocolVersion(); // 1.1
            $json = $response->getBody();
            $aryR = json_decode($json);
            echo PHP_EOL;
            $total_rows = $aryR->total;
            echo PHP_EOL .'**** total rows to process: '. $total_rows .' ***' . PHP_EOL;
            flush();
            // $this->Mip_model->truncate_tblRow($seg);
            $batch = $this->mkGUID();
            set_time_limit(600);
            while ($ctr < $total_rows) {
                foreach($aryR->data as $objR) {
                    $aryI = (array)$objR;
                    $aryI['batch'] = $batch;
                    $aryI['guid'] = $this->mkGUID();

                    // $this->Mip_model->create_tblRow($aryI, $seg);
                    $ctr++;
                }
                echo 'done page: '. $pg_num . PHP_EOL;
                $pg_num++;

                $url = $base . '&page[number]='. $pg_num;
                if ($ctr < $total_rows) {
                    $response = $cli->request( 'GET'
                        , $url
                        , [
                                'allow_redirects' => false, 'body' => $bod
                                , 'headers' => [ 'Content-type' => 'application/json'
                                        , 'Authorization-Token' => $this->acc_tokn
                                    ]
                            ]
                        );
                    $json = $response->getBody();
                    $aryR = json_decode($json);
                }
                flush();
            } 
            echo '*** pages done :: got: '. $ctr .' rows'. PHP_EOL;
        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            #guzzle repose for future use
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            print_r($responseBodyAsString);
        } catch (Exception $x) {
            echo PHP_EOL .'*** ERROR!'. PHP_EOL;
            var_dump($x);
            echo PHP_EOL .'*** *** *** ***'. PHP_EOL;
        }
        $dtEnd = strtotime(date('Y-m-d H:i:s'));
        echo PHP_EOL .'Time elapsed: '. ($dtEnd - $dtSta);
        echo PHP_EOL .'mip_tblAcctCode_'. $seg .' refresh done :: '. date('Y-m-d H:i:s');
        // echo PHP_EOL .'</pre>';

        $this->logout();
    }

}
