#!/usr/local/bin/php
<?php 
/*
	$Id: services_captiveportal_mac_edit.php 411 2010-11-12 12:58:55Z mkasper $
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

$pgtitle = array("高级服务", "WEB认证", "修改MAC白名单");
require("guiconfig.inc");

if (!is_array($config['captiveportal']['passthrumac']))
	$config['captiveportal']['passthrumac'] = array();

passthrumacs_sort();
$a_passthrumacs = &$config['captiveportal']['passthrumac'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_passthrumacs[$id]) {
	$pconfig['mac'] = $a_passthrumacs[$id]['mac'];
	$pconfig['descr'] = $a_passthrumacs[$id]['descr'];
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "mac");
	$reqdfieldsn = explode(",", "MAC address");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	$_POST['mac'] = str_replace("-", ":", $_POST['mac']);
	
	if (($_POST['mac'] && !is_macaddr($_POST['mac']))) {
		$input_errors[] = "必须输入有效的MAC地址。[".$_POST['mac']."]";
	}

	foreach ($a_passthrumacs as $macent) {
		if (isset($id) && ($a_passthrumacs[$id]) && ($a_passthrumacs[$id] === $macent))
			continue;
		
		if ($macent['mac'] == $_POST['mac']){
			$input_errors[] = "[" . $_POST['mac'] . "] 已存在。" ;
			break;
		}	
	}

	if (!$input_errors) {
		$mac = array();
		$mac['mac'] = $_POST['mac'];
		$mac['descr'] = $_POST['descr'];

		if (isset($id) && $a_passthrumacs[$id])
			$a_passthrumacs[$id] = $mac;
		else
			$a_passthrumacs[] = $mac;
		
		write_config();

		touch($d_passthrumacsdirty_path) ;
		
		header("Location: services_captiveportal_mac.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
            <form action="services_captiveportal_mac_edit.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
				<tr>
                  <td width="22%" valign="top" class="vncellreq">MAC地址</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="mac" type="text" class="formfld" id="mac" size="17" value="<?=htmlspecialchars($pconfig['mac']);?>">
                    <br> 
                    <span class="vexpl">MAC地址，格式如：00:21:00:2C:9C:F2</span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncell">描述</td>
                  <td width="78%" class="vtable"> 
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>">
                    <br> <span class="vexpl">描述信息（不含空格）。</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="保存">
                    <?php if (isset($id) && $a_passthrumacs[$id]): ?>
                    <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>">
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<?php include("fend.inc"); ?>
