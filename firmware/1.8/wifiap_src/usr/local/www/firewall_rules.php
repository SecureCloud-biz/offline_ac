#!/usr/local/bin/php
<?php 
/*
	$Id: firewall_rules.php 477 2011-08-17 12:27:34Z mkasper $
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

if ($ipv6rules = ($_GET['type'] == 'ipv6')) {
	$configname = 'rule6';
	$typelink = '&type=ipv6';
} else {
	$configname = 'rule';
	$typelink = '';
}
$pgtitle = array("防火墙", "规则");	/* make group manager happy */
$pgtitle = array("Firewall", ipv6enabled() ? ($ipv6rules ? 'IPv6 Rules' : 'IPv4 Rules') : 'Rules');

if (!is_array($config['filter'][$configname])) {
	$config['filter'][$configname] = array();
}
filter_rules_sort();
$a_filter = &$config['filter'][$configname];

$if = $_GET['if'];
if ($_POST['if'])
	$if = $_POST['if'];
	
$iflist = array("lan" => "LAN", "wan" => "WAN");

if ($config['pptpd']['mode'] == "server" && !$ipv6rules)
	$iflist['pptp'] = "PPTP VPN";

if (isset($config['ipsec']['enable']))
	$iflist['ipsec'] = "IPsec VPN";

for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
	$iflist['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
}

if (!$if || !isset($iflist[$if]))
	$if = "wan";

if ($_POST) {

	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;
		
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			if ($configname == 'rule6') {
				$retval = filter_configure6();
			} else {
				$retval = filter_configure(true);
			}
			config_unlock();
		}
		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			if (file_exists($d_natconfdirty_path))
				unlink($d_natconfdirty_path);
			if (file_exists($d_filterconfdirty_path))
				unlink($d_filterconfdirty_path);
		}
	}
}

if (isset($_POST['del_x'])) {
	/* delete selected rules */
	if (is_array($_POST['rule']) && count($_POST['rule'])) {
		foreach ($_POST['rule'] as $rulei) {
			unset($a_filter[$rulei]);
		}
		write_config();
		touch($d_filterconfdirty_path);
		header("Location: firewall_rules.php?if={$if}{$typelink}");
		exit;
	}
} else if ($_GET['act'] == "toggle") {
	if ($a_filter[$_GET['id']]) {
		$a_filter[$_GET['id']]['disabled'] = !isset($a_filter[$_GET['id']]['disabled']);
		write_config();
		touch($d_filterconfdirty_path);
		header("Location: firewall_rules.php?if={$if}{$typelink}");
		exit;
	}
} else {
	/* yuck - IE won't send value attributes for image buttons, while Mozilla does - 
	   so we use .x/.y to fine move button clicks instead... */
	unset($movebtn);
	foreach ($_POST as $pn => $pd) {
		if (preg_match("/move_(\d+)_x/", $pn, $matches)) {
			$movebtn = $matches[1];
			break;
		}
	}
	/* move selected rules before this rule */
	if (isset($movebtn) && is_array($_POST['rule']) && count($_POST['rule'])) {
		$a_filter_new = array();
		
		/* copy all rules < $movebtn and not selected */
		for ($i = 0; $i < $movebtn; $i++) {
			if (!in_array($i, $_POST['rule']))
				$a_filter_new[] = $a_filter[$i];
		}
		
		/* copy all selected rules */
		for ($i = 0; $i < count($a_filter); $i++) {
			if ($i == $movebtn)
				continue;
			if (in_array($i, $_POST['rule']))
				$a_filter_new[] = $a_filter[$i];
		}
		
		/* copy $movebtn rule */
		if ($movebtn < count($a_filter))
			$a_filter_new[] = $a_filter[$movebtn];
		
		/* copy all rules > $movebtn and not selected */
		for ($i = $movebtn+1; $i < count($a_filter); $i++) {
			if (!in_array($i, $_POST['rule']))
				$a_filter_new[] = $a_filter[$i];
		}
		
		$a_filter = $a_filter_new;
		write_config();
		touch($d_filterconfdirty_path);
		header("Location: firewall_rules.php?if={$if}{$typelink}");
		exit;
	}
}

?>
<?php include("fbegin.inc"); ?>
<script type="text/javascript">
<!--
function fr_toggle(id) {
	var checkbox = document.getElementById('frc' + id);
	checkbox.checked = !checkbox.checked;
	fr_bgcolor(id);
}
function fr_bgcolor(id) {
	var row = document.getElementById('fr' + id);
	var checkbox = document.getElementById('frc' + id);
	var cells = row.getElementsByTagName("td");
	
	for (i = 2; i <= 6; i++) {
		cells[i].style.backgroundColor = checkbox.checked ? "#FFFFBB" : "#FFFFFF";
	}
	cells[7].style.backgroundColor = checkbox.checked ? "#FFFFBB" : "#D9DEE8";
}
function fr_insline(id, on) {
	var row = document.getElementById('fr' + id);
	var prevrow;
	if (id != 0) {
		prevrow = document.getElementById('fr' + (id-1));
	} else {
		if (<?php if (($if == "wan") && isset($config['interfaces']['wan']['blockpriv'])) echo "true"; else echo "false"; ?>) {
			prevrow = document.getElementById('frrfc1918');
		} else {
			prevrow = document.getElementById('frheader');
		}
	}
	
	var cells = row.getElementsByTagName("td");
	var prevcells = prevrow.getElementsByTagName("td");
	
	for (i = 2; i <= 7; i++) {
		if (on) {
			prevcells[i].style.borderBottom = "3px solid #999999";
			prevcells[i].style.paddingBottom = (id != 0) ? 2 : 3;
		} else {
			prevcells[i].style.borderBottomWidth = "1px";
			prevcells[i].style.paddingBottom = (id != 0) ? 4 : 5;
		}
	}
	
	for (i = 2; i <= 7; i++) {
		if (on) {
			cells[i].style.borderTop = "2px solid #999999";
			cells[i].style.paddingTop = 2;
		} else {
			cells[i].style.borderTopWidth = 0;
			cells[i].style.paddingTop = 4;
		}
	}
}
// -->
</script>
<form action="firewall_rules.php<?=($typelink ? '?' . $typelink : '')?>" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_filterconfdirty_path)): ?><p>
<?php print_info_box_np("防火墙规则设置已改变，<br>您还须按应用钮使之生效。");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="应用更改"></p>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php $i = 0; foreach ($iflist as $ifent => $ifname):
	if ($ifent == $if): ?>
    <li class="tabact"><?=htmlspecialchars($ifname);?></li>
<?php else: ?>
    <li class="<?php if ($i == 0) echo "tabinact1"; else echo "tabinact";?>"><a href="firewall_rules.php?if=<?=$ifent;?><?=$typelink;?>"><?=htmlspecialchars($ifname);?></a></li>
<?php endif; ?>
<?php $i++; endforeach; ?>
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
              <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
                <tr id="frheader">
                  <td width="3%" class="list">&nbsp;</td>
                  <td width="5%" class="list">&nbsp;</td>
                  <td width="10%" class="listhdrr">协议</td>
                  <td width="15%" class="listhdrr">源</td>
                  <td width="10%" class="listhdrr">端口</td>
                  <td width="15%" class="listhdrr">目的</td>
                  <td width="10%" class="listhdrr">端口</td>
                  <td width="22%" class="listhdr">描述</td>
                  <td width="10%" class="list"></td>
				</tr>
<?php if (($if == "wan") && isset($config['interfaces']['wan']['blockpriv'])): ?>
                <tr valign="top" id="frrfc1918">
                  <td class="listt"></td>
                  <td class="listt" align="center"><img src="block.gif" width="11" height="11" border="0" alt=""></td>
                  <td class="listlr" style="background-color: #e0e0e0">*</td>
                  <td class="listr" style="background-color: #e0e0e0">
						<?php if ($ipv6rules): ?>
						Reserved IPv6网络
						<?php else: ?>
						RFC 1918网络
						<?php endif; ?></td>
                  <td class="listr" style="background-color: #e0e0e0">*</td>
                  <td class="listr" style="background-color: #e0e0e0">*</td>
                  <td class="listr" style="background-color: #e0e0e0">*</td>
                  <td class="listbg" style="background-color: #e0e0e0">
						<?php if ($ipv6rules): ?>
						Block reserved networks
						<?php else: ?>
						阻止私有网络
						<?php endif; ?></td>
                  <td valign="middle" nowrap class="list">
				    <table border="0" cellspacing="0" cellpadding="1" summary="rule table">
					<tr>
					  <td><img src="left_d.gif" width="17" height="17" title="将选中规则移至本条前" alt="将选中规则移至本条前"></td>
					  <td><a href="interfaces_wan.php#rfc1918"><img src="e.gif" title="编辑规则" width="17" height="17" border="0" alt="编辑规则"></a></td>
					</tr>
					<tr>
					  <td align="center" valign="middle"></td>
					  <td><img src="plus_d.gif" title="在本条规则基础上新加一条" width="17" height="17" border="0" alt="在本条规则基础上新加一条"></td>
					</tr>
					</table>
				  </td>
				</tr>
<?php endif; ?>
				<?php $nrules = 0; for ($i = 0; isset($a_filter[$i]); $i++):
					$filterent = $a_filter[$i];
					if ($filterent['interface'] != $if)
						continue;
				?>
                <tr valign="top" id="fr<?=$nrules;?>">
                  <td class="listt"><input type="checkbox" id="frc<?=$nrules;?>" name="rule[]" value="<?=$i;?>" onClick="fr_bgcolor('<?=$nrules;?>')" style="margin: 0; padding: 0; width: 15px; height: 15px;"></td>
                  <td class="listt" align="center">
				  <?php if ($filterent['type'] == "block")
				  			$iconfn = "block";
						else if ($filterent['type'] == "reject") {
							if ($filterent['protocol'] == "tcp" || $filterent['protocol'] == "udp")
								$iconfn = "reject";
							else
								$iconfn = "block";
						} else
							$iconfn = "pass";
						if (isset($filterent['disabled'])) {
							$textss = "<span class=\"gray\">";
							$textse = "</span>";
							$iconfn .= "_d";
						} else {
							$textss = $textse = "";
						}
				  ?>
				  <a href="?if=<?=htmlspecialchars($if);?>&act=toggle&id=<?=$i;?><?=$typelink;?>"><img src="<?=$iconfn;?>.gif" width="11" height="11" border="0" title="点击开关工作状态"></a>
				  <?php if (isset($filterent['log'])):
							$iconfn = "log_s";
						if (isset($filterent['disabled']))
							$iconfn .= "_d";
				  	?>
				  <br><img src="<?=$iconfn;?>.gif" width="11" height="15" border="0">
				  <?php endif; ?>
				  </td>
                  <td class="listlr" onClick="fr_toggle(<?=$nrules;?>)"> 
                    <?=$textss;?><?php if (isset($filterent['protocol'])) echo strtoupper($filterent['protocol']); else echo "*"; ?><?=$textse;?>
                  </td>
                  <td class="listr" onClick="fr_toggle(<?=$nrules;?>)">
				    <?=$textss;?><?php echo htmlspecialchars(pprint_address($filterent['source'])); ?><?=$textse;?>
                  </td>
                  <td class="listr" onClick="fr_toggle(<?=$nrules;?>)">
                    <?=$textss;?><?php echo htmlspecialchars(pprint_port($filterent['source']['port'])); ?><?=$textse;?>
                  </td>
                  <td class="listr" onClick="fr_toggle(<?=$nrules;?>)"> 
				    <?=$textss;?><?php echo htmlspecialchars(pprint_address($filterent['destination'])); ?><?=$textse;?>
                  </td>
                  <td class="listr" onClick="fr_toggle(<?=$nrules;?>)"> 
                    <?=$textss;?><?php echo htmlspecialchars(pprint_port($filterent['destination']['port'])); ?><?=$textse;?>
                  </td>
                  <td class="listbg" onClick="fr_toggle(<?=$nrules;?>)"> 
                    <?=$textss;?><?=htmlspecialchars($filterent['descr']);?>&nbsp;<?=$textse;?>
                  </td>
                  <td valign="middle" nowrap class="list">
				    <table border="0" cellspacing="0" cellpadding="1" summary="button pane">
					<tr>
					  <td><input name="move_<?=$i;?>" type="image" src="left.gif" width="17" height="17" title="将选中规则移至本条前" onMouseOver="fr_insline(<?=$nrules;?>, true)" onMouseOut="fr_insline(<?=$nrules;?>, false)"></td>
					  <td><a href="firewall_rules_edit.php?id=<?=$i;?><?=$typelink;?>"><img src="e.gif" title="编辑规则" width="17" height="17" border="0" alt="编辑规则"></a></td>
					</tr>
					<tr>
					  <td align="center" valign="middle"></td>
					  <td><a href="firewall_rules_edit.php?dup=<?=$i;?><?=$typelink;?>"><img src="plus.gif" title="在本条规则基础上新加一条" width="17" height="17" border="0" alt="在本条规则基础上新加一条"></a></td>
					</tr>
					</table>
				  </td>
				</tr>
			  <?php $nrules++; endfor; ?>
			  <?php if ($nrules == 0): ?>
              <td class="listt"></td>
			  <td class="listt"></td>
			  <td class="listlr" colspan="6" align="center" valign="middle">
			  <span class="gray">
			  本接口上尚未定义规则<br>
			  除非您为之添加通过规则，本接口上的所有进入联接都将会被阻止。<br><br>
			  点击 <a href="firewall_rules_edit.php?if=<?=htmlspecialchars($if);?><?=$typelink;?>"><img src="plus.gif" title="添加新规则" border="0" width="17" height="17" align="middle" alt="添加新规则"></a> 来添加新规则。</span>
			  </td>
			  <?php endif; ?>
                <tr id="fr<?=$nrules;?>"> 
                  <td class="list"></td>
                  <td class="list"></td>
                  <td class="list">&nbsp;</td>
                  <td class="list">&nbsp;</td>
                  <td class="list">&nbsp;</td>
                  <td class="list">&nbsp;</td>
                  <td class="list">&nbsp;</td>
                  <td class="list">&nbsp;</td>
                  <td class="list">
				    <table border="0" cellspacing="0" cellpadding="1" summary="button pane">
					<tr>
				      <td>
					  <?php if ($nrules == 0): ?><img src="left_d.gif" width="17" height="17" title="将选中规则移至末尾" border="0" alt="将选中规则移至末尾"><?php else: ?><input name="move_<?=$i;?>" type="image" src="left.gif" width="17" height="17" title="将选中规则移至末尾" onMouseOver="fr_insline(<?=$nrules;?>, true)" onMouseOut="fr_insline(<?=$nrules;?>, false)"><?php endif; ?></td>
					  <td></td>
				    </tr>
					<tr>
					  <td><?php if ($nrules == 0): ?><img src="x_d.gif" width="17" height="17" title="删除选中规则" border="0" alt="删除选中规则"><?php else: ?><input name="del" type="image" src="x.gif" width="17" height="17" title="删除选中规则" alt="删除选中规则" onclick="return confirm('您确认要删除选中的规则吗？')"><?php endif; ?></td>
					  <td><a href="firewall_rules_edit.php?if=<?=htmlspecialchars($if);?><?=$typelink;?>"><img src="plus.gif" title="添加新规则" width="17" height="17" border="0" alt="添加新规则"></a></td>
					</tr>
				    </table>
				  </td>
				</tr>
              </table>
			  <table border="0" cellspacing="0" cellpadding="0" summary="info pane">
                <tr> 
                  <td width="16"><img src="pass.gif" width="11" height="11"></td>
                  <td>通过</td>
                  <td width="14"></td>
                  <td width="16"><img src="block.gif" width="11" height="11"></td>
                  <td>阻止</td>
                  <td width="14"></td>
                  <td width="16"><img src="reject.gif" width="11" height="11"></td>
                  <td>拒绝</td>
                  <td width="14"></td>
                  <td width="16"><img src="log.gif" width="11" height="11"></td>
                  <td>日志</td>
                </tr>
                <tr>
                  <td colspan="5" height="4"></td>
                </tr>
                <tr> 
                  <td><img src="pass_d.gif" width="11" height="11"></td>
                  <td>通过 (关)</td>
                  <td></td>
                  <td><img src="block_d.gif" width="11" height="11"></td>
                  <td>阻止 (关)</td>
                  <td></td>
                  <td><img src="reject_d.gif" width="11" height="11"></td>
                  <td>拒绝 (关)</td>
                  <td></td>
                  <td width="16"><img src="log_d.gif" width="11" height="11"></td>
                  <td>日志 (关)</td>
                </tr>
              </table>
    </td>
  </tr>
</table><br>
  <strong><span class="red">提示：<br>
  </span></strong>规则以顺序定优先（也就是先符合的规则先动作）。 
  这也就要求您在使用阻止动作时必须考虑先后。默认状态下，所有未指明通过的都将被阻止。
  <input type="hidden" name="if" value="<?=htmlspecialchars($if);?>">
</form>
<?php include("fend.inc"); ?>
