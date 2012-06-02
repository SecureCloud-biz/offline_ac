#!/usr/local/bin/php
<?php
/*
	$Id: system_routes.php 477 2011-08-17 12:27:34Z mkasper $
	part of wifiAP (http://wifiap.cn)
	
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

require("guiconfig.inc");

if ($ipv6routes = ($_GET['type'] == 'ipv6')) {
	$configname = 'route6';
	$typelink = '&type=ipv6';
} else {
	$configname = 'route';
	$typelink = '';
}
$pgtitle = array("高级服务", "静态路由");	/* make group manager happy */
$pgtitle = array("System", ipv6enabled() ? ($ipv6routes ? 'IPv6 Static routes' : 'IPv4 Static routes') : 'Static routes');

if (!is_array($config['staticroutes'][$configname]))
	$config['staticroutes'][$configname] = array();

staticroutes_sort();
$a_routes = &$config['staticroutes'][$configname];

if ($_POST) {

	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			$retval = system_routing_configure();
			if ($configname == 'rule6') {
				$retval |= filter_configure6();
			} else {
				$retval |= filter_configure(true);
			}
		}
		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			if (file_exists($d_staticroutesdirty_path)) {
				config_lock();
				unlink($d_staticroutesdirty_path);
				config_unlock();
			}
		}
	}
}

if ($_GET['act'] == "del") {
	if ($a_routes[$_GET['id']]) {
		unset($a_routes[$_GET['id']]);
		write_config();
		touch($d_staticroutesdirty_path);
		header("Location: system_routes.php?{$typelink}");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<form action="system_routes.php?<?=$typelink?>" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_staticroutesdirty_path)): ?><p>
<?php print_info_box_np("配置已更改，请点击“应用更改”使配置生效。");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="应用更改"></p>
<?php endif; ?>
              <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
                <tr>
                  <td width="15%" class="listhdrr">接口</td>
                  <td width="25%" class="listhdrr">网络</td>
                  <td width="20%" class="listhdrr">网关</td>
                  <td width="30%" class="listhdr">描述</td>
                  <td width="10%" class="list"></td>
				</tr>
			  <?php $i = 0; foreach ($a_routes as $route): ?>
                <tr>
                  <td class="listlr">
                    <?php
				  $iflabels = array('lan' => 'LAN', 'wan' => 'WAN');
				  if (!$ipv6routes)
					$iflabels['pptp'] = "PPTP";
				  for ($j = 1; isset($config['interfaces']['opt' . $j]); $j++)
				  	$iflabels['opt' . $j] = $config['interfaces']['opt' . $j]['descr'];
				  echo htmlspecialchars($iflabels[$route['interface']]); ?>
                  </td>
                  <td class="listr">
                    <?=strtolower($route['network']);?>
                  </td>
                  <td class="listr">
                    <?=strtolower($route['gateway']);?>
                  </td>
                  <td class="listbg">
                    <?=htmlspecialchars($route['descr']);?>&nbsp;
                  </td>
                  <td valign="middle" nowrap class="list"> <a href="system_routes_edit.php?id=<?=$i;?><?=$typelink?>"><img src="e.gif" title="编辑路由" width="17" height="17" border="0" alt="编辑路由"></a>
                     &nbsp;<a href="system_routes.php?act=del&amp;id=<?=$i;?><?=$typelink?>" onclick="return confirm('你确认要删除此路由吗？')"><img src="x.gif" title="删除路由" width="17" height="17" border="0" alt="删除路由"></a></td>
				</tr>
			  <?php $i++; endforeach; ?>
                <tr> 
                  <td class="list" colspan="4"></td>
                  <td class="list"> <a href="system_routes_edit.php?<?=$typelink?>"><img src="plus.gif" title="添加路由" width="17" height="17" border="0" alt="添加路由"></a></td>
				</tr>
              </table>
            </form>
<?php include("fend.inc"); ?>
