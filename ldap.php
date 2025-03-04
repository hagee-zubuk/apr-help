<?php

set_time_limit(60);
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);

//echo '<pre>'. PHP_EOL;
// phpinfo();

// config
$ldapserver = 'ldap://ACAMRDC2';
$ldapuser   = 'LSSNE\hageeadmin';
$ldappass   = 'ERCcy@GTQrG5j';
$dn         = "dc=lssne,dc=domain"; // ph.Zbk.com";
$srch       = "cn=debor*";
//$srch       = '(|(objectCategory=person)(objectCategory=contact))';
//$srch       = '(&(&(&(objectCategory=person)(objectClass=user))))';
// connect
$ldapconn = ldap_connect($ldapserver) or die("Could not connect to LDAP server.");

if ($ldapconn) {
	ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

	// binding to ldap server
	$ldapbind = ldap_bind($ldapconn, $ldapuser, $ldappass) or die ("Error trying to bind: ".ldap_error($ldapconn));

	// verify binding
	if ($ldapbind) {
		/*
		echo "LDAP bind successful...<br /><br />"; 
		$res = ldap_list($ldapconn, $dn, "ou=*");
		echo 'List Result<br /><pre>'. PHP_EOL;
		print_r($res);
		echo PHP_EOL .'</pre>';
		$data = ldap_get_entries($ldapconn, $res);
		// SHOW ALL DATA
		echo '<h1>Dump List</h1><pre>';
		print_r($data);   
		echo '</pre>';
		unset($data);
		*/

		$result = ldap_search($ldapconn, $dn, $srch) or die ("Error in search query: ".ldap_error($ldapconn));
		$data = ldap_get_entries($ldapconn, $result);

		// SHOW ALL DATA
		/*
		echo '<h1>Dump all data</h1><pre>';
		print_r($data);   
		echo '</pre>';
		*/

		// iterate over array and print data for each entry
		echo '<h1>Show me the users</h1>';
		for ($i=0; $i<$data["count"]; $i++) {
			echo "dn is: ". $data[$i]["dn"] ."<br />";
			echo "User: <b>". $data[$i]["cn"][0] ."</b><br />";
			if (isset($data[$i]["mail"][0])) {
				echo "Email: ". $data[$i]["mail"][0] ."<br />";
			}
			if (isset($data[$i]["memberof"][0])) {
				for ($j=0; $j<$data[$i]['memberof']['count']; $j++) {
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".
							"Gr: ". $data[$i]['memberof'][$j] ."<br />";
				}
			} else {
				echo "Member of's: None<br /><br />";
			}
			echo '<br /><br />';
		}
		// print number of entries found
		echo "Number of entries found: " . ldap_count_entries($ldapconn, $result);
	} else {
		echo "LDAP bind failed...";
	}
}

// all done? clean up
ldap_close($ldapconn);

/*
$cred = explode('\\',$_SERVER['REMOTE_USER']);
var_dump($cred);
if (count($cred) == 1) array_unshift($cred, "(no domain info - perhaps SSPIOmitDomain is On)");
list($domain, $user) = $cred;

var_dump($user);

var_dump($domain);
//return $user;
*/
// echo PHP_EOL .'</pre>'. PHP_EOL;
?>