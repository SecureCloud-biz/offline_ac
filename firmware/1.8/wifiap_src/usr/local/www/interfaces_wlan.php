#!/usr/local/bin/php
<?php 
/*
	$Id: interfaces_wlan.php 446 2011-05-01 10:07:17Z mkasper $
	part of wifiAP (http://wifiap.cn)
	
	Copyright (C) 2011 Manuel Kasper <mk@neon1.net>.
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

$pgtitle = array("基本设置", "无线WLAN");
require("guiconfig.inc");

if (!is_array($config['wlans']['wlan']))
	$config['wlans']['wlan'] = array();

$a_wlans = &$config['wlans']['wlan'] ;

function wlan_inuse($num) {
	global $config, $g;

	if ($config['interfaces']['lan']['if'] == "wlan{$num}")
		return true;
	if ($config['interfaces']['wan']['if'] == "wlan{$num}")
		return true;
	
	for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
		if ($config['interfaces']['opt' . $i]['if'] == "wlan{$num}")
			return true;
	}
	
	return false;
}

function renumber_wlan($if, $delwlan) {
	if (!preg_match("/^wlan/", $if))
		return $if;
	
	$wlan = substr($if, 4);
	if ($wlan > $delwlan)
		return "wlan" . ($wlan - 1);
	else
		return $if;
}

if ($_GET['act'] == "del") {
	/* check if still in use */
	if (wlan_inuse($_GET['id'])) {
		$input_errors[] = "此WLAN不能删除（作为接口被使用中...）。";
	} else {
		unset($a_wlans[$_GET['id']]);
		
		/* renumber all interfaces that use WLANs */
		$config['interfaces']['lan']['if'] = renumber_wlan($config['interfaces']['lan']['if'], $_GET['id']);
		$config['interfaces']['wan']['if'] = renumber_wlan($config['interfaces']['wan']['if'], $_GET['id']);
		for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++)
			$config['interfaces']['opt' . $i]['if'] = renumber_wlan($config['interfaces']['opt' . $i]['if'], $_GET['id']);
		
		write_config();
		touch($d_sysrebootreqd_path);
		header("Location: interfaces_wlan.php");
		exit;
	}
}

?>
<?php include("fbegin.inc"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if (file_exists($d_sysrebootreqd_path)) print_info_box(get_std_save_message(0)); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
    <li class="tabinact1"><a href="interfaces_assign.php">接口分配</a></li>
    <li class="tabinact"><a href="interfaces_vlan.php">VLANs</a></li>
    <li class="tabact">WLANs</li>
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
                <tr>
                  <td width="20%" class="listhdrr">接口</td>
                  <td width="20%" class="listhdrr">SSID</td>
                  <td width="50%" class="listhdr">描述</td>
                  <td width="10%" class="list"></td>
				</tr>
			  <?php $i = 0; foreach ($a_wlans as $wlan): ?>
                <tr>
                  <td class="listlr">
					<?=htmlspecialchars($wlan['if']);?>
                  </td>
                  <td class="listr">
					<?=htmlspecialchars($wlan['ssid']);?>
                  </td>
                  <td class="listbg">
                    <?=htmlspecialchars($wlan['descr']);?>&nbsp;
                  </td>
                  <td valign="middle" nowrap class="list"> <a href="interfaces_wlan_edit.php?id=<?=$i;?>"><img src="e.gif" title="修改WLAN" width="17" height="17" border="0" alt="修改WLAN"></a>
                     &nbsp;<a href="interfaces_wlan.php?act=del&amp;id=<?=$i;?>" onclick="return confirm('你确认删除此WLAN ？')"><img src="x.gif" title="删除WLAN" width="17" height="17" border="0" alt="删除WLAN"></a></td>
				</tr>
			  <?php $i++; endforeach; ?>
                <tr> 
                  <td class="list" colspan="3">&nbsp;</td>
                  <td class="list"> <a href="interfaces_wlan_edit.php"><img src="plus.gif" title="增加WLAN" width="17" height="17" border="0" alt="增加WLAN"></a></td>
				</tr>
              </table>
			  </td>
	</tr>
</table>
<?php include("fend.inc"); ?>
