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

$pgtitle = array("�߼�����", "web��֤");
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
					$input_errors[] = "����һ����������ӿڴ����Ž�״̬ʱ����ʹ��WEB��֤���ܡ�";
					break;
				}
			}
			
			if ($_POST['httpslogin_enable']) {
			 	if (!$_POST['cert'] || !$_POST['key']) {
					$input_errors[] = "ͨ��HTTPS��¼ʱ���ṩ֤������롣";
				} else {
					if (!strstr($_POST['cert'], "BEGIN CERTIFICATE") || !strstr($_POST['cert'], "END CERTIFICATE"))
						$input_errors[] = "��������֤����Ч��";
					if (!strstr($_POST['key'], "BEGIN RSA PRIVATE KEY") || !strstr($_POST['key'], "END RSA PRIVATE KEY"))
						$input_errors[] = "�����������벻��ȷ��";
				}
				
				if (!$_POST['httpsname'] || !is_domain($_POST['httpsname'])) {
					$input_errors[] = "ͨ��HTTPS��¼�����HTTPS�����������֡�";
				}
			}
		}
		
		if ($_POST['timeout'] && (!is_numeric($_POST['timeout']) || ($_POST['timeout'] < 1))) {
			$input_errors[] = "��ʱʱ������Ϊ1���ӡ�";
		}
		if ($_POST['idletimeout'] && (!is_numeric($_POST['idletimeout']) || ($_POST['idletimeout'] < 1))) {
			$input_errors[] = "���г�ʱʱ������Ϊ1���ӡ�";
		}
		if ($_POST['peruserbw'] && (!isset($config['shaper']['enable']))) {
			$input_errors[] = "�����������������ܡ�";
		}
		if ($_POST['bwdefaultdn'] && (!is_numeric($_POST['bwdefaultdn']) || ($_POST['bwdefaultdn'] < 16))) {
			$input_errors[] = "ÿ�û����������ٶȱ������ 16��";
		}
		if ($_POST['bwdefaultup'] && (!is_numeric($_POST['bwdefaultup']) || ($_POST['bwdefaultup'] < 16))) {
			$input_errors[] = "ÿ�û������ϴ��ٶȱ������ 16��";
		}
		if (($_POST['radiusip'] && !is_ipaddr($_POST['radiusip']))) {
			$input_errors[] = "������һ���Ϸ���IP��ַ�� [".$_POST['radiusip']."]";
		}
		if (($_POST['radiusip2'] && !is_ipaddr($_POST['radiusip2']))) {
			$input_errors[] = "������һ���Ϸ���IP��ַ�� [".$_POST['radiusip2']."]";
		}
		if (($_POST['radiusport'] && !is_port($_POST['radiusport']))) {
			$input_errors[] = "������һ���Ϸ��Ķ˿ںš� [".$_POST['radiusport']."]";
		}
		if (($_POST['radiusport2'] && !is_port($_POST['radiusport2']))) {
			$input_errors[] = "������һ���Ϸ��Ķ˿ںš� [".$_POST['radiusport2']."]";
		}
		if (($_POST['radiusacctport'] && !is_port($_POST['radiusacctport']))) {
			$input_errors[] = "������һ���Ϸ��Ķ˿ںš� [".$_POST['radiusacctport']."]";
		}
		if ($_POST['maxproc'] && (!is_numeric($_POST['maxproc']) || ($_POST['maxproc'] < 4) || ($_POST['maxproc'] > 100))) {
			$input_errors[] = "����һ�����󲢷������������ 4 - 100 ֮�䡣";
		}
		$mymaxproc = $_POST['maxproc'] ? $_POST['maxproc'] : 16;
		if ($_POST['maxprocperip'] && (!is_numeric($_POST['maxprocperip']) || ($_POST['maxprocperip'] > $mymaxproc))) {
			$input_errors[] = "���Ե����û�IP����󲢷����������ô�������������";
		}
		
		if($_POST['user_reg']=='open' && strlen($_POST['user_reg_exp'])>0 && strtotime($_POST['user_reg_exp']) <= 0){
				$input_errors[] = "�û�ע�����.��Ч�����ڸ�ʽ����ȷ��ʽΪ MM/DD/YYYY����YYYY-MM-DD";
		}
		
		if($_POST['auth_method']=='sms' && !is_numeric($_POST['auth_sms_max_count'])){
			$input_errors[] = "������֤��.һ������ȡ�������벻��ȷ";
		}
		if($_POST['auth_method']=='sms' && !is_numeric($_POST['auth_sms_intvl'])){
			$input_errors[] = "������֤��.���ͼ�����벻��ȷ";
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
   	$tabs = array('WEB��֤' => 'services_captiveportal.php',
           		  'MAC������' => 'services_captiveportal_mac.php',
           		  'IP������' => 'services_captiveportal_ip.php',
           		  '�û�����' => 'services_captiveportal_users.php',
           		  '�ļ���ҳ�����' => 'services_captiveportal_filemanager.php');
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
		<strong>����WEB��֤</strong></td>
	</tr>
	<tr> 
	  <td width="22%" valign="top" class="vncellreq">�ӿ�</td>
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
		<span class="vexpl">ѡ����Ҫ����WEB��֤������ӿڡ�</span></td>
	</tr>
	<tr>
	  <td valign="top" class="vncell">��󲢷�����</td>
	  <td class="vtable">
		<table cellpadding="0" cellspacing="0" summary="max-conc-connection widget">
                 <tr>
           <td><input name="maxprocperip" type="text" class="formfld" id="maxprocperip" size="5" value="<?=htmlspecialchars($pconfig['maxprocperip']);?>"> ÿIP��ַ (0 = no limit)</td>
                 </tr>
                 <tr>
           <td><input name="maxproc" type="text" class="formfld" id="maxproc" size="5" value="<?=htmlspecialchars($pconfig['maxproc']);?>"> �ܹ�</td>
                 </tr>
               </table>
�����������������ӵ�HTTP(S)�������Ĳ����������� ��������ָ�ж����û����Ե�¼����������ָ�ж����û�����ͬʱ��WEB��֤ҳ�������֤��
Ĭ��ֵΪÿIP�ɿ�4�����ӣ��ܹ�������Ϊ16����</td>
	</tr>
	<tr>
	  <td valign="top" class="vncell">���г�ʱ�Ͽ�</td>
	  <td class="vtable">
		<input name="idletimeout" type="text" class="formfld" id="idletimeout" size="6" value="<?=htmlspecialchars($pconfig['idletimeout']);?>">
����<br>
�����г��������ʱ���󣬸��û������Ӿͻᱻ�Ͽ�����Ȼ����Ҳ���������������ϡ��˴��������û�д˳�ʱ�Ͽ�������</td>
	</tr>
	<tr> 
	  <td width="22%" valign="top" class="vncell">��ʱӲ�ԶϿ�</td>
	  <td width="78%" class="vtable"> 
		<input name="timeout" type="text" class="formfld" id="timeout" size="6" value="<?=htmlspecialchars($pconfig['timeout']);?>"> 
		����<br>
	  �����û���û�в������ڳ�������ʱ����������Ӳ�ԶϿ�����Ȼ��Ҳ���������������ϡ��˴��������û�д˳�ʱ�Ͽ��������������������˿��г�ʱ�Ͽ����������ó�ʱӲ�ԶϿ���</td>
	</tr>
	<tr> 
	  <td width="22%" valign="top" class="vncell">��¼������</td>
	  <td width="78%" class="vtable"> 
		<input name="logoutwin_enable" type="checkbox" class="formfld" id="logoutwin_enable" value="yes" <?php if($pconfig['logoutwin_enable']) echo "checked"; ?>>
		<strong>���õ�¼������</strong><br>
	  �˹������򿪣����û���¼�����һ�������ڡ�ͨ�������û������ڿ��г�ʱ��Ӳ��ʱ����֮ǰ�����Ͽ����ӡ�</td>
	</tr>
	<tr>
	  <td valign="top" class="vncell">�ض�������</td>
	  <td class="vtable">
		<input name="redirurl" type="text" class="formfld" id="redirurl" size="60" value="<?=htmlspecialchars($pconfig['redirurl']);?>">
		<br>
�����������ṩ��һ���ض������ӣ��û��������Ż���֤�ɹ�����ʱ�ᱻ���򵽸����ӣ���������һ��ʼ����ʵ����ӡ�</td>
	</tr>
	<tr>
      <td valign="top" class="vncell">�ص�¼</td>
      <td class="vtable">
	<input name="noconcurrentlogins" type="checkbox" class="formfld" id="noconcurrentlogins" value="yes" <?php if ($pconfig['noconcurrentlogins']) echo "checked"; ?>>
	<strong>��ֹͬһ�˺Ŷ��ն˵�¼</strong><br>
	������ֻ�����µĵ�¼����Ч������ͬһ�˺�/�û�����¼����ǰ�����Դ��û�����¼���������ӻᱻ�Ͽ���</td>
	</tr>
	<tr>
      <td valign="top" class="vncell">����MAC��ַ</td>
      <td class="vtable">
        <input name="nomacfilter" type="checkbox" class="formfld" id="nomacfilter" value="yes" <?php if ($pconfig['nomacfilter']) echo "checked"; ?>>
        <strong>�ر�MAC��ַ����</strong><br>
    ������wifiAP���û���¼���ٳ��Ժ�����MAC��ַ�Ƿ񱣳ֲ��䡣
	������Щ��������ã������û���MAC��ַ����ȷ��ʱ��һ������wifiAP���û�֮�仹��·�������������</td>
	</tr>
    <tr>
      <td valign="top" class="vncell">ÿ�û���������</td>
      <td class="vtable">
        <input name="peruserbw" type="checkbox" class="formfld" id="peruserbw" value="yes" <?php if ($pconfig['peruserbw']) echo "checked"; ?>>
        <strong>����ÿ�û���������</strong><br><br>
        <table cellpadding="0" cellspacing="0" summary="bandwidth-restriction widget">
        <tr>
        <td>Ĭ������&nbsp;&nbsp;</td>
        <td><input type="text" class="formfld" name="bwdefaultdn" id="bwdefaultdn" size="10" value="<?=htmlspecialchars($pconfig['bwdefaultdn']);?>"> Kbit/s</td>
        </tr>
        <tr>
        <td>Ĭ���ϴ�</td>
        <td><input type="text" class="formfld" name="bwdefaultup" id="bwdefaultup" size="10" value="<?=htmlspecialchars($pconfig['bwdefaultup']);?>"> Kbit/s</td>
        </tr></table>
        <br>
        �����ѡ����ã������������Ż�ʹ�õ����ߵ�ÿ�û�ΪĬ�ϴ������ջ������� 0 �����ơ�<strong>����</strong>������������ʹ��������Ч��</td>
        </tr>
	<tr> 
	  <td width="22%" valign="top" class="vncell">��֤��ʽ</td>
	  <td width="78%" class="vtable"> 
		<table cellpadding="0" cellspacing="0" summary="authentication widget">
		<tr>
		  <td colspan="2"><input name="auth_method" type="radio" id="auth_method_none" value="none" onClick="enable_change(false)" <?php if($pconfig['auth_method']!="local" && $pconfig['auth_method']!="radius") echo "checked"; ?>>
  ����֤��������ʾ��¼ҳ��/չʾҳ�棩</td>  
		  </tr>
		<tr>
		  <td colspan="2"><input name="auth_method" type="radio" id="auth_method_local" value="local" onClick="enable_change(false)" <?php if($pconfig['auth_method']=="local") echo "checked"; ?>>
  wifiAP<a href="services_wifiap.php">������֤</a></td>  
		  </tr>
		  <tr>
		  <td colspan="2"><input name="auth_method" type="radio" id="auth_method_sms" value="sms" onClick="enable_change(false)" <?php if($pconfig['auth_method']=="sms") echo "checked"; ?>>
  ������֤����֤</td>
		  </tr>
		<tr id="smspara1" <?php if($smspara=='none'){ ?> style="display:none;" <?php }?>>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		</tr>
		<tr id="smspara2" <?php if($smspara=='none'){ ?> style="display:none;" <?php }?>>
			<td colspan="2">
				<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="radius-server widget">
        	<tr> 
            	<td colspan="2" valign="top" class="optsect_t2">������֤�����</td>
					</tr>
					<tr>
						<td class="vncell" valign="top">ͨ��API</td>
						<td class="vtable"><input name="auth_sms_chnl" type="text" class="formfld" id="auth_sms_chnl" size="20" value="<?php echo $pconfig['auth_sms_chnl']; ?>">
						�ֻ�����{mobile}, ����������{msg}.</td>
					</tr>
					<tr>
						<td class="vncell" valign="top">��������</td>
						<td class="vtable"><input name="auth_sms_text" type="text" class="formfld" id="auth_sms_text" size="20" value="<?php echo $pconfig['auth_sms_text']; ?>">
						���ɵ���֤����{verifycode}.</td>
					</tr>
					<tr>
						<td class="vncell" valign="top">���ŷ��ͳɹ���־</td>
						<td class="vtable"><input name="auth_sms_ok_flag" type="text" class="formfld" id="auth_sms_ok_flag" size="20" value="<?php echo $pconfig['auth_sms_ok_flag']; ?>"></td>
					</tr>
					<tr>
						<td class="vncell" valign="top">һ������ȡ����</td>
						<td class="vtable"><input name="auth_sms_max_count" type="text" class="formfld" id="auth_sms_max_count" size="20" value="<?php echo $pconfig['auth_sms_max_count']; ?>"></td>
					</tr>
					<tr>
						<td class="vncell" valign="top">��ȡ���</td>
						<td class="vtable"><input name="auth_sms_intvl" type="text" class="formfld" id="auth_sms_intvl" size="20" value="<?php echo $pconfig['auth_sms_intvl']; ?>">�Է���Ϊ��λ</td>
					</tr>
					<tr>
						<td class="vncell" valign="top">�û�����</td>
						<td class="vtable"><input name="auth_sms_utt" type="radio" id="auth_sms_utt_0" size="20" value="0" <?php if($pconfig['auth_sms_utt']=='0'){echo 'checked'; }?>>һ�����û� 
							<input name="auth_sms_utt" type="radio" id="auth_sms_utt_1" size="20" value="1" <?php if($pconfig['auth_sms_utt']=='1'){echo 'checked'; }?>>��ͨ�û�
							</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
		  <td colspan="2"><input name="auth_method" type="radio" id="auth_method_api" value="api" onClick="enable_change(false)" <?php if($pconfig['auth_method']=="api") echo "checked"; ?>>
  �ⲿAPI��֤</td>
		  </tr>
		<tr id="apipara1" <?php if($apipara=='none'){ ?> style="display:none;" <?php }?>>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		</tr>
		<tr id="apipara2" <?php if($apipara=='none'){ ?> style="display:none;" <?php }?>>
			<td colspan="2">
				<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="radius-server widget">
        	<tr> 
            	<td colspan="2" valign="top" class="optsect_t2">API��֤����</td>
					</tr>
					<tr>
						<td class="vncell" valign="top" width="50">API</td>
						<td class="vtable"><input name="auth_api_url" type="text" class="formfld" id="auth_api_url" size="20" value="<?php echo $pconfig['auth_api_url']; ?>"><br>
						���磺http://www.mydomain.cn/api.php. ��ʱ���õ���ʽΪhttp://www.mydomain.cn/api.php?username=�û���&password=���ܵ�����&rnd=8λ�����<br>
						���ܵ�����=md5(Key&����&�����)</td>
					</tr>
					<tr>
						<td class="vncell" valign="top" width="50">Key</td>
						<td class="vtable"><input name="auth_api_key" type="text" class="formfld" id="auth_api_key" size="20" value="<?php echo $pconfig['auth_api_key']; ?>"><br>
						�������õ�key�����API�趨��һ��</td>
					</tr>
					<tr>
						<td class="vncell" valign="top">��֤ͨ������</td>
						<td class="vtable"><input name="auth_api_success_flag" type="text" class="formfld" id="auth_api_success_flag" size="20" value="<?php echo $pconfig['auth_api_success_flag']; ?>">
						��ͨ���ķ�����Ϣ����Ϊ������Ϣ��ʾ.</td>
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
	  <td width="22%" valign="top" class="vncell">�û�ע��</td>
	  <td width="78%" class="vtable"> 
		<table cellpadding="0" cellspacing="0" summary="authentication widget">
		<tr>
		  <td colspan="2"><input name="user_reg" type="radio" id="user_reg_close" value="close" onClick="enable_change(false)" <?php if($pconfig['user_reg']=="close") echo "checked"; ?>>
  �ر�ע�ᣨ�û�����ע�ᣩ</td>  
		  </tr>
		<tr>
		  <td colspan="2"><input name="user_reg" type="radio" id="user_reg_open" value="open" onClick="enable_change(false)" <?php if($pconfig['user_reg']=="open") echo "checked"; ?>>
  ����ע�ᣨ�û���ͨ��ҳ�����ע�ᣩ</td>  
		  </tr>
		</table>
		<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="radius-server widget">
        	<tr> 
            	<td colspan="2" valign="top" class="optsect_t2">�û�ע�����</td>
			</tr>
			<tr>
				<td class="vncell" valign="top">��Ч��</td>
				<td class="vtable"><input name="user_reg_exp" type="text" class="formfld" id="user_reg_exp" size="20" value="<?php echo $pconfig['user_reg_exp']; ?>">(YYYY-MM-DD)<br>
				ע����û�����Ч��.</td>
			</tr>
		</table>
	</td>
	</tr>
	<tr>
      <td valign="top" class="vncell">HTTPS ��¼��֤</td>
      <td class="vtable">
        <input name="httpslogin_enable" type="checkbox" class="formfld" id="httpslogin_enable" value="yes" <?php if($pconfig['httpslogin_enable']) echo "checked"; ?>>
        <strong>���� HTTPS ��¼��֤</strong><br>
    �����ô���û���������ͻᰴHTTPS�����Լ��ܵķ�ʽ���䣬�Է�����ȡ������������֤���ƥ���˽����Կ��Ҫ���������롣</td>
	  </tr>
	<tr>
      <td valign="top" class="vncell">HTTPS ����������</td>
      <td class="vtable">
        <input name="httpsname" type="text" class="formfld" id="httpsname" size="30" value="<?=htmlspecialchars($pconfig['httpsname']);?>"><br>
    �û��˻�ͨ��HTTPS POST�����Ա�����ʽ�ύ���÷�������֤���еġ��������֡���CN�����������Ҫһ�£������û���������ܿ��ܻ���ʾһ����ȫ���棩��������ȷ��DNS���Խ����÷���������</td>
	  </tr>
	<tr>
      <td valign="top" class="vncell">HTTPS ֤��</td>
      <td class="vtable">
        <textarea name="cert" cols="65" rows="7" id="cert" class="formpre"><?=htmlspecialchars($pconfig['cert']);?></textarea>
        <br>
    �ڴ˴�ճ�� X.509 PEM ��ʽ����ǩ֤�顣</td>
	  </tr>
	<tr>
      <td valign="top" class="vncell">HTTPS ˽����Կ</td>
      <td class="vtable">
        <textarea name="key" cols="65" rows="7" id="key" class="formpre"><?=htmlspecialchars($pconfig['key']);?></textarea>
        <br>
    �ڴ˴�ճ�� PEM ��ʽ�� RSA ˽����Կ��</td>
	  </tr>
	<tr style="display:none;"> 
	  <td width="22%" valign="top" class="vncellreq">��֤ҳ������</td>
	  <td width="78%" class="vtable">    
		<?=$mandfldhtml;?><input type="file" name="htmlfile" class="formfld" id="htmlfile"><br>
		<?php if ($config['captiveportal']['page']['htmltext']): ?>
		<a href="?act=viewhtml" target="_blank">��ʾ��ǰҳ��</a>                      
		  <br>
		  <br>
		<?php endif; ?>
		  ��ͨ�������ϴ�һ��HTML�ļ����������Ż�ҳ�棨�����򱣳ֵ�ǰҳ�治�䣩���ϴ���ҳ������� (POST to &quot;$PORTAL_ACTION$&quot;) �����ټ�һ���ύ��ť (name=&quot;accept&quot;) ���һ��name=&quot;redirurl&quot; and value=&quot;$PORTAL_REDIRURL$&quot;��������
���� &quot;auth_user&quot; and &quot;auth_pass&quot; ���������Ҫ��֤�Ļ�����������֤����ɹ���
��ʾ����<br>
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
	  <td width="22%" valign="top" class="vncell">��֤ʧ��ҳ<br>
		����</td>
	  <td class="vtable">
		<input name="errfile" type="file" class="formfld" id="errfile"><br>
		<?php if ($config['captiveportal']['page']['errtext']): ?>
		<a href="?act=viewerrhtml" target="_blank">��ʾ��ǰҳ��</a>                      
		  <br>
		  <br>
		<?php endif; ?>
��ͨ�������ϴ�һ��HTML�ļ���Ϊ������֤ʧ�ܺ���ʾ�Ĵ�����Ϣ��
�����԰��� &quot;$PORTAL_MESSAGE$&quot;��Ϣ�Ρ�������������������¼�����Թ��û��ٴγ��ԡ�</td>
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
		<input name="Submit" type="submit" class="formbtn" value="����" onClick="enable_change(true)"> 
	  </td>
	</tr>
	<tr> 
	  <td width="22%" valign="top">&nbsp;</td>
	  <td width="78%"><span class="vexpl"><span class="red"><strong>��ʾ��<br>
		</strong></span>�޸ı�ҳ��Ͽ������û����ӣ���Ҫ������web��֤���õ�����ӿ��ϴ�DHCP���񣬲���DHCP��Ĭ�ϣ������Լ����Ϊ�����ڱ�ҳ���õĳ�ʱʱ�������⣬����Ҫ��DNSת�����Թ�δ��֤�û�ʹ�á� </span></td>
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
