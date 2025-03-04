<?php

set_time_limit(60);
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);

//echo '<pre>'. PHP_EOL;
// phpinfo();

// config
$adServer = 'ldap://ACAMRDC2';
$domain   = 'LSSNE';
$ldapuser = 'LSSNE\hageeadmin';
$ldappass = 'ERCcy@GTQrG5j';
$dn       = "dc=lssne,dc=domain"; // ph.Zbk.com";
$userDn   = "";

if(isset($_POST['username']) && isset($_POST['password'])){
	$ldap = ldap_connect($adServer);
	$username = $_POST['username'];
	$password = $_POST['password'];
	$ldaprdn  = $domain . '\\' . $username;
	$filter   = "(sAMAccountName=$username)";

	ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

	$bind = @ldap_bind($ldap, $ldaprdn, $password);
	if ($bind) {
		$result = ldap_search($ldap, $dn, $filter);
		$info = ldap_get_entries($ldap, $result);

		for ($i=0; $i < $info["count"]; $i++) {
		    echo "<p>You are accessing <strong> ". $info[$i]["cn"][0] .' ('. $info[$i]["description"][0] ."), " . 
		    		$info[$i]["givenname"][0] ."</strong><br /> {" . 
		    		$info[$i]["samaccountname"][0] ."}</p>\n";
		    $userDn = $info[$i]["distinguishedname"][0] ;
		    $userDn = $info[$i]["samaccountname"][0] ;
		    if (isset($info[$i]["memberof"][0])) {
				for ($j=0; $j<$info[$i]['memberof']['count']; $j++) {
					$grp = ldap_explode_dn($info[$i]['memberof'][$j],1);
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".
							"Gr: ". $grp[0] ."<br />";
				}
			}
		}
		echo '<pre>';
		var_dump($info);
		echo '</pre>';
		
		@ldap_close($ldap);
	} else {
		$msg = "<div class=\"err\">Invalid email address / password</div>";
		echo $msg;
	}
}
?>
<hr />
	<form action="#" method="POST">
		<label for="username">Username: </label><input id="username" type="text" name="username" value="<?= $userDn?>" /> 
		<label for="password">Password: </label><input id="password" type="password" name="password" />
		<input type="submit" name="submit" value="Submit" />
	</form>
