#!/usr/local/bin/php
<?php 
/*
	$Id: services_captiveportal_users.php 238 2008-01-21 18:33:33Z mkasper $
	part of wifiAP (http://wifiap.cn)
	
	Copyright (C) 2003-2007 Manuel Kasper <mk@neon1.net>.
	All rights reserved.
	Copyright (C) 2005 Pascal Suter <d-monodev@psuter.ch>.
	All rights reserved. 
	(files was created by Pascal based on the source code of services_captiveportal.php from Manuel)
	
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
$pgtitle = array("�߼�����", "WEB��֤", "�û�����");
require("guiconfig.inc");

if (!is_array($config['captiveportal']['user'])) {
	$config['captiveportal']['user'] = array();
}
captiveportal_users_sort();
$a_user = &$config['captiveportal']['user'];

if ($_GET['act'] == "del") {
	if ($a_user[$_GET['id']]) {
		unset($a_user[$_GET['id']]);
		write_config();
		header("Location: services_captiveportal_users.php");
		exit;
	}
}

//erase expired accounts
$changed = false;
for ($i = 0; $i < count($a_user); $i++) {
	if ($a_user[$i]['expirationdate'] && (strtotime("-1 day") > strtotime($a_user[$i]['expirationdate']))) {
		unset($a_user[$i]);
		$changed = true;
	}
}
if ($changed) {
	write_config();
	header("Location: services_captiveportal_users.php");
	exit;
}

$username=$_POST['username'];
$page=0 + $_POST['page'];
if($page==0){
	$page=1; 
}
$pagerecord=30;
$fromidx=($page-1) * $pagerecord;
$toidx=$fromidx + $pagerecord -1;


?>
<?php include("fbegin.inc"); ?>
<script>
function submitform(pagenum) {
	document.searchuserform.page.value = pagenum;
	document.searchuserform.submit();
}

</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
  <tr><td>
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
  	<form action="" method="post" name="searchuserform" id="searchuserform">
	  	<table>
		  	<tr>
					<td class="listhdrr">
						�û�����
					</td>
					<td class="listhdrr">
						<input name="username" type="text">
					</td>
					<td class="listhdr">
						<input type="submit" name="search" value="����"><input type="hidden" name="page">
					</td>
				</tr>
			</table>
		</form>
    <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
                <tr>
                  <td width="35%" class="listhdrr">�û���</td>
                  <td width="20%" class="listhdrr">ȫ��</td>
                  <td width="35%" class="listhdr">����ʱ��</td>
                  <td width="10%" class="list"></td>
		</tr>
	<?php $i = 0; foreach($a_user as $userent): ?>
	<?php if(strlen($username)>0){
					if(strpos($userent['name'],$username)===false){
						continue ;
					}
				}
				if($i>=$fromidx && $i <=$toidx){
	?>
		<tr>
                  <td class="listlr">
                    <?=htmlspecialchars($userent['name']); ?>&nbsp;
                  </td>
                  <td class="listr">
                    <?=htmlspecialchars($userent['fullname']);?>&nbsp;
                  </td>
                  <td class="listbg">
                    <?=$userent['expirationdate']; ?>&nbsp;
                  </td>
                  <td valign="middle" nowrap class="list"> <a href="services_captiveportal_users_edit.php?id=<?=$i; ?>"><img src="e.gif" title="�޸��û�" width="17" height="17" border="0" alt="�޸��û�"></a>
                     &nbsp;<a href="services_captiveportal_users.php?act=del&amp;id=<?=$i; ?>" onclick="return confirm('��ȷ��Ҫɾ�����û���')"><img src="x.gif" title="ɾ���û�" width="17" height="17" border="0" alt="ɾ���û�"></a></td>
		</tr>
	<?php 
			}
			$i++; endforeach; ?>
		<?php $recordcount = $i;
					$pagecount = ceil($recordcount/$pagerecord);
		?>
		<tr> 
			  <td class="list" colspan="3">[<?=$page ?>/<?=$pagecount ?>] <?php if($page>1){ ?><a href="javascript:submitform(1);">��ҳ</a><?php }else{ ?>��ҳ<?php } ?> <?php if($page>1){ ?><a href="javascript:submitform(<?=$page-1 ?>);">��һҳ</a><?php }else{ ?>��һҳ<?php } ?> <?php if($page<$pagecount){ ?><a href="javascript:submitform(<?=$page+1 ?>);">��һҳ</a><?php }else{ ?>��һҳ<?php } ?> <?php if($page<$pagecount){ ?><a href="javascript:submitform(<?=$pagecount ?>)">βҳ</a><?php }else{ ?>βҳ<?php } ?></td>
			  <td class="list"> <a href="services_captiveportal_users_edit.php"><img src="plus.gif" title="����û�" width="17" height="17" border="0" alt="����û�"></a></td>
		</tr>
 </table>     
</td>
</tr>
</table>
<?php include("fend.inc"); ?>
