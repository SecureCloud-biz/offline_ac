#!/usr/local/bin/php
<?php
/*
	$Id: services_captiveportal_mac.php 238 2008-01-21 18:33:33Z mkasper $
	part of wifiAP (http://wifiap.cn)
	
	Copyright (C) 2004 Dinesh Nair <dinesh@alphaque.com>
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

$pgtitle = array("�߼�����", "WEB��֤", "MAC������");
require("guiconfig.inc");

if (!is_array($config['captiveportal']['passthrumac']))
	$config['captiveportal']['passthrumac'] = array();

passthrumacs_sort();
$a_passthrumacs = &$config['captiveportal']['passthrumac'] ;

if ($_POST) {

	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			$retval = captiveportal_passthrumac_configure();
		}
		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			if (file_exists($d_passthrumacsdirty_path)) {
				config_lock();
				unlink($d_passthrumacsdirty_path);
				config_unlock();
			}
		}
	}
}

if ($_GET['act'] == "del") {
	if ($a_passthrumacs[$_GET['id']]) {
		unset($a_passthrumacs[$_GET['id']]);
		write_config();
		touch($d_passthrumacsdirty_path);
		header("Location: services_captiveportal_mac.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<form action="services_captiveportal_mac.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_passthrumacsdirty_path)): ?><p>
<?php print_info_box_np("MAC�������Ѹ��ġ����Ӧ��ʹ����Ч��");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Ӧ�ø���"></p>
<?php endif; ?>
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
  <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
	<tr>
	  <td width="30%" class="listhdrr">MAC��ַ</td>
	  <td width="60%" class="listhdr">����</td>
	  <td width="10%" class="list"></td>
	</tr>
  <?php $i = 0; foreach ($a_passthrumacs as $mac): ?>
	<tr>
	  <td class="listlr">
		<?=strtolower($mac['mac']);?>
	  </td>
	  <td class="listbg">
		<?=htmlspecialchars($mac['descr']);?>&nbsp;
	  </td>
	  <td valign="middle" nowrap class="list"> <a href="services_captiveportal_mac_edit.php?id=<?=$i;?>"><img src="e.gif" title="�޸�" width="17" height="17" border="0" alt="�޸�"></a>
		 &nbsp;<a href="services_captiveportal_mac.php?act=del&amp;id=<?=$i;?>" onclick="return confirm('ȷ��ɾ����')"><img src="x.gif" title="ɾ��" width="17" height="17" border="0" alt="ɾ��"></a></td>
	</tr>
  <?php $i++; endforeach; ?>
	<tr> 
	  <td class="list" colspan="2">&nbsp;</td>
	  <td class="list"> <a href="services_captiveportal_mac_edit.php"><img src="plus.gif" title="���" width="17" height="17" border="0" alt="���"></a></td>
	</tr>
	<tr>
	<td colspan="2" class="list"><span class="vexpl"><span class="red"><strong>
	��ʾ��<br>
	</strong></span>
	MAC���������豸����Ҫ��֤�Ϳ���ֱ��������</span></td>
	<td class="list">&nbsp;</td>
	</tr>
  </table>
  </td>
  </tr>
  </table>
</form>
<?php include("fend.inc"); ?>
