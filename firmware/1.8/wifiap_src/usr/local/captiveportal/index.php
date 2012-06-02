#!/usr/local/bin/php
<?php 
/*
    $Id: index.php 404 2010-08-25 15:41:31Z mkasper $
    part of m0n0wall (http://m0n0.ch/wall)
    
    Copyright (C) 2003-2007 Manuel Kasper <mk@neon1.net>.
    All rights reserved.
    
    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:
    
    1. Redistributions of source code must retain the above copyright notice,
       this list of conditions and the following disclaimer.
    
    2. Redistributions in binary form must reproduce the above copyright
       notice, this list of conditions and the following disclaimer in the
       documentation and/or other materials provided with the distribution.
    
    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.
*/

require_once("functions.inc");

header("Expires: 0");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$orig_host = $_ENV['HTTP_HOST'];
$orig_request = $_ENV['CAPTIVE_REQPATH'];
$clientip = $_ENV['REMOTE_ADDR'];

if (!$clientip) {
    /* not good - bail out */
    exit;
}

if (isset($config['captiveportal']['httpslogin'])) {
    $ourhostname = $config['captiveportal']['httpsname'] . ":8001";
    $oururl = "https://{$ourhostname}/";
} else {
    $ourhostname = $config['interfaces'][$config['captiveportal']['interface']]['ipaddr'] . ":8000";
    $oururl = "http://{$ourhostname}/";
}

if ($orig_host != $ourhostname) {
    /* the client thinks it's connected to the desired web server, but instead
       it's connected to us. Issue a redirect... */
      
    header("Location: {$oururl}?redirurl=" . urlencode("http://{$orig_host}{$orig_request}"));
    exit;
}

$redirurl = $oururl;
if (preg_match("/redirurl=(.*)/", $orig_request, $matches))
    $redirurl = urldecode($matches[1]);
if ($_POST['redirurl'])
    $redirurl = $_POST['redirurl'];

$macfilter = !isset($config['captiveportal']['nomacfilter']);

/* find MAC address for client */
$clientmac = arp_get_mac_by_ip($clientip);
if (!$clientmac && $macfilter) {
    /* unable to find MAC address - shouldn't happen! - bail out */
    captiveportal_logportalauth("unauthenticated","noclientmac",$clientip,"ERROR");
    exit;
}

/* find out if we need RADIUS + RADIUSMAC or not */
if (file_exists("{$g['vardb_path']}/captiveportal_radius.db")) {
    $radius_enable = TRUE;
    if ($radius_enable && isset($config['captiveportal']['radmac_enable']))
        $radmac_enable = TRUE;
}

if ($_POST['logout_id']) {
    disconnect_client($_POST['logout_id']);
    setcookie('sessionid', '', 1);
    if ($_POST['logout_popup']&&1==0) {
    echo <<<EOD
<HTML>
<HEAD><TITLE>Disconnecting...</TITLE></HEAD>
<BODY BGCOLOR="#435370">
<SPAN STYLE="color: #ffffff; font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif; font-size: 11px;">
<B>You've been disconnected.</B>
</SPAN>
<SCRIPT LANGUAGE="JavaScript">
<!--
setTimeout('window.close();',5000) ;
-->
</SCRIPT>
</BODY>
</HTML>
EOD;
    } else
        portal_reply_page($redirurl, "logout");
    exit;
}
$sessionid = $_COOKIE['sessionid'];
/* we received a cookie with a sessionid */
if (isset($_COOKIE['sessionid'])) {
    /* read in client database */
    $cpdb = captiveportal_read_db();
    $sessionid = $_COOKIE['sessionid'];
    for ($i = 0; $i < count($cpdb); $i++) {
        /* look for sessionid and ip */
        if(($cpdb[$i][5] == $sessionid) && ($cpdb[$i][2] == $clientip)) {

            /* change password requested */
            if (($config['captiveportal']['auth_method'] == "local") && $_POST['change_pass'] && $_POST['oldpass'] && $_POST['newpass'] && $_POST['newpass2']) {
                if ($_POST['newpass'] != $_POST['newpass2']) {
                    $msg = "Error: Passwords do not match";
                } else if ($_POST['newpass'] == $_POST['oldpass']) {
                    $msg = "Error: Choose a different password";
                } else {
                    /* look for user */
                    $a_user = &$config['captiveportal']['user'];
                    for ($j = 0; $j < count($a_user); $j++) {
                        if ($a_user[$j]['name'] == $cpdb[$i][4]) {
                            if (md5($_POST['oldpass']) == $a_user[$j]['password']) {
                                /* change password */
                                $a_user[$j]['password'] = md5($_POST['newpass']);
                            }
                            else {
                                $msg = "错误: 旧密码不正确";
                                captiveportal_syslog("FAILED PASSWORD CHANGE: ".$cpdb[$i][4].", ".$clientmac.", ".$clientip);
                            }
                            break;
                        }
                    }
                }
                if (!isset($msg)) {
                    write_config();
                    captiveportal_syslog("密码已修改: ".$cpdb[$i][4].", ".$clientmac.", ".$clientip);
                    $msg = "密码已修改";
                }
            }
            portal_reply_page($redirurl, "status", $msg);
            exit;
        }
    }
    /* sessionid not found */
    unset($sessionid);
    setcookie('sessionid', '', 1);
}
/* The $macfilter can be removed safely since we first check if the $clientmac is present, if not we fail */
if ($clientmac && portal_mac_fixed($clientmac)) {
    /* punch hole in ipfw for pass thru mac addresses */
    portal_allow($clientip, $clientmac, "unauthenticated");
    exit;

} else if ($clientmac && $radmac_enable && portal_mac_radius($clientmac,$clientip)) {
    /* radius functions handle everything so we exit here since we're done */
    exit;

} else if ($_POST['accept'] && $_POST['auth_voucher']) {

    $voucher = trim($_POST['auth_voucher']);
    $timecredit = voucher_auth($voucher);
    // $timecredit contains either a credit in minutes or an error message
    if ($timecredit > 0) {  // voucher is valid. Remaining minutes returned
        // if multiple vouchers given, use the first as username
        $a_vouchers = split("[\t\n\r ]+",$voucher);
        $voucher = $a_vouchers[0];
        $attr = array( 'voucher' => 1,
                'session_timeout' => $timecredit*60,
                'session_terminate_time' => 0);
        if (portal_allow($clientip, $clientmac,$voucher,null,$attr)) {

            // YES: user is good for $timecredit minutes.
            captiveportal_logportalauth($voucher,$clientmac,$clientip,"VOUCHER LOGIN good for $timecredit min.");
        } else {
            portal_reply_page($redirurl, "error", $config['voucher']['msgexpired']);
        }
    } else if (-1 == $timecredit) {  // valid but expired
        captiveportal_logportalauth($voucher,$clientmac,$clientip,"FAILURE","voucher expired");
        portal_reply_page($redirurl, "error", $config['voucher']['msgexpired']);
    } else {
        captiveportal_logportalauth($voucher,$clientmac,$clientip,"FAILURE");
        portal_reply_page($redirurl, "error", $config['voucher']['msgnoaccess']);
    }

} else if ($_POST['accept'] && $radius_enable) {

    if ($_POST['auth_user'] && $_POST['auth_pass']) {
        $auth_list = radius($_POST['auth_user'],$_POST['auth_pass'],$clientip,$clientmac,"USER LOGIN");

        if ($auth_list['auth_val'] == 1) {
            captiveportal_logportalauth($_POST['auth_user'],$clientmac,$clientip,"ERROR",$auth_list['error']);
            portal_reply_page($redirurl, "error", $auth_list['error']);
        }
        else if ($auth_list['auth_val'] == 3) {
            captiveportal_logportalauth($_POST['auth_user'],$clientmac,$clientip,"FAILURE",$auth_list['reply_message']);
            portal_reply_page($redirurl, "error", $auth_list['reply_message']);
        }
    } else {
        captiveportal_logportalauth($_POST['auth_user'],$clientmac,$clientip,"ERROR");
        portal_reply_page($redirurl, "error");
    }

}else if(isset($_POST['verifycode']) && $config['captiveportal']['auth_method'] == 'sms'){
	$mobile=$_POST['mobile'];
	$a_user = &$config['captiveportal']['user'];
	
	if(strlen($mobile)==11 && is_numeric($mobile)){
		$smsapiurl=$config['captiveportal']['auth_sms_chnl'];
		$smsapiurl = str_replace("{mobile}", $mobile, $smsapiurl);
		$username=createRandomStr(8,1);
		$username_flag=true;
		if(is_array($a_user)){
			foreach ($a_user as $userent) {
					if ($userent['name'] == $username) {
						$username=createRandomStr(8,1);
						$username_flag=false;
						break;
					}
			}
		}
		
		if($username_flag==flase){
			$username_flag=true;
			if(is_array($a_user)){
				foreach ($a_user as $userent) {
						if ($userent['name'] == $username) {
							$username=createRandomStr(8,1);
							$username_flag=false;
							break;
						}
				}
			}
		}
		if($username_flag==false){
			$msg='验证码发送失败，请重试。|'.$username;
		}else{
			$msg= str_replace("{verifycode}", $username, $config['captiveportal']['auth_sms_text']);
			$smsapiurl = str_replace("{msg}", $msg, $smsapiurl);
			
			if(strlen($config['captiveportal']['auth_sms_chnl'])>0){
				$valback = file_get_contents($smsapiurl);
			}else{
				$valback = $config['captiveportal']['auth_sms_ok_flag'];
			}
			if(strlen($valback)>0 && strpos($valback,$config['captiveportal']['auth_sms_ok_flag'])===false){
				$msg='验证码发送失败，请重试。';
			}else{
				$userent=array();
				$userent['name'] = $username;
				$userent['fullname'] = $mobile;
				$userent['password'] = md5('');
				$userent['expirationdate'] = '';
				$userent['type'] = 'sms';
				$userent['time'] = date("Y-m-d H:i:s");
				if($config['captiveportal']['auth_sms_utt']=='0'){
					$userent['utt']= '0';
				}else{
					$userent['utt']= '1';
				}
				
				$a_user[] = $userent;
				
				write_config();
			
				if(strlen($config['captiveportal']['auth_sms_chnl'])>0){
					$msg='验证码发送成功';
				}else{
					$msg='验证码发送成功. 验证码为：'.$username;
				}
				
				
			}
		}
	}else{
		$msg='请正确输入手机号码';
	}
	portal_reply_page($redirurl, "login", '', $msg);
	exit(0);
}else if ($_POST['accept'] && ($config['captiveportal']['auth_method'] == "local" || $config['captiveportal']['auth_method'] == "sms")) {

    //check against local usermanager
    $userdb = &$config['captiveportal']['user'];

    $loginok = false;

    //erase expired accounts
    if (is_array($userdb)) {
        $moddb = false;
        for ($i = 0; $i < count($userdb); $i++) {
            if ($userdb[$i]['expirationdate'] && (strtotime("-1 day") > strtotime($userdb[$i]['expirationdate']))) {
                unset($userdb[$i]);
                $moddb = true;
            }
        }
        if ($moddb)
            write_config();

        $userdb = &$config['captiveportal']['user'];

        for ($i = 0; $i < count($userdb); $i++) {
            if (($userdb[$i]['name'] == $_POST['auth_user']) && ($userdb[$i]['password'] == md5($_POST['auth_pass']))) {
                $loginok = true;
                break;
            }
        }
    }

    if ($loginok){
        captiveportal_logportalauth($_POST['auth_user'],$clientmac,$clientip,"LOGIN");
        portal_allow($clientip, $clientmac,$_POST['auth_user']);
    } else {
        captiveportal_logportalauth($_POST['auth_user'],$clientmac,$clientip,"FAILURE");
        portal_reply_page($redirurl, "error");
    }
}else if ($_POST['accept'] && $config['captiveportal']['auth_method'] == "api") {
		$username=$_POST['auth_user'];
		$password=$_POST['auth_pass'];
		$rnd = createRandomStr(8);
		$encrypwd=md5($config['captiveportal']['auth_api_key'].$password.$rnd);
		$apiurl=$config['captiveportal']['auth_api_url'].'?username='.$username.'&password='.$encrypwd.'&rnd='.$rnd;
		//get api to check user
		$result = file_get_contents($apiurl);

    $loginok = false;
    
    if($result===$config['captiveportal']['auth_api_success_flag']){
    	$loginok = true;
    }

    if ($loginok){
        captiveportal_logportalauth($_POST['auth_user'],$clientmac,$clientip,"LOGIN");
        portal_allow($clientip, $clientmac,$_POST['auth_user']);
    } else {
        captiveportal_logportalauth($_POST['auth_user'],$clientmac,$clientip,"FAILURE");
        portal_reply_page($redirurl, "error",$result);
    }
}else if ($_POST['accept'] && $clientip) {
    captiveportal_logportalauth("unauthenticated",$clientmac,$clientip,"ACCEPT");
    portal_allow($clientip, $clientmac, "unauthenticated");

} else {
    /* display captive portal page */
    portal_reply_page($redirurl, "login");
}

exit;

function portal_reply_page($redirurl, $type = null, $message = null, $message1 = null) {
    global $g, $config, $oururl, $sessionid;

		$mydevice = mobile_device_detect(true,false,true,true,true,true,true);
		if($mydevice==false||$mydevice==''){
			$mydevice = ''; 
		}else{
			if(!empty($mydevice[1])){
				if($mydevice[1]=='ipad' || $mydevice[1]=='iphone' || $mydevice[1]=='android' || $mydevice[1]=='smartphone' || $mydevice[1]=='blackberry' || $mydevice[1]=='opera' || $mydevice[1]=='palm'){
					$mydevice = $mydevice[1];
				}else{
					$mydevice = ''; 
				}
			}
		}

    /* Get captive portal layout */
    if ($type == "login"){
    	  if($mydevice ==''){
        	$htmltext = file_get_contents("{$g['varetc_path']}/captiveportal.html");
        }else{
        	if(file_exists("{$g['varetc_path']}/captiveportal_{$mydevice}.html")){
        		$htmltext = file_get_contents("{$g['varetc_path']}/captiveportal_{$mydevice}.html");
        	}else{
        		$htmltext = file_get_contents("{$g['varetc_path']}/captiveportal.html");
        	}
        }
    }else if ($type == "status"){
    		if($mydevice ==''){
        	$htmltext = file_get_contents("{$g['varetc_path']}/captiveportal-status.html");
        }else{
        	if(file_exists("{$g['varetc_path']}/captiveportal-status_{$mydevice}.html")){
        		$htmltext = file_get_contents("{$g['varetc_path']}/captiveportal-status_{$mydevice}.html");
        	}else{
        		$htmltext = file_get_contents("{$g['varetc_path']}/captiveportal-status.html");
        	}
        }
    }else if ($type == "logout"){
    		if($mydevice ==''){
        	$htmltext = file_get_contents("{$g['varetc_path']}/captiveportal-logout.html");
        }else{
        	if(file_exists("{$g['varetc_path']}/captiveportal-logout_{$mydevice}.html")){
        		$htmltext = file_get_contents("{$g['varetc_path']}/captiveportal-logout_{$mydevice}.html");
        	}else{
        		$htmltext = file_get_contents("{$g['varetc_path']}/captiveportal-logout.html");
        	}
        }
    }else{
    		if($mydevice ==''){
        	$htmltext = file_get_contents("{$g['varetc_path']}/captiveportal-error.html");
        }else{
        	if(file_exists("{$g['varetc_path']}/captiveportal-error_{$mydevice}.html")){
        		$htmltext = file_get_contents("{$g['varetc_path']}/captiveportal-error_{$mydevice}.html");
        	}else{
        		$htmltext = file_get_contents("{$g['varetc_path']}/captiveportal-error.html");
        	}
        }
     }

    /* substitute other variables */
    $htmltext = str_replace("\$PORTAL_ACTION\$", $oururl, $htmltext);
    $htmltext = str_replace("\$PORTAL_SESSIONID\$", $sessionid, $htmltext);

    $htmltext = str_replace("\$PORTAL_REDIRURL\$", htmlspecialchars($redirurl), $htmltext);
    $htmltext = str_replace("\$PORTAL_MESSAGE\$", htmlspecialchars($message), $htmltext);
    $htmltext = str_replace("\$PORTAL_MESSAGE1\$", htmlspecialchars($message1), $htmltext);

    echo $htmltext;
}

function portal_mac_radius($clientmac,$clientip) {
    global $config ;

    $radmac_secret = $config['captiveportal']['radmac_secret'];

    /* authentication against the radius server */
    $username = mac_format($clientmac);
    $auth_list = radius($username,$radmac_secret,$clientip,$clientmac,"MACHINE LOGIN");
    if ($auth_list['auth_val'] == 2) {
        return TRUE;
    }
    return FALSE;
}

function portal_allow($clientip,$clientmac,$username,$password = null, $attributes = null, $ruleno = null)  {

    global $redirurl, $g, $config;

    /* See if a ruleno is passed, if not start locking the sessions because this means there isn't one atm */
    if (is_null($ruleno)) {
        captiveportal_lock();
        $ruleno = captiveportal_get_next_ipfw_ruleno();

    /* if the pool is empty, return appropriate message and exit */
    if (is_null($ruleno)) {
        portal_reply_page($redirurl, "error", "System reached maximum login capacity");
        captiveportal_unlock();
        exit;
    }
    }

    // Ensure we create an array if we are missing attributes
    if (!is_array($attributes))
        $attributes = array();

    /* read in client database */
    $cpdb = captiveportal_read_db();

    $radiusservers = captiveportal_get_radius_servers();

    if ($attributes['voucher']) {
        $remaining_time = $attributes['session_timeout'];
    }

    /* Find an existing session */
    for ($i = 0; $i < count($cpdb); $i++) {
        /* on the same ip */
        if($cpdb[$i][2] == $clientip) {
            captiveportal_logportalauth($cpdb[$i][4],$cpdb[$i][3],$cpdb[$i][2],"CONCURRENT LOGIN - REUSING OLD SESSION");
            $sessionid = $cpdb[$i][5];
            break;
        }
        elseif (($attributes['voucher']) && ($username != 'unauthenticated') && ($cpdb[$i][4] == $username)) {
            // user logged in with an active voucher. Check for how long and calculate 
            // how much time we can give him (voucher credit - used time)
            $remaining_time = $cpdb[$i][0] + $cpdb[$i][7] - time();
            if ($remaining_time < 0)    // just in case. 
                $remaining_time = 0;

            /* This user was already logged in so we disconnect the old one */
            captiveportal_disconnect($cpdb[$i],$radiusservers,13);
            captiveportal_logportalauth($cpdb[$i][4],$cpdb[$i][3],$cpdb[$i][2],"CONCURRENT LOGIN - TERMINATING OLD SESSION");
            unset($cpdb[$i]);
            break;
        }
        elseif ((isset($config['captiveportal']['noconcurrentlogins'])) && ($username != 'unauthenticated')) {
            /* on the same username */
            if (strcasecmp($cpdb[$i][4], $username) == 0) {
                /* This user was already logged in so we disconnect the old one */
                captiveportal_disconnect($cpdb[$i],$radiusservers,13);
                captiveportal_logportalauth($cpdb[$i][4],$cpdb[$i][3],$cpdb[$i][2],"CONCURRENT LOGIN - TERMINATING OLD SESSION");
                unset($cpdb[$i]);
                break;
            }
        }
    }

    if ($attributes['voucher'] && $remaining_time <= 0) {
        captiveportal_unlock();
        return 0;       // voucher already used and no time left
    }

    if (!isset($sessionid)) {

        /* generate unique session ID */
        $tod = gettimeofday();
        $sessionid = substr(md5(mt_rand() . $tod['sec'] . $tod['usec'] . $clientip . $clientmac), 0, 16);

        /* Add rules for traffic shaping
         * We don't need to add extra l3 allow rules since traffic will pass due to the following kernel option
         * net.inet.ip.fw.one_pass: 1
         */
        $peruserbw = isset($config['captiveportal']['peruserbw']);

        $bw_up = isset($attributes['bw_up']) ? trim($attributes['bw_up']) : $config['captiveportal']['bwdefaultup'];
        $bw_down = isset($attributes['bw_down']) ? trim($attributes['bw_down']) : $config['captiveportal']['bwdefaultdn'];

        if ($peruserbw && !empty($bw_up) && is_numeric($bw_up)) {
            $bw_up_pipeno = $ruleno + 40500;
            exec("/sbin/ipfw add $ruleno set 2 pipe $bw_up_pipeno ip from $clientip to any in");
            exec("/sbin/ipfw pipe $bw_up_pipeno config bw {$bw_up}Kbit/s queue 100");
        } else {
            exec("/sbin/ipfw add $ruleno set 2 skipto 50000 ip from $clientip to any in");
        }
        if ($peruserbw && !empty($bw_down) && is_numeric($bw_down)) {
            $bw_down_pipeno = $ruleno + 45500;
            exec("/sbin/ipfw add $ruleno set 2 pipe $bw_down_pipeno ip from any to $clientip out");
            exec("/sbin/ipfw pipe $bw_down_pipeno config bw {$bw_down}Kbit/s queue 100");
        } else {
            exec("/sbin/ipfw add $ruleno set 2 skipto 50000 ip from any to $clientip out");
        }

        /* add ipfw rules for layer 2 */
        if (!isset($config['captiveportal']['nomacfilter'])) {
            $l2ruleno = $ruleno + 10000;
            exec("/sbin/ipfw add $l2ruleno set 3 deny all from $clientip to any not MAC any $clientmac layer2 in");
            exec("/sbin/ipfw add $l2ruleno set 3 deny all from any to $clientip not MAC $clientmac any layer2 out");
        }

        if ($attributes['voucher']) {
            $attributes['session_timeout'] = $remaining_time;
        }

        /* encode password in Base64 just in case it contains commas */
        $bpassword = base64_encode($password);
        $cpdb[] = array(time(), $ruleno, $clientip, $clientmac, $username, $sessionid, $bpassword,
                $attributes['session_timeout'],
                $attributes['idle_timeout'],
                $attributes['session_terminate_time']);

        if (isset($config['captiveportal']['radacct_enable']) && isset($radiusservers[0])) {
            $acct_val = RADIUS_ACCOUNTING_START($ruleno,
                                                            $username,
                                                            $sessionid,
                                                            $radiusservers[0]['ipaddr'],
                                                            $radiusservers[0]['acctport'],
                                                            $radiusservers[0]['key'],
                                                            $clientip,
                                                            $clientmac);
            if ($acct_val == 1) 
                captiveportal_logportalauth($username,$clientmac,$clientip,$type,"RADIUS ACCOUNTING FAILED");
        }


    }

    /* rewrite information to database */
    captiveportal_write_db($cpdb);

    /* redirect user to desired destination */
    if ($url_redirection)
        $my_redirurl = $url_redirection;
    else if ($config['captiveportal']['redirurl'])
        $my_redirurl = $config['captiveportal']['redirurl'];
    else
        $my_redirurl = $redirurl;

    /* limit cookie validity */
    if(isset($config['captiveportal']['timeout']))
        $expiration = time() + 60*$config['captiveportal']['timeout'];
        else
        $expiration = 0;
    setcookie('sessionid', $sessionid, $expiration);
    if(isset($config['captiveportal']['logoutwin_enable'])) {
				$ourhostname = $config['interfaces'][$config['captiveportal']['interface']]['ipaddr'] . ":8000";
    		$oururl = "http://{$ourhostname}/";
        echo <<<EOD
<HTML>
<HEAD><TITLE>Redirecting...</TITLE></HEAD>
<BODY>
<SPAN STYLE="font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif; font-size: 11px;">
<B>Redirecting to <A HREF="{$my_redirurl}">{$my_redirurl}</A>...</B>
</SPAN>
<SCRIPT LANGUAGE="JavaScript">
<!--
LogoutWin = window.open('{$oururl}', 'Logout', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=480,height=400');
document.location.href="{$my_redirurl}";
-->
</SCRIPT>
</BODY>
</HTML>
EOD;
    } else {
        header("Location: " . $my_redirurl); 
    }

    captiveportal_unlock();
    return $sessionid;
}



/* remove a single client by session ID
   by Dinesh Nair
 */
function disconnect_client($sessionid, $logoutReason = "LOGOUT", $term_cause = 1) {

    global $g, $config;

    captiveportal_lock();
    /* read database */
    $cpdb = captiveportal_read_db();

    $radiusservers = captiveportal_get_radius_servers();

    /* find entry */
    for ($i = 0; $i < count($cpdb); $i++) {
        if ($cpdb[$i][5] == $sessionid) {
            captiveportal_disconnect($cpdb[$i],$radiusservers, $term_cause);
            captiveportal_logportalauth($cpdb[$i][4],$cpdb[$i][3],$cpdb[$i][2],$logoutReason);
            unset($cpdb[$i]);
            break;
        }
    }

    /* write database */
    captiveportal_write_db($cpdb);

    captiveportal_unlock();
}


?>
