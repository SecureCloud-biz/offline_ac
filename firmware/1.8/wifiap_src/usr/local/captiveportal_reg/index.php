#!/usr/local/bin/php
<?php 
require("guiconfig.inc");

if($config['captiveportal']['user_reg']!=='open'){
	echo '�û�ע���ѹر�';
	exit();
}

if (!is_array($config['captiveportal']['user'])) {
	$config['captiveportal']['user'] = array();
}
captiveportal_users_sort();
$a_user = &$config['captiveportal']['user'];


if ($_POST) {
	
	$pconfig = $_POST;

	if (preg_match("/[^a-zA-Z0-9\.\-_]/", $_POST['username']))
		$msg.='�û��������˷Ƿ��ַ���';
		
		
	if (($_POST['password']) && ($_POST['password'] != $_POST['password2']))
		$msg.='�������벻һ�¡�';
	
	if (strlen($msg)<=0 && !(isset($id) && $a_user[$id])) {
		/* make sure there are no dupes */
		foreach ($a_user as $userent) {
			if ($userent['name'] == $_POST['username']) {
				$msg.='���û����Ѵ��ڡ�';
				break;
			}
		}
	}
	
	if (strlen($msg)<=0) {
	
		if (isset($id) && $a_user[$id])
			$userent = $a_user[$id];
		
		$userent['name'] = $_POST['username'];
		$userent['fullname'] = $_POST['name'];
		$userent['expirationdate'] = $_POST['expirationdate'];
		$userent['expirationdate'] = $config['captiveportal']['user_reg_exp'];
		
		if($_POST['password'])
			$userent['password'] = md5($_POST['password']);
		
		if (isset($id) && $a_user[$id])
			$a_user[$id] = $userent;
		else
			$a_user[] = $userent;
		
		write_config();
		
		$msg='ע��ɹ�';
	}
}

$htmltext = file_get_contents("{$g['varetc_path']}/reg.html");
$htmltext = str_replace("\$msg\$", htmlspecialchars($msg), $htmltext);

echo $htmltext;
?>