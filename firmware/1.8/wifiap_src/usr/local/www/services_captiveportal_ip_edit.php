#!/usr/local/bin/php
<?php 
/*
	$Id: services_captiveportal_ip_edit.php 411 2010-11-12 12:58:55Z mkasper $
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

$pgtitle = array("高级服务", "WEB认证", "修改IP白名单");
require("guiconfig.inc");

if (!is_array($config['captiveportal']['allowedip']))
	$config['captiveportal']['allowedip'] = array();

allowedips_sort();
$a_allowedips = &$config['captiveportal']['allowedip'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_allowedips[$id]) {
	$pconfig['ip'] = $a_allowedips[$id]['ip'];
	$pconfig['descr'] = $a_allowedips[$id]['descr'];
	$pconfig['dir'] = $a_allowedips[$id]['dir'];
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "ip dir");
	$reqdfieldsn = explode(",", "Allowed IP address,Direction");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	if (($_POST['ip'] && !is_ipaddr($_POST['ip']))) {
		$input_errors[] = "必须输入一个有效的IP地址。[".$_POST['ip']."]";
	}

	foreach ($a_allowedips as $ipent) {
		if (isset($id) && ($a_allowedips[$id]) && ($a_allowedips[$id] === $ipent))
			continue;
		
		if (($ipent['dir'] == $_POST['dir']) && ($ipent['ip'] == $_POST['ip'])){
			$input_errors[] = "[" . $_POST['ip'] . "] 已经存在。" ;
			break ;
		}	
	}

	if (!$input_errors) {
		$ip = array();
		$ip['ip'] = $_POST['ip'];
		$ip['descr'] = $_POST['descr'];
		$ip['dir'] = $_POST['dir'];

		if (isset($id) && $a_allowedips[$id])
			$a_allowedips[$id] = $ip;
		else
			$a_allowedips[] = $ip;
		
		write_config();

		touch($d_allowedipsdirty_path) ;
		
		header("Location: services_captiveportal_ip.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
            <form action="services_captiveportal_ip_edit.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
				<tr>
                  <td width="22%" valign="top" class="vncellreq">方向</td>
                  <td width="78%" class="vtable"> 
					<select name="dir" class="formfld">
					<?php 
					$dirs = explode(" ", "From To") ;
					foreach ($dirs as $dir): ?>
					<option value="<?=strtolower($dir);?>" <?php if (strtolower($dir) == strtolower($pconfig['dir'])) echo "selected";?> >
					<?=htmlspecialchars($dir);?>
					</option>
					<?php endforeach; ?>
					</select>
                    <br> 
                    <span class="vexpl">选择 <em>From</em> 让此IP地址（内网）的机器可以任意访问网络（不需要认证）； <br>
                    选择 <em>To</em> 让所有内网的机器都可以访问此IP地址（未通过认证都可以访问）。</span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncellreq">IP地址</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="ip" type="text" class="formfld" id="ip" size="17" value="<?=htmlspecialchars($pconfig['ip']);?>">
                    <br> 
                    <span class="vexpl">IP地址</span></td>
                </tr>
				<tr>
                  <td width="22%" valign="top" class="vncell">描述</td>
                  <td width="78%" class="vtable"> 
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>">
                    <br> <span class="vexpl">输入描述信息（不含空格）。</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="保存">
                    <?php if (isset($id) && $a_allowedips[$id]): ?>
                    <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>">
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<?php include("fend.inc"); ?>
