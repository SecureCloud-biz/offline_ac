#!/usr/local/bin/php
<?php
/*
	$Id: services_captiveportal_filemanager.php 238 2008-01-21 18:33:33Z mkasper $
	part of wifiAP (http://wifiap.cn)

	Copyright (C) 2005-2006 Jonathan De Graeve (jonathan.de.graeve@imelda.be)
	and Paul Taylor (paultaylor@winn-dixie.com).
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

$pgtitle = array("�߼�����", "WEB��֤", "�ļ���ҳ�����");

require_once("guiconfig.inc");

if (!is_array($config['captiveportal']['element']))
	$config['captiveportal']['element'] = array();

cpelements_sort();
$a_element = &$config['captiveportal']['element'];

// Calculate total size of all files
$total_size = 0;
foreach ($a_element as $element) {
	$total_size += $element['size'];
}

if ($_POST) {
    unset($input_errors);
    
    if (is_uploaded_file($_FILES['new']['tmp_name'])) {
    	
    	$name = $_FILES['new']['name'];
    	$size = filesize($_FILES['new']['tmp_name']);
    	
    	$file_ext = explode('.',$name);
    	$fc=count($file_ext);
    	if($fc>1){
    		$file_ext = $file_ext[$fc-1];
    	}else{
    		$file_ext='';
    	}

	    // is there already a file with that name?
	    foreach ($a_element as $element) {
				if ($element['name'] == $name) {
					$input_errors[] = "ͬ�����ļ��Ѵ���.";
					break;
				}
			}
			
			// check total file size
			if (($total_size + $size) > $g['captiveportal_element_sizelimit']) {
				$input_errors[] = "�ϴ��������ļ���С���ܳ��� " .
					format_bytes($g['captiveportal_element_sizelimit']) . ".";
			}
			
			if (!$input_errors) {
				$element = array();
				$element['name'] = $name;
				$element['size'] = $size;
				$element['content'] = base64_encode(file_get_contents($_FILES['new']['tmp_name']));
				
				$a_element[] = $element;
				//'ipad' || $mydevice[1]=='iphone' || $mydevice[1]=='android' || $mydevice[1]=='smartphone' || $mydevice[1]=='blackberry' || $mydevice[1]=='opera' || $mydevice[1]=='palm'
				if($name=='captiveportal.html' || $name=='captiveportal_iphone.html' || $name=='captiveportal_ipad.html' || $name=='captiveportal_android.html' || $name=='captiveportal_smartphone.html' || $name=='captiveportal_blackberry.html' || $name=='captiveportal_opera.html' || $name=='captiveportal_palm.html'){
					if($name=='captiveportal_ipad.html'){
						$config['captiveportal']['page']['htmltext_ipad'] = $element['content'];
					}elseif($name=='captiveportal_iphone.html'){
						$config['captiveportal']['page']['htmltext_iphone'] = $element['content'];
					}elseif($name=='captiveportal_android.html'){
						$config['captiveportal']['page']['htmltext_android'] = $element['content'];
					}elseif($name=='captiveportal_smartphone.html'){
						$config['captiveportal']['page']['htmltext_smartphone'] = $element['content'];
					}elseif($name=='captiveportal_blackberry.html'){
						$config['captiveportal']['page']['htmltext_blackberry'] = $element['content'];
					}elseif($name=='captiveportal_opera.html'){
						$config['captiveportal']['page']['htmltext_opera'] = $element['content'];
					}elseif($name=='captiveportal_palm.html'){
						$config['captiveportal']['page']['htmltext_palm'] = $element['content'];
					}else{
						$config['captiveportal']['page']['htmltext'] = $element['content'];
					}
					
					$fd = @fopen("{$g['varetc_path']}/{$name}", "w");
        	if ($fd) {
            fwrite($fd, base64_decode($element['content']));
            fclose($fd);    
        	}
				}
				if($name=='captiveportal-error.html' || $name=='captiveportal-error_iphone.html' || $name=='captiveportal-error_ipad.html' || $name=='captiveportal-error_android.html' || $name=='captiveportal-error_smartphone.html' || $name=='captiveportal-error_blackberry.html' || $name=='captiveportal-error_opera.html' || $name=='captiveportal-error_palm.html'){
					if($name=='captiveportal-error_ipad.html'){
						$config['captiveportal']['page']['errtext_ipad'] = $element['content'];
					}elseif($name=='captiveportal-error_iphone.html'){
						$config['captiveportal']['page']['errtext_iphone'] = $element['content'];
					}elseif($name=='captiveportal-error_android.html'){
						$config['captiveportal']['page']['errtext_android'] = $element['content'];
					}elseif($name=='captiveportal-error_smartphone.html'){
						$config['captiveportal']['page']['errtext_smartphone'] = $element['content'];
					}elseif($name=='captiveportal-error_blackberry.html'){
						$config['captiveportal']['page']['errtext_blackberry'] = $element['content'];
					}elseif($name=='captiveportal-error_opera.html'){
						$config['captiveportal']['page']['errtext_opera'] = $element['content'];
					}elseif($name=='captiveportal-error_palm.html'){
						$config['captiveportal']['page']['errtext_palm'] = $element['content'];
					}else{
						$config['captiveportal']['page']['errtext'] = $element['content'];
					}
					
					$fd = @fopen("{$g['varetc_path']}/{$name}", "w");
        	if ($fd) {
            fwrite($fd, base64_decode($element['content']));
            fclose($fd);    
        	}
				}
				if($name=='captiveportal-logout.html' || $name=='captiveportal-logout_iphone.html' || $name=='captiveportal-logout_ipad.html' || $name=='captiveportal-logout_android.html' || $name=='captiveportal-logout_smartphone.html' || $name=='captiveportal-logout_blackberry.html' || $name=='captiveportal-logout_opera.html' || $name=='captiveportal-logout_palm.html'){
					if($name=='captiveportal-logout_ipad.html'){
						$config['captiveportal']['page']['logouttext_ipad'] = $element['content'];
					}elseif($name=='captiveportal-logout_iphone.html'){
						$config['captiveportal']['page']['logouttext_iphone'] = $element['content'];
					}elseif($name=='captiveportal-logout_android.html'){
						$config['captiveportal']['page']['logouttext_android'] = $element['content'];
					}elseif($name=='captiveportal-logout_smartphone.html'){
						$config['captiveportal']['page']['logouttext_smartphone'] = $element['content'];
					}elseif($name=='captiveportal-logout_blackberry.html'){
						$config['captiveportal']['page']['logouttext_blackberry'] = $element['content'];
					}elseif($name=='captiveportal-logout_opera.html'){
						$config['captiveportal']['page']['logouttext_opera'] = $element['content'];
					}elseif($name=='captiveportal-logout_palm.html'){
						$config['captiveportal']['page']['logouttext_palm'] = $element['content'];
					}else{
						$config['captiveportal']['page']['logouttext'] = $element['content'];
					}
					
					$fd = @fopen("{$g['varetc_path']}/{$name}", "w");
        	if ($fd) {
            fwrite($fd, base64_decode($element['content']));
            fclose($fd);    
        	}
				}
				if($name=='captiveportal-status.html' || $name=='captiveportal-status_iphone.html' || $name=='captiveportal-status_ipad.html' || $name=='captiveportal-status_android.html' || $name=='captiveportal-status_smartphone.html' || $name=='captiveportal-status_blackberry.html' || $name=='captiveportal-status_opera.html' || $name=='captiveportal-status_palm.html'){
					if($name=='captiveportal-status_ipad.html'){
						$config['captiveportal']['page']['statustext_ipad'] = $element['content'];
					}elseif($name=='captiveportal-status_iphone.html'){
						$config['captiveportal']['page']['statustext_iphone'] = $element['content'];
					}elseif($name=='captiveportal-status_android.html'){
						$config['captiveportal']['page']['statustext_android'] = $element['content'];
					}elseif($name=='captiveportal-status_smartphone.html'){
						$config['captiveportal']['page']['statustext_smartphone'] = $element['content'];
					}elseif($name=='captiveportal-status_blackberry.html'){
						$config['captiveportal']['page']['statustext_blackberry'] = $element['content'];
					}elseif($name=='captiveportal-status_opera.html'){
						$config['captiveportal']['page']['statustext_opera'] = $element['content'];
					}elseif($name=='captiveportal-status_palm.html'){
						$config['captiveportal']['page']['statustext_palm'] = $element['content'];
					}else{
						$config['captiveportal']['page']['statustext'] = $element['content'];
					}
					
					$fd = @fopen("{$g['varetc_path']}/{$name}", "w");
        	if ($fd) {
            fwrite($fd, base64_decode($element['content']));
            fclose($fd);    
        	}
				}
				
				if($name=='reg.html' || $name=='reg_iphone.html' || $name=='reg_ipad.html' || $name=='reg_android.html' || $name=='reg_smartphone.html' || $name=='reg_blackberry.html' || $name=='reg_opera.html' || $name=='reg_palm.html'){
					if($name=='reg_iphone.html'){
						$config['captiveportal']['page']['regtext_ipad'] = $element['content'];
					}elseif($name=='reg_iphone.html'){
						$config['captiveportal']['page']['regtext_iphone'] = $element['content'];
					}elseif($name=='reg_android.html'){
						$config['captiveportal']['page']['regtext_android'] = $element['content'];
					}elseif($name=='reg_smartphone.html'){
						$config['captiveportal']['page']['regtext_smartphone'] = $element['content'];
					}elseif($name=='reg_blackberry.html'){
						$config['captiveportal']['page']['regtext_blackberry'] = $element['content'];
					}elseif($name=='reg_opera.html'){
						$config['captiveportal']['page']['regtext_opera'] = $element['content'];
					}elseif($name=='reg_palm.html'){
						$config['captiveportal']['page']['regtext_palm'] = $element['content'];
					}else{
						$config['captiveportal']['page']['regtext'] = $element['content'];
					}
					
					$fd = @fopen("{$g['varetc_path']}/{$name}", "w");
        	if ($fd) {
            fwrite($fd, base64_decode($element['content']));
            fclose($fd);    
        	}
				}
				
				write_config();
				captiveportal_write_elements();
				header("Location: services_captiveportal_filemanager.php");
				exit;
			}
			
    }
} else {
	if (($_GET['act'] == "del") && $a_element[$_GET['id']]) {
		unset($a_element[$_GET['id']]);
		write_config();
		captiveportal_write_elements();
		header("Location: services_captiveportal_filemanager.php");
		exit;
	}
}

?>
<?php include("fbegin.inc"); ?>
<form action="services_captiveportal_filemanager.php" method="post" enctype="multipart/form-data" name="iform" id="iform">
<?php if ($input_errors) print_input_errors($input_errors); ?>
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
	<table width="80%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
      <tr>
        <td width="70%" class="listhdrr">Name</td>
        <td width="20%" class="listhdr">Size</td>
        <td width="10%" class="list"></td>
      </tr>
  <?php $i = 0; foreach ($a_element as $element): ?>
  	  <tr>
		<td class="listlr"><?=htmlspecialchars($element['name']);?></td>
		<td class="listr" align="right"><?=format_bytes($element['size']);?></td>
		<td valign="middle" nowrap class="list">
		<a href="services_captiveportal_filemanager.php?act=del&id=<?=$i;?>" onclick="return confirm('��ȷ��Ҫɾ�����ļ�?')"><img src="x.gif" title="delete file" width="17" height="17" border="0" alt="ɾ���ļ�"></a>
		</td>
	  </tr>
  <?php $i++; endforeach; ?>
  
  <?php if (count($a_element) > 0): ?>
  	  <tr>
		<td class="listlr" style="background-color: #eee"><strong>TOTAL</strong></td>
		<td class="listr" style="background-color: #eee" align="right"><strong><?=format_bytes($total_size);?></strong></td>
		<td valign="middle" nowrap class="list"></td>
	  </tr>
  <?php endif; ?>
  
  <?php if ($_GET['act'] == 'add'): ?>
	  <tr>
		<td class="listlr" colspan="2"><input type="file" name="new" class="formfld" size="40" id="new"> 
		<input name="Submit" type="submit" class="formbtn" value="Upload"></td>
		<td valign="middle" nowrap class="list">
		<a href="services_captiveportal_filemanager.php"><img src="x.gif" title="cancel" width="17" height="17" border="0" alt="cancel"></a>
		</td>
	  </tr>
  <?php else: ?>
	  <tr>
		<td class="list" colspan="2"></td>
		<td class="list"> <a href="services_captiveportal_filemanager.php?act=add"><img src="plus.gif" title="add file" width="17" height="17" border="0" alt="add file"></a></td>
	  </tr>
  <?php endif; ?>
	</table>
	<span class="vexpl"><span class="red"><strong>
	˵��:<br>
	</strong></span>
	1.�����������ϴ����ļ����������֤�������ĸ�Ŀ¼. ���������֤ҳ����ֱ��ʹ�����ǣ�����:<br>
	<tt>&lt;img src=&quot;test.jpg&quot; width=... height=...&gt;</tt>
	<br><br>
	2.�����ļ����ܴ�С����Ϊ<?=format_bytes($g['captiveportal_element_sizelimit']);?>.<br><br>
	3.��֤ҳ��˵��(ֱ������Ӧ�ļ����ϴ����ɸ���)��<Br>
	  captiveportal.html ��֤��¼ҳ��<Br>
	  captiveportal-error.html ��֤����ҳ��<Br>
	  captiveportal-status.html ��¼״̬ҳ�棬���������Ƿ��ڵ�¼����ʾ<Br>
	  captiveportal-logout.html �˳���¼����ʾҳ��<br>
	  reg.html �û�ע��ҳ��(�����û�ע����ʹ��)<Br><br>
	4.���䲻ͬ�ն˺���������ֿ�����android�ն�,ipad,iphone,windows mobile,��ù,palm,3��������֤ҳ�涼���Ը����ն���ʾ��Ӧ��ҳ�棬ҳ��������Ӧ�ĺ�׺���ɡ�<br>
	  ���磺captiveportal.html��¼ҳ��Ҫ�����䵽iphoneʱʹ�õ�ҳ�棬���ļ���captiveportal_iphone.html����<br>
	  <table>
	  	<tr><td colspan="2">��׺�б�</td></tr>
	  	<tr><td>�ն�</td><td>��׺</td></tr>
	  	<tr><td>android</td><td>_android</td></tr>
	  	<tr><td>ipad</td><td>_ipad</td></tr>
	  	<tr><td>iphone</td><td>_iphone</td></tr>
	  	<tr><td>windows mobile</td><td>_smartphone</td></tr>
	  	<tr><td>��ù</td><td>_blackberry</td></tr>
	  	<tr><td>palm</td><td>_palm</td></tr>
		</table>
	</span>
</td>
</tr>
</table>
</form>
<?php include("fend.inc"); ?>	
