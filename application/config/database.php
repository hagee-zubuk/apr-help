<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
*/
$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
	'dsn'	   => '',
	'hostname' => 'TSURU',
	'username' => 'ascentria',
	'password' => 'zubuk#zubuk',
	'database' => 'tmp_apr',
	'dbdriver' => 'sqlsrv',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => TRUE,			/*(ENVIRONMENT !== 'production'), */
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'UTF-8',		/* GODDAMNED MICROSOFT! */
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt'  => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => FALSE
);
