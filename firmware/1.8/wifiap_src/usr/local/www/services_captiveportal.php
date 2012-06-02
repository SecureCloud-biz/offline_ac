#!/usr/local/bin/php
<?php 
/*
	$Id: services_captiveportal.php 379 2010-04-26 21:48:03Z mkasper $
	part of wifiap (http://wifiap.cn)
	
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

$pgtitle = array("高级服务", "web认证");
require("guiconfig.inc");

if (!is_array($config['captiveportal'])) {
	$config['captiveportal'] = array();
	$config['captiveportal']['page'] = array();
	$config['captiveportal']['timeout'] = 60;
}

if ($_GET['act'] == "viewhtml") {
	echo base64_decode($config['captiveportal']['page']['htmltext']);
	exit;
} else if ($_GET['act'] == "viewerrhtml") {
	echo base64_decode($config['captiveportal']['page']['errtext']);
	exit;
} else if ($_GET['act'] == "viewstatushtml") {
	echo base64_decode($config['captiveportal']['page']['statustext']);
	exit;
} else if ($_GET['act'] == "viewlogouthtml") {
	echo base64_decode($config['captiveportal']['page']['logouttext']);
	exit;
}

$pconfig['cinterface'] = $config['captiveportal']['interface'];
$pconfig['maxproc'] = $config['captiveportal']['maxproc'];
$pconfig['maxprocperip'] = $config['captiveportal']['maxprocperip'];
$pconfig['timeout'] = $config['captiveportal']['timeout'];
$pconfig['idletimeout'] = $config['captiveportal']['idletimeout'];
$pconfig['enable'] = isset($config['captiveportal']['enable']);
$pconfig['auth_method'] = $config['captiveportal']['auth_method'];
$pconfig['radacct_enable'] = isset($config['captiveportal']['radacct_enable']);
$pconfig['radmac_enable'] = isset($config['captiveportal']['radmac_enable']);
$pconfig['radmac_secret'] = $config['captiveportal']['radmac_secret'];
$pconfig['reauthenticate'] = isset($config['captiveportal']['reauthenticate']);
$pconfig['reauthenticateacct'] = $config['captiveportal']['reauthenticateacct'];
$pconfig['httpslogin_enable'] = isset($config['captiveportal']['httpslogin']);
$pconfig['httpsname'] = $config['captiveportal']['httpsname'];
$pconfig['cert'] = base64_decode($config['captiveportal']['certificate']);
$pconfig['key'] = base64_decode($config['captiveportal']['private-key']);
$pconfig['logoutwin_enable'] = isset($config['captiveportal']['logoutwin_enable']);
$pconfig['peruserbw'] = isset($config['captiveportal']['peruserbw']);
$pconfig['bwdefaultdn'] = $config['captiveportal']['bwdefaultdn'];
$pconfig['bwdefaultup'] = $config['captiveportal']['bwdefaultup'];
$pconfig['nomacfilter'] = isset($config['captiveportal']['nomacfilter']);
$pconfig['noconcurrentlogins'] = isset($config['captiveportal']['noconcurrentlogins']);
$pconfig['redirurl'] = $config['captiveportal']['redirurl'];
$pconfig['radiusip'] = $config['captiveportal']['radiusip'];
$pconfig['radiusip2'] = $config['captiveportal']['radiusip2'];
$pconfig['radiusport'] = $config['captiveportal']['radiusport'];
$pconfig['radiusport2'] = $config['captiveportal']['radiusport2'];
$pconfig['radiusacctport'] = $config['captiveportal']['radiusacctport'];
$pconfig['radiuskey'] = $config['captiveportal']['radiuskey'];
$pconfig['radiuskey2'] = $config['captiveportal']['radiuskey2'];
$pconfig['radiusvendor'] = $config['captiveportal']['radiusvendor'];
$pconfig['radiussession_timeout'] = isset($config['captiveportal']['radiussession_timeout']);
$pconfig['radmac_format'] = $config['captiveportal']['radmac_format'];
$pconfig['user_reg'] = $config['captiveportal']['user_reg'];
$pconfig['user_reg_exp'] = $config['captiveportal']['user_reg_exp'];

$pconfig['auth_sms_chnl'] = $config['captiveportal']['auth_sms_chnl'];
$pconfig['auth_sms_text'] = $config['captiveportal']['auth_sms_text'];
$pconfig['auth_sms_ok_flag'] = $config['captiveportal']['auth_sms_ok_flag'];
$pconfig['auth_sms_max_count'] = $config['captiveportal']['auth_sms_max_count'];
$pconfig['auth_sms_intvl'] = $config['captiveportal']['auth_sms_intvl'];
$pconfig['auth_sms_utt'] = $config['captiveportal']['auth_sms_utt'];

$pconfig['auth_api_url'] = $config['captiveportal']['auth_api_url'];
$pconfig['auth_api_key'] = $config['captiveportal']['auth_api_key'];
$pconfig['auth_api_success_flag'] = $config['captiveportal']['auth_api_success_flag'];


if ($_POST) {
	$portlist = get_interface_list(true, false);
	
	$macmatch = array();
	$macmatch[1]=false;
	$macmatch[2]=false;
	foreach ($portlist as $portname => $portinfo) {
		if($portinfo['mac']==$g['mac1']){
			$macmatch[1]=true;
		}elseif($portinfo['mac']==$g['mac2']){
			$macmatch[2]=true;
		}
	}
	if(1==0 && ($macmatch[1]==false || $macmatch[2]=false)){
		$savemsg = get_std_save_message(10);
	}else{
	
		unset($input_errors);
		$pconfig = $_POST;
	
		/* input validation */
		if ($_POST['enable']) {
			$reqdfields = explode(" ", "cinterface");
			$reqdfieldsn = explode(",", "Interface");
			
			do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
			
			/* make sure no interfaces are bridged */
			for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
				$coptif = &$config['interfaces']['opt' . $i];
				if (isset($coptif['enable']) && $coptif['bridge']) {
					$input_errors[] = "在有一个或多个网络接口处于桥接状态时不能使用WEB认证功能。";
					break;
				}
			}
			
			if ($_POST['httpslogin_enable']) {
			 	if (!$_POST['cert'] || !$_POST['key']) {
					$input_errors[] = "通过HTTPS登录时须提供证书和密码。";
				} else {
					if (!strstr($_POST['cert'], "BEGIN CERTIFICATE") || !strstr($_POST['cert'], "END CERTIFICATE"))
						$input_errors[] = "您所给的证书无效。";
					if (!strstr($_POST['key'], "BEGIN RSA PRIVATE KEY") || !strstr($_POST['key'], "END RSA PRIVATE KEY"))
						$input_errors[] = "您所给的密码不正确。";
				}
				
				if (!$_POST['httpsname'] || !is_domain($_POST['httpsname'])) {
					$input_errors[] = "通过HTTPS登录须给出HTTPS服务器的名字。";
				}
			}
		}
		
		if ($_POST['timeout'] && (!is_numeric($_POST['timeout']) || ($_POST['timeout'] < 1))) {
			$input_errors[] = "超时时长至少为1分钟。";
		}
		if ($_POST['idletimeout'] && (!is_numeric($_POST['idletimeout']) || ($_POST['idletimeout'] < 1))) {
			$input_errors[] = "空闲超时时长至少为1分钟。";
		}
		if ($_POST['peruserbw'] && (!isset($config['shaper']['enable']))) {
			$input_errors[] = "请先启动流量管理功能。";
		}
		if ($_POST['bwdefaultdn'] && (!is_numeric($_POST['bwdefaultdn']) || ($_POST['bwdefaultdn'] < 16))) {
			$input_errors[] = "每用户带宽下载速度必须大于 16。";
		}
		if ($_POST['bwdefaultup'] && (!is_numeric($_POST['bwdefaultup']) || ($_POST['bwdefaultup'] < 16))) {
			$input_errors[] = "每用户带宽上传速度必须大于 16。";
		}
		if (($_POST['radiusip'] && !is_ipaddr($_POST['radiusip']))) {
			$input_errors[] = "须输入一个合法的IP地址。 [".$_POST['radiusip']."]";
		}
		if (($_POST['radiusip2'] && !is_ipaddr($_POST['radiusip2']))) {
			$input_errors[] = "须输入一个合法的IP地址。 [".$_POST['radiusip2']."]";
		}
		if (($_POST['radiusport'] && !is_port($_POST['radiusport']))) {
			$input_errors[] = "须输入一个合法的端口号。 [".$_POST['radiusport']."]";
		}
		if (($_POST['radiusport2'] && !is_port($_POST['radiusport2']))) {
			$input_errors[] = "须输入一个合法的端口号。 [".$_POST['radiusport2']."]";
		}
		if (($_POST['radiusacctport'] && !is_port($_POST['radiusacctport']))) {
			$input_errors[] = "须输入一个合法的端口号。 [".$_POST['radiusacctport']."]";
		}
		if ($_POST['maxproc'] && (!is_numeric($_POST['maxproc']) || ($_POST['maxproc'] < 4) || ($_POST['maxproc'] > 100))) {
			$input_errors[] = "加在一起的最大并发连接数须介于 4 - 100 之间。";
		}
		$mymaxproc = $_POST['maxproc'] ? $_POST['maxproc'] : 16;
		if ($_POST['maxprocperip'] && (!is_numeric($_POST['maxprocperip']) || ($_POST['maxprocperip'] > $mymaxproc))) {
			$input_errors[] = "来自单个用户IP的最大并发连接数不得大于总连接数。";
		}
		
		if($_POST['user_reg']=='open' && strlen($_POST['user_reg_exp'])>0 && strtotime($_POST['user_reg_exp']) <= 0){
				$input_errors[] = "用户注册参数.无效的日期格式：正确格式为 MM/DD/YYYY或者YYYY-MM-DD";
		}
		
		if($_POST['auth_method']=='sms' && !is_numeric($_POST['auth_sms_max_count'])){
			$input_errors[] = "短信验证码.一天最多获取次数输入不正确";
		}
		if($_POST['auth_method']=='sms' && !is_numeric($_POST['auth_sms_intvl'])){
			$input_errors[] = "短信验证码.发送间隔输入不正确";
		}
	
		if (!$input_errors) {
			$config['captiveportal']['interface'] = $_POST['cinterface'];
			$config['captiveportal']['maxproc'] = $_POST['maxproc'];
			$config['captiveportal']['maxprocperip'] = $_POST['maxprocperip'] ? $_POST['maxprocperip'] : false;
			$config['captiveportal']['timeout'] = $_POST['timeout'];
			$config['captiveportal']['idletimeout'] = $_POST['idletimeout'];
			$config['captiveportal']['enable'] = $_POST['enable'] ? true : false;
			$config['captiveportal']['auth_method'] = $_POST['auth_method'];
			$config['captiveportal']['radacct_enable'] = $_POST['radacct_enable'] ? true : false;
			$config['captiveportal']['reauthenticate'] = $_POST['reauthenticate'] ? true : false;
			$config['captiveportal']['radmac_enable'] = $_POST['radmac_enable'] ? true : false;
			$config['captiveportal']['radmac_secret'] = $_POST['radmac_secret'] ? $_POST['radmac_secret'] : false;
			$config['captiveportal']['reauthenticateacct'] = $_POST['reauthenticateacct'];
			$config['captiveportal']['httpslogin'] = $_POST['httpslogin_enable'] ? true : false;
			$config['captiveportal']['httpsname'] = $_POST['httpsname'];
			$config['captiveportal']['certificate'] = base64_encode($_POST['cert']);
			$config['captiveportal']['private-key'] = base64_encode($_POST['key']);
			$config['captiveportal']['logoutwin_enable'] = $_POST['logoutwin_enable'] ? true : false;
			$config['captiveportal']['peruserbw'] = $_POST['peruserbw'] ? true : false;
			$config['captiveportal']['bwdefaultdn'] = $_POST['bwdefaultdn'];
			$config['captiveportal']['bwdefaultup'] = $_POST['bwdefaultup'];
			$config['captiveportal']['nomacfilter'] = $_POST['nomacfilter'] ? true : false;
			$config['captiveportal']['noconcurrentlogins'] = $_POST['noconcurrentlogins'] ? true : false;
			$config['captiveportal']['redirurl'] = $_POST['redirurl'];
			$config['captiveportal']['radiusip'] = $_POST['radiusip'];
			$config['captiveportal']['radiusip2'] = $_POST['radiusip2'];
			$config['captiveportal']['radiusport'] = $_POST['radiusport'];
			$config['captiveportal']['radiusport2'] = $_POST['radiusport2'];
			$config['captiveportal']['radiusacctport'] = $_POST['radiusacctport'];
			$config['captiveportal']['radiuskey'] = $_POST['radiuskey'];
			$config['captiveportal']['radiuskey2'] = $_POST['radiuskey2'];
			$config['captiveportal']['radiusvendor'] = $_POST['radiusvendor'] ? $_POST['radiusvendor'] : false;
			$config['captiveportal']['radiussession_timeout'] = $_POST['radiussession_timeout'] ? true : false;
	        $config['captiveportal']['radmac_format'] = $_POST['radmac_format'] ? $_POST['radmac_format'] : false;
	    $config['captiveportal']['user_reg'] = $_POST['user_reg'];
	    $config['captiveportal']['user_reg_exp'] = $_POST['user_reg_exp'];
	    /* sms auth paramater */
	    $config['captiveportal']['auth_sms_chnl'] = $_POST['auth_sms_chnl'];
	    $config['captiveportal']['auth_sms_text'] = $_POST['auth_sms_text'];
	    $config['captiveportal']['auth_sms_ok_flag'] = $_POST['auth_sms_ok_flag'];
	    $config['captiveportal']['auth_sms_max_count'] = $_POST['auth_sms_max_count'];
	    $config['captiveportal']['auth_sms_intvl'] = $_POST['auth_sms_intvl'];
	    $config['captiveportal']['auth_sms_utt'] = $_POST['auth_sms_utt'];
	    /* api auth paramater */
	    $config['captiveportal']['auth_api_url'] = $_POST['auth_api_url'];
	    $config['captiveportal']['auth_api_key'] = $_POST['auth_api_key'];
	    $config['captiveportal']['auth_api_success_flag'] = $_POST['auth_api_success_flag'];
	    
			
			/* file upload? */
			if (is_uploaded_file($_FILES['htmlfile']['tmp_name']))
				$config['captiveportal']['page']['htmltext'] = base64_encode(file_get_contents($_FILES['htmlfile']['tmp_name']));
			if (is_uploaded_file($_FILES['errfile']['tmp_name']))
				$config['captiveportal']['page']['errtext'] = base64_encode(file_get_contents($_FILES['errfile']['tmp_name']));
			if (is_uploaded_file($_FILES['statusfile']['tmp_name']))
				$config['captiveportal']['page']['statustext'] = base64_encode(file_get_contents($_FILES['statusfile']['tmp_name']));
			if (is_uploaded_file($_FILES['logoutfile']['tmp_name']))
				$config['captiveportal']['page']['logouttext'] = base64_encode(file_get_contents($_FILES['logoutfile']['tmp_name']));
				
			write_config();
			
			$retval = 0;
			if (!file_exists($d_sysrebootreqd_path)) {
				config_lock();
				$retval = captiveportal_configure();
				echo 'retval:'.$retval;
				config_unlock();
			}
			$savemsg = get_std_save_message($retval);
		}
	}
}
$smspara='none';
$apipara='none';
if($pconfig['auth_method']=='sms'){
	$smspara='';
}
if($pconfig['auth_method']=='api'){
	$apipara='';
}
?>
<?php include("fbegin.inc"); ?>
<script type="text/javascript">
<!--
function enable_change(enable_change) {
	var endis, radius_endis;
	endis = !(document.iform.enable.checked || enable_change);
	//radius_endis = !((!endis && document.iform.auth_method[4].checked) || enable_change);
	
	document.iform.cinterface.disabled = endis;
	document.iform.maxproc.disabled = endis;
	document.iform.maxprocperip.disabled = endis;
	document.iform.idletimeout.disabled = endis;
	document.iform.timeout.disabled = endis;
	document.iform.redirurl.disabled = endis;
	//document.iform.radiusip.disabled = radius_endis;
	//document.iform.radiusip2.disabled = radius_endis;
	//document.iform.radiusport.disabled = radius_endis;
	//document.iform.radiusport2.disabled = radius_endis;
	//document.iform.radiuskey.disabled = radius_endis;
	//document.iform.radiuskey2.disabled = radius_endis;
	//document.iform.radacct_enable.disabled = radius_endis;
	//document.iform.reauthenticate.disabled = radius_endis;
	document.iform.auth_method[0].disabled = endis;
	document.iform.auth_method[1].disabled = endis;
	document.iform.auth_method[2].disabled = endis;
	document.iform.auth_method[3].disabled = endis;
	document.iform.peruserbw.disabled = endis;
	document.iform.bwdefaultdn.disabled = endis;
	document.iform.bwdefaultup.disabled = endis;
	//document.iform.radmac_enable.disabled = radius_endis;
	//document.iform.radmac_format.disabled = radius_endis;
	document.iform.httpslogin_enable.disabled = endis;
	document.iform.httpsname.disabled = endis;
	document.iform.cert.disabled = endis;
	document.iform.key.disabled = endis;
	document.iform.logoutwin_enable.disabled = endis;
	document.iform.nomacfilter.disabled = endis;
	document.iform.noconcurrentlogins.disabled = endis;
	//document.iform.radiusvendor.disabled = radius_endis;
	//document.iform.radiussession_timeout.disabled = radius_endis;
	document.iform.htmlfile.disabled = endis;
	document.iform.errfile.disabled = endis;
	document.iform.statusfile.disabled = endis;
	document.iform.logoutfile.disabled = endis;
	
	//document.iform.radiusacctport.disabled = (radius_endis || !document.iform.radacct_enable.checked) && !enable_change;
	
	//document.iform.radmac_secret.disabled = (radius_endis || !document.iform.radmac_enable.checked) && !enable_change;
	
	//var reauthenticate_dis = (radius_endis || !document.iform.reauthenticate.checked) && !enable_change;
	//document.iform.reauthenticateacct[0].disabled = reauthenticate_dis;
	//document.iform.reauthenticateacct[1].disabled = reauthenticate_dis;
	//document.iform.reauthenticateacct[2].disabled = reauthenticate_dis;
	
	var user_reg_endis = !((!endis && document.iform.user_reg[1].checked) || enable_change);	
	document.iform.user_reg_exp.disabled = user_reg_endis;
	
	sms_endis = !((!endis && document.iform.auth_method[2].checked) || enable_change);
	api_endis = !((!endis && document.iform.auth_method[3].checked) || enable_change);
	if(sms_endis){
		document.getElementById('smspara1').style.display="none";
		document.getElementById('smspara2').style.display="none";
	}else{
		document.getElementById('smspara1').style.display="";
		document.getElementById('smspara2').style.display="";
	}
	if(api_endis){
		document.getElementById('apipara1').style.display="none";
		document.getElementById('apipara2').style.display="none";
	}else{
		document.getElementById('apipara1').style.display="";
		document.getElementById('apipara2').style.display="";
	}
}
//-->
</script>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<form action="services_captiveportal.php" method="post" enctype="multipart/form-data" name="iform" id="iform">
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php 
   	$tabs = array('WEB认证' => 'services_captiveportal.php',
           		  'MAC白名单' => 'services_captiveportal_mac.php',
           		  'IP白名单' => 'services_captiveportal_ip.php',
           		  '用户管理' => 'services_captiveportal_users.php',
           		  '文件和页面管理' => 'services_captiveportal_filemanager.php');
	dynamic_tab_menu($tabs);
?> 
  </ul>
  </td></tr>
  <tr>
  <td class="tabcont">
  <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
	<tr> 
	  <td width="22%" valign="top" class="vtable">&nbsp;</td>
	  <td width="78%" class="vtable">
		<input name="enable" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?> onClick="enable_change(false)">
		<strong>开启WEB认证</strong></td>
	</tr>
	<tr> 
	  <td width="22%" valign="top" class="vncellreq">接口</td>
	  <td width="78%" class="vtable">
		<select name="cinterface" class="formfld" id="cinterface">
		  <?php $interfaces = array('lan' => 'LAN');
		  for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
			if (isset($config['interfaces']['opt' . $i]['enable']))
				$interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
		  }
		  foreach ($interfaces as $iface => $ifacename): ?>
		  <option value="<?=$iface;?>" <?php if ($iface == $pconfig['cinterface']) echo "selected"; ?>> 
		  <?=htmlspecialchars($ifacename);?>
		  </option>
		  <?php endforeach; ?>
		</select> <br>
		<span class="vexpl">选择需要开启WEB认证的网络接口。</span></td>
	</tr>
	<tr>
	  <td valign="top" class="vncell">最大并发连接</td>
	  <td class="vtable">
		<table cellpadding="0" cellspacing="0" summary="max-conc-connection widget">
                 <tr>
           <td><input name="maxprocperip" type="text" class="formfld" id="maxprocperip" size="5" value="<?=htmlspecialchars($pconfig['maxprocperip']);?>"> 每IP地址 (0 = no limit)</td>
                 </tr>
                 <tr>
           <td><input name="maxproc" type="text" class="formfld" id="maxproc" size="5" value="<?=htmlspecialchars($pconfig['maxproc']);?>"> 总共</td>
                 </tr>
               </table>
本设置用来限制连接到HTTP(S)服务器的并发连接数。 它并不是指有多少用户可以登录上网，而是指有多少用户可以同时打开WEB认证页面进行验证。
默认值为每IP可开4个连接，总共连接数为16个。</td>
	</tr>
	<tr>
	  <td valign="top" class="vncell">空闲超时断开</td>
	  <td class="vtable">
		<input name="idletimeout" type="text" class="formfld" id="idletimeout" size="6" value="<?=htmlspecialchars($pconfig['idletimeout']);?>">
分钟<br>
当空闲超过所设的时长后，该用户的连接就会被断开。当然，他也可以马上再联接上。此处若不填，则没有此超时断开操作。</td>
	</tr>
	<tr> 
	  <td width="22%" valign="top" class="vncell">超时硬性断开</td>
	  <td width="78%" class="vtable"> 
		<input name="timeout" type="text" class="formfld" id="timeout" size="6" value="<?=htmlspecialchars($pconfig['timeout']);?>"> 
		分钟<br>
	  不管用户有没有操作，在超过所设时长后，他都被硬性断开。当然他也可以马上再联接上。此处若不填，则没有此超时断开操作。（除非已设置了空闲超时断开，建议设置超时硬性断开）</td>
	</tr>
	<tr> 
	  <td width="22%" valign="top" class="vncell">登录管理窗口</td>
	  <td width="78%" class="vtable"> 
		<input name="logoutwin_enable" type="checkbox" class="formfld" id="logoutwin_enable" value="yes" <?php if($pconfig['logoutwin_enable']) echo "checked"; ?>>
		<strong>启用登录管理窗口</strong><br>
	  此功能若打开，在用户登录后出现一个管理窗口。通过它，用户可以在空闲超时或硬超时满足之前主动断开连接。</td>
	</tr>
	<tr>
	  <td valign="top" class="vncell">重定向链接</td>
	  <td class="vtable">
		<input name="redirurl" type="text" class="formfld" id="redirurl" size="60" value="<?=htmlspecialchars($pconfig['redirurl']);?>">
		<br>
若您在这里提供了一个重定向链接，用户在入网门户验证成功进入时会被定向到该链接，而不是他一开始想访问的链接。</td>
	</tr>
	<tr>
      <td valign="top" class="vncell">重登录</td>
      <td class="vtable">
	<input name="noconcurrentlogins" type="checkbox" class="formfld" id="noconcurrentlogins" value="yes" <?php if ($pconfig['noconcurrentlogins']) echo "checked"; ?>>
	<strong>禁止同一账号多终端登录</strong><br>
	若设此项，只有最新的登录才有效。在以同一账号/用户名登录后，先前所有以此用户名登录的其它连接会被断开。</td>
	</tr>
	<tr>
      <td valign="top" class="vncell">核验MAC地址</td>
      <td class="vtable">
        <input name="nomacfilter" type="checkbox" class="formfld" id="nomacfilter" value="yes" <?php if ($pconfig['nomacfilter']) echo "checked"; ?>>
        <strong>关闭MAC地址核验</strong><br>
    若设此项，wifiAP在用户登录后不再尝试核验其MAC地址是否保持不变。
	这在有些情况下有用，比如用户的MAC地址不能确认时（一般是在wifiAP和用户之间还有路由器的情况）。</td>
	</tr>
    <tr>
      <td valign="top" class="vncell">每用户带宽限制</td>
      <td class="vtable">
        <input name="peruserbw" type="checkbox" class="formfld" id="peruserbw" value="yes" <?php if ($pconfig['peruserbw']) echo "checked"; ?>>
        <strong>开启每用户带宽限制</strong><br><br>
        <table cellpadding="0" cellspacing="0" summary="bandwidth-restriction widget">
        <tr>
        <td>默认下载&nbsp;&nbsp;</td>
        <td><input type="text" class="formfld" name="bwdefaultdn" id="bwdefaultdn" size="10" value="<?=htmlspecialchars($pconfig['bwdefaultdn']);?>"> Kbit/s</td>
        </tr>
        <tr>
        <td>默认上传</td>
        <td><input type="text" class="formfld" name="bwdefaultup" id="bwdefaultup" size="10" value="<?=htmlspecialchars($pconfig['bwdefaultup']);?>"> Kbit/s</td>
        </tr></table>
        <br>
        如果该选项被设置，将限制入网门户使用登入者的每用户为默认带宽。留空或者设置 0 则不限制。<strong>必须</strong>开启流量管理使该设置有效。</td>
        </tr>
	<tr> 
	  <td width="22%" valign="top" class="vncell">验证方式</td>
	  <td width="78%" class="vtable"> 
		<table cellpadding="0" cellspacing="0" summary="authentication widget">
		<tr>
		  <td colspan="2"><input name="auth_method" type="radio" id="auth_method_none" value="none" onClick="enable_change(false)" <?php if($pconfig['auth_method']!="local" && $pconfig['auth_method']!="radius") echo "checked"; ?>>
  不验证（但仍显示登录页面/展示页面）</td>  
		  </tr>
		<tr>
		  <td colspan="2"><input name="auth_method" type="radio" id="auth_method_local" value="local" onClick="enable_change(false)" <?php if($pconfig['auth_method']=="local") echo "checked"; ?>>
  wifiAP<a href="services_wifiap.php">本地认证</a></td>  
		  </tr>
		  <tr>
		  <td colspan="2"><input name="auth_method" type="radio" id="auth_method_sms" value="sms" onClick="enable_change(false)" <?php if($pconfig['auth_method']=="sms") echo "checked"; ?>>
  短信验证码认证</td>
		  </tr>
		<tr id="smspara1" <?php if($smspara=='none'){ ?> style="display:none;" <?php }?>>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		</tr>
		<tr id="smspara2" <?php if($smspara=='none'){ ?> style="display:none;" <?php }?>>
			<td colspan="2">
				<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="radius-server widget">
        	<tr> 
            	<td colspan="2" valign="top" class="optsect_t2">短信验证码参数</td>
					</tr>
					<tr>
						<td class="vncell" valign="top">通道API</td>
						<td class="vtable"><input name="auth_sms_chnl" type="text" class="formfld" id="auth_sms_chnl" size="20" value="<?php echo $pconfig['auth_sms_chnl']; ?>">
						手机号用{mobile}, 短信内容用{msg}.</td>
					</tr>
					<tr>
						<td class="vncell" valign="top">短信内容</td>
						<td class="vtable"><input name="auth_sms_text" type="text" class="formfld" id="auth_sms_text" size="20" value="<?php echo $pconfig['auth_sms_text']; ?>">
						生成的验证码用{verifycode}.</td>
					</tr>
					<tr>
						<td class="vncell" valign="top">短信发送成功标志</td>
						<td class="vtable"><input name="auth_sms_ok_flag" type="text" class="formfld" id="auth_sms_ok_flag" size="20" value="<?php echo $pconfig['auth_sms_ok_flag']; ?>"></td>
					</tr>
					<tr>
						<td class="vncell" valign="top">一天最多获取次数</td>
						<td class="vtable"><input name="auth_sms_max_count" type="text" class="formfld" id="auth_sms_max_count" size="20" value="<?php echo $pconfig['auth_sms_max_count']; ?>"></td>
					</tr>
					<tr>
						<td class="vncell" valign="top">获取间隔</td>
						<td class="vtable"><input name="auth_sms_intvl" type="text" class="formfld" id="auth_sms_intvl" size="20" value="<?php echo $pconfig['auth_sms_intvl']; ?>">以分钟为单位</td>
					</tr>
					<tr>
						<td class="vncell" valign="top">用户类型</td>
						<td class="vtable"><input name="auth_sms_utt" type="radio" id="auth_sms_utt_0" size="20" value="0" <?php if($pconfig['auth_sms_utt']=='0'){echo 'checked'; }?>>一次性用户 
							<input name="auth_sms_utt" type="radio" id="auth_sms_utt_1" size="20" value="1" <?php if($pconfig['auth_sms_utt']=='1'){echo 'checked'; }?>>普通用户
							</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
		  <td colspan="2"><input name="auth_method" type="radio" id="auth_method_api" value="api" onClick="enable_change(false)" <?php if($pconfig['auth_method']=="api") echo "checked"; ?>>
  外部API认证</td>
		  </tr>
		<tr id="apipara1" <?php if($apipara=='none'){ ?> style="display:none;" <?php }?>>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		</tr>
		<tr id="apipara2" <?php if($apipara=='none'){ ?> style="display:none;" <?php }?>>
			<td colspan="2">
				<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="radius-server widget">
        	<tr> 
            	<td colspan="2" valign="top" class="optsect_t2">API验证参数</td>
					</tr>
					<tr>
						<td class="vncell" valign="top" width="50">API</td>
						<td class="vtable"><input name="auth_api_url" type="text" class="formfld" id="auth_api_url" size="20" value="<?php echo $pconfig['auth_api_url']; ?>"><br>
						例如：http://www.mydomain.cn/api.php. 这时调用的形式为http://www.mydomain.cn/api.php?username=用户名&password=加密的密码&rnd=8位随机码<br>
						加密的密码=md5(Key&密码&随机码)</td>
					</tr>
					<tr>
						<td class="vncell" valign="top" width="50">Key</td>
						<td class="vtable"><input name="auth_api_key" type="text" class="formfld" id="auth_api_key" size="20" value="<?php echo $pconfig['auth_api_key']; ?>"><br>
						这里设置的key必须和API设定的一样</td>
					</tr>
					<tr>
						<td class="vncell" valign="top">验证通过返回</td>
						<td class="vtable"><input name="auth_api_success_flag" type="text" class="formfld" id="auth_api_success_flag" size="20" value="<?php echo $pconfig['auth_api_success_flag']; ?>">
						不通过的返回信息将作为错误信息显示.</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr style="display:none;">
		  <td colspan="2"><input name="auth_method" type="radio" id="auth_method_radius" value="radius" onClick="enable_change(false)" <?php if($pconfig['auth_method']=="radius") echo "checked"; ?>>
  RADIUS authentication</td>  
		</tr>
		</table>
		
		<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="radius-server widget" style="display:none;">
        	<tr> 
            	<td colspan="2" valign="top" class="optsect_t2">Primary RADIUS server</td>
			</tr>
			<tr>
				<td class="vncell" valign="top">IP address</td>
				<td class="vtable"><input name="radiusip" type="text" class="formfld" id="radiusip" size="20" value="<?=htmlspecialchars($pconfig['radiusip']);?>"><br>
				Enter the IP address of the RADIUS server which users of the captive portal have to authenticate against.</td>
			</tr>
			<tr>
				<td class="vncell" valign="top">Port</td>
				<td class="vtable"><input name="radiusport" type="text" class="formfld" id="radiusport" size="5" value="<?=htmlspecialchars($pconfig['radiusport']);?>"><br>
				 Leave this field blank to use the default port (1812).</td>
			</tr>
			<tr>
				<td class="vncell" valign="top">Shared secret&nbsp;&nbsp;</td>
				<td class="vtable"><input name="radiuskey" type="text" class="formfld" id="radiuskey" size="16" value="<?=htmlspecialchars($pconfig['radiuskey']);?>"><br>
				Leave this field blank to not use a RADIUS shared secret (not recommended).</td>
			</tr>
			<tr> 
			  <td colspan="2" class="list" height="12"></td>
			</tr>
			<tr>
				<td colspan="2" valign="top" class="optsect_t2">Secondary RADIUS server</td>
			</tr>
			<tr>
				<td class="vncell" valign="top">IP address</td>
				<td class="vtable"><input name="radiusip2" type="text" class="formfld" id="radiusip2" size="20" value="<?=htmlspecialchars($pconfig['radiusip2']);?>"><br>
				If you have a second RADIUS server, you can activate it by entering its IP address here.</td>
			</tr>
			<tr>
				<td class="vncell" valign="top">Port</td>
				<td class="vtable"><input name="radiusport2" type="text" class="formfld" id="radiusport2" size="5" value="<?=htmlspecialchars($pconfig['radiusport2']);?>"></td>
			</tr>
			<tr>
				<td class="vncell" valign="top">Shared secret&nbsp;&nbsp;</td>
				<td class="vtable"><input name="radiuskey2" type="text" class="formfld" id="radiuskey2" size="16" value="<?=htmlspecialchars($pconfig['radiuskey2']);?>"></td>
			</tr>
			<tr> 
			  <td colspan="2" class="list" height="12"></td>
			</tr>
			<tr>
				<td colspan="2" valign="top" class="optsect_t2">Accounting</td>
			</tr>
			<tr>
				<td class="vncell">&nbsp;</td>
				<td class="vtable"><input name="radacct_enable" type="checkbox" id="radacct_enable" value="yes" onClick="enable_change(false)" <?php if($pconfig['radacct_enable']) echo "checked"; ?>>
				<strong>send RADIUS accounting packets</strong><br>
				If this is enabled, RADIUS accounting packets will be sent to the primary RADIUS server.</td>
			</tr>
			<tr>
			  <td class="vncell" valign="top">Accounting port</td>
			  <td class="vtable"><input name="radiusacctport" type="text" class="formfld" id="radiusacctport" size="5" value="<?=htmlspecialchars($pconfig['radiusacctport']);?>"><br>
			  Leave blank to use the default port (1813).</td>
			  </tr>
			<tr>
			  <td colspan="2" class="list" height="12"></td>
			</tr>
			<tr>
				<td colspan="2" valign="top" class="optsect_t2">Reauthentication</td>
			</tr>
			<tr>
				<td class="vncell">&nbsp;</td>
				<td class="vtable"><input name="reauthenticate" type="checkbox" id="reauthenticate" value="yes" onClick="enable_change(false)" <?php if($pconfig['reauthenticate']) echo "checked"; ?>>
			  <strong>Reauthenticate connected users every minute</strong><br>
			  If reauthentication is enabled, Access-Requests will be sent to the RADIUS server for each user that is
			  logged in every minute. If an Access-Reject is received for a user, that user is disconnected from the captive portal immediately.</td>
			</tr>
			<tr>
			  <td class="vncell" valign="top">Accounting updates</td>
			  <td class="vtable">
			  <input name="reauthenticateacct" type="radio" value="" <?php if(!$pconfig['reauthenticateacct']) echo "checked"; ?>> no accounting updates<br>
			  <input name="reauthenticateacct" type="radio" value="stopstart" <?php if($pconfig['reauthenticateacct'] == "stopstart") echo "checked"; ?>> stop/start accounting<br>
			  <input name="reauthenticateacct" type="radio" value="interimupdate" <?php if($pconfig['reauthenticateacct'] == "interimupdate") echo "checked"; ?>> interim update
			  </td>
			</tr>
			<tr>
			  <td colspan="2" class="list" height="12"></td>
			</tr>
			<tr>
				<td colspan="2" valign="top" class="optsect_t2">RADIUS MAC authentication</td>
			</tr>
			<tr>
				<td class="vncell">&nbsp;</td>
				<td class="vtable">
				<input name="radmac_enable" type="checkbox" id="radmac_enable" value="yes" onClick="enable_change(false)" <?php if ($pconfig['radmac_enable']) echo "checked"; ?>><strong>Enable RADIUS MAC authentication</strong><br>
				If this option is enabled, the captive portal will try to authenticate users by sending their MAC address as the username and the password
				entered below to the RADIUS server.</td>
			</tr>
			<tr>
				<td class="vncell">Shared secret</td>
				<td class="vtable"><input name="radmac_secret" type="text" class="formfld" id="radmac_secret" size="16" value="<?=htmlspecialchars($pconfig['radmac_secret']);?>"></td>
			</tr>
			<tr>
			  <td colspan="2" class="list" height="12"></td>
			</tr>
			<tr>
				<td colspan="2" valign="top" class="optsect_t2">RADIUS options</td>
			</tr>
			<tr>
				<td class="vncell" valign="top">Session-Timeout</td>
				<td class="vtable"><input name="radiussession_timeout" type="checkbox" id="radiussession_timeout" value="yes" <?php if ($pconfig['radiussession_timeout']) echo "checked"; ?>><strong>Use RADIUS Session-Timeout attributes</strong><br>
				When this is enabled, clients will be disconnected after the amount of time retrieved from the RADIUS Session-Timeout attribute.</td>
			</tr>
			<tr>
				<td class="vncell" valign="top">Type</td>
				<td class="vtable"><select name="radiusvendor" id="radiusvendor">
				<option>default</option>
				<?php 
				$radiusvendors = array("cisco");
				foreach ($radiusvendors as $radiusvendor){
					if ($pconfig['radiusvendor'] == $radiusvendor)
						echo "<option selected value=\"$radiusvendor\">$radiusvendor</option>\n";
					else
						echo "<option value=\"$radiusvendor\">$radiusvendor</option>\n";
				}
				?></select><br>
				If RADIUS type is set to Cisco, in RADIUS requests (Authentication/Accounting) the value of Calling-Station-Id will be set to the client's IP address and
				the Called-Station-Id to the client's MAC address. Default behaviour is Calling-Station-Id = client's MAC address and Called-Station-Id = wifiap's WAN MAC address.</td>
			</tr>
            <tr>
                <td class="vncell" valign="top">MAC address format</td>
                <td class="vtable">
                <select name="radmac_format" id="radmac_format">
                <option>default</option>
                <?php
                $macformats = array("singledash","ietf","cisco","unformatted");
                foreach ($macformats as $macformat) {
                    if ($pconfig['radmac_format'] == $macformat)
                        echo "<option selected value=\"$macformat\">$macformat</option>\n";
                    else
                        echo "<option value=\"$macformat\">$macformat</option>\n";
                }
                ?>
                </select><br>
                This option changes the MAC address format used in the whole RADIUS system. Change this if you also
                need to change the username format for RADIUS MAC authentication.<br>
                default: 00:11:22:33:44:55<br>
                singledash: 001122-334455<br>
                ietf: 00-11-22-33-44-55<br>
                cisco: 0011.2233.4455<br>
                unformatted: 001122334455
            </tr>
		</table>
	</tr>
	<tr> 
	  <td width="22%" valign="top" class="vncell">用户注册</td>
	  <td width="78%" class="vtable"> 
		<table cellpadding="0" cellspacing="0" summary="authentication widget">
		<tr>
		  <td colspan="2"><input name="user_reg" type="radio" id="user_reg_close" value="close" onClick="enable_change(false)" <?php if($pconfig['user_reg']=="close") echo "checked"; ?>>
  关闭注册（用户不能注册）</td>  
		  </tr>
		<tr>
		  <td colspan="2"><input name="user_reg" type="radio" id="user_reg_open" value="open" onClick="enable_change(false)" <?php if($pconfig['user_reg']=="open") echo "checked"; ?>>
  开启注册（用户可通过页面进行注册）</td>  
		  </tr>
		</table>
		<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="radius-server widget">
        	<tr> 
            	<td colspan="2" valign="top" class="optsect_t2">用户注册参数</td>
			</tr>
			<tr>
				<td class="vncell" valign="top">有效期</td>
				<td class="vtable"><input name="user_reg_exp" type="text" class="formfld" id="user_reg_exp" size="20" value="<?php echo $pconfig['user_reg_exp']; ?>">(YYYY-MM-DD)<br>
				注册的用户的有效期.</td>
			</tr>
		</table>
	</td>
	</tr>
	<tr>
      <td valign="top" class="vncell">HTTPS 登录认证</td>
      <td class="vtable">
        <input name="httpslogin_enable" type="checkbox" class="formfld" id="httpslogin_enable" value="yes" <?php if($pconfig['httpslogin_enable']) echo "checked"; ?>>
        <strong>启用 HTTPS 登录认证</strong><br>
    若启用此项，用户名和密码就会按HTTPS连接以加密的方式传输，以防被窃取。服务器名，证书和匹配的私有密钥需要在下面输入。</td>
	  </tr>
	<tr>
      <td valign="top" class="vncell">HTTPS 服务器名称</td>
      <td class="vtable">
        <input name="httpsname" type="text" class="formfld" id="httpsname" size="30" value="<?=htmlspecialchars($pconfig['httpsname']);?>"><br>
    用户端会通过HTTPS POST操作以表单的形式提交给该服务器，证书中的“公有名字”（CN）与服务器名要一致（否则，用户的浏览器很可能会显示一个安全警告）。另外再确认DNS可以解析该服务器名。</td>
	  </tr>
	<tr>
      <td valign="top" class="vncell">HTTPS 证书</td>
      <td class="vtable">
        <textarea name="cert" cols="65" rows="7" id="cert" class="formpre"><?=htmlspecialchars($pconfig['cert']);?></textarea>
        <br>
    在此处粘贴 X.509 PEM 格式的已签证书。</td>
	  </tr>
	<tr>
      <td valign="top" class="vncell">HTTPS 私有密钥</td>
      <td class="vtable">
        <textarea name="key" cols="65" rows="7" id="key" class="formpre"><?=htmlspecialchars($pconfig['key']);?></textarea>
        <br>
    在此处粘贴 PEM 格式的 RSA 私有密钥。</td>
	  </tr>
	<tr style="display:none;"> 
	  <td width="22%" valign="top" class="vncellreq">认证页面内容</td>
	  <td width="78%" class="vtable">    
		<?=$mandfldhtml;?><input type="file" name="htmlfile" class="formfld" id="htmlfile"><br>
		<?php if ($config['captiveportal']['page']['htmltext']): ?>
		<a href="?act=viewhtml" target="_blank">显示当前页面</a>                      
		  <br>
		  <br>
		<?php endif; ?>
		  可通过这里上传一个HTML文件用作入网门户页面（留空则保持当前页面不变）。上传的页面须包含 (POST to &quot;$PORTAL_ACTION$&quot;) 表单，再加一个提交按钮 (name=&quot;accept&quot;) 另加一个name=&quot;redirurl&quot; and value=&quot;$PORTAL_REDIRURL$&quot;的隐藏域。
包括 &quot;auth_user&quot; and &quot;auth_pass&quot; 及输入框（需要验证的话），否则验证不会成功。
表单示例：<br>
		  <br>
		  <tt>&lt;form method=&quot;post&quot; action=&quot;$PORTAL_ACTION$&quot;&gt;<br>
		  &nbsp;&nbsp;&nbsp;&lt;input name=&quot;auth_user&quot; type=&quot;text&quot;&gt;<br>
		  &nbsp;&nbsp;&nbsp;&lt;input name=&quot;auth_pass&quot; type=&quot;password&quot;&gt;<br>
		  &nbsp;&nbsp;&nbsp;&lt;input name=&quot;auth_voucher&quot; type=&quot;text&quot;&gt;<br>
		  &nbsp;&nbsp;&nbsp;&lt;input name=&quot;redirurl&quot; type=&quot;hidden&quot; value=&quot;$PORTAL_REDIRURL$&quot;&gt;<br>
&nbsp;&nbsp;&nbsp;&lt;input name=&quot;accept&quot; type=&quot;submit&quot; value=&quot;Continue&quot;&gt;<br>
		  &lt;/form&gt;</tt></td>
	</tr>
	<tr style="display:none;">
	  <td width="22%" valign="top" class="vncell">验证失败页<br>
		内容</td>
	  <td class="vtable">
		<input name="errfile" type="file" class="formfld" id="errfile"><br>
		<?php if ($config['captiveportal']['page']['errtext']): ?>
		<a href="?act=viewerrhtml" target="_blank">显示当前页面</a>                      
		  <br>
		  <br>
		<?php endif; ?>
可通过这里上传一个HTML文件作为入网验证失败后显示的错误信息。
您可以包括 &quot;$PORTAL_MESSAGE$&quot;信息段。您还可以在内面加入登录区，以供用户再次尝试。</td>
	</tr>
	<tr style="display:none;"> 
	  <td width="22%" valign="top" class="vncell">Status page<br>
		contents</td>
	  <td class="vtable">
		<input name="statusfile" type="file" class="formfld" id="statusfile"><br>
		<?php if ($config['captiveportal']['page']['statustext']): ?>
		<a href="?act=viewstatushtml" target="_blank">View current page</a>
		  <br>
		  <br>
		<?php endif; ?>
The status page currently allows users to logout or change their password (local users only).
Example code for the form:<br>
		  <br>
		  <tt>&lt;form method=&quot;post&quot; action=&quot;$PORTAL_ACTION$&quot;&gt;<br>
		  &nbsp;&nbsp;&nbsp;&lt;input name=&quot;logout_id&quot; type=&quot;hidden&quot; value=&quot;$PORTAL_SESSIONID$&quot;&gt;<br>
&nbsp;&nbsp;&nbsp;&lt;input name=&quot;logout&quot; type=&quot;submit&quot; value=&quot;Logout&quot;&gt;<br>
		  &lt;/form&gt;<br>
		  &lt;form method=&quot;post&quot; action=&quot;$PORTAL_ACTION$&quot;&gt;<br>
		  &nbsp;&nbsp;&nbsp;&lt;input name=&quot;oldpass&quot; type=&quot;password&quot;&gt;<br>
		  &nbsp;&nbsp;&nbsp;&lt;input name=&quot;newpass&quot; type=&quot;password&quot;&gt;<br>
		  &nbsp;&nbsp;&nbsp;&lt;input name=&quot;newpass2&quot; type=&quot;password&quot;&gt;<br>
&nbsp;&nbsp;&nbsp;&lt;input name=&quot;change_pass&quot; type=&quot;submit&quot; value=&quot;Change Password&quot;&gt;<br>
		  &lt;/form&gt;</tt>
</td>
	</tr>
	<tr style="display:none;">
	  <td width="22%" valign="top" class="vncell">Logout page<br>
		contents</td>
	  <td class="vtable">
		<input name="logoutfile" type="file" class="formfld" id="logoutfile"><br>
		<?php if ($config['captiveportal']['page']['logouttext']): ?>
		<a href="?act=viewlogouthtml" target="_blank">View current page</a>
		  <br>
		  <br>
		<?php endif; ?>
The contents of the HTML file that you upload here are displayed when a logout occurs.
</td>
	</tr>
	<tr>
	  <td width="22%" valign="top">&nbsp;</td>
	  <td width="78%"> 
		<input name="Submit" type="submit" class="formbtn" value="保存" onClick="enable_change(true)"> 
	  </td>
	</tr>
	<tr> 
	  <td width="22%" valign="top">&nbsp;</td>
	  <td width="78%"><span class="vexpl"><span class="red"><strong>提示：<br>
		</strong></span>修改本页会断开所有用户连接！不要忘了在web认证启用的网络接口上打开DHCP服务，并将DHCP的默认／最大租约期设为大于在本页设置的超时时长。另外，还需要打开DNS转发器以供未验证用户使用。 </span></td>
	</tr>
  </table>
  </td>
  </tr>
  </table>
</form>
<script type="text/javascript">
<!--
enable_change(false);
//-->
</script>
<?php include("fend.inc"); ?>
