#!/usr/local/bin/php
<?php 
/*
	$Id: firewall_nat_1to1_edit.php 411 2010-11-12 12:58:55Z mkasper $
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

$pgtitle = array("防火墙", "NAT", "编辑 1:1");
require("guiconfig.inc");

if (!is_array($config['nat']['onetoone'])) {
	$config['nat']['onetoone'] = array();
}
nat_1to1_rules_sort();
$a_1to1 = &$config['nat']['onetoone'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_1to1[$id]) {
	$pconfig['external'] = $a_1to1[$id]['external'];
	$pconfig['internal'] = $a_1to1[$id]['internal'];
	$pconfig['interface'] = $a_1to1[$id]['interface'];
	if (!$pconfig['interface'])
		$pconfig['interface'] = "wan";
	if (!$a_1to1[$id]['subnet'])
		$pconfig['subnet'] = 32;
	else
		$pconfig['subnet'] = $a_1to1[$id]['subnet'];
	$pconfig['descr'] = $a_1to1[$id]['descr'];
} else {
    $pconfig['subnet'] = 32;
	$pconfig['interface'] = "wan";
}

if ($_POST) {
	
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "interface external internal");
	$reqdfieldsn = explode(",", "Interface,External subnet,Internal subnet");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if (($_POST['external'] && !is_ipaddr($_POST['external']))) {
		$input_errors[] = "须指定一个合法的外部子网。";
	}
	if (($_POST['internal'] && !is_ipaddr($_POST['internal']))) {
		$input_errors[] = "须指定一个合法的内部子网。";
	}
	
	/*  return the subnet address given a host address and a subnet bit count */
	if ($extsubnetip = gen_subnet($_POST['external'], $_POST['subnet'])) {
		$_POST['external'] = $extsubnetip;
	} else {
		$input_errors[] = "未能由所给的外部IP地址和掩码位得出合法的子网地址。";
	}

	if ($intsubnetip = gen_subnet($_POST['internal'], $_POST['subnet'])) {
		$_POST['internal'] = $intsubnetip;
	} else {
		$input_errors[] = "未能由所给的内部IP地址和掩码位得出合法的子网地址。";
	}

	if (is_ipaddr($config['interfaces']['wan']['ipaddr'])) {
		if (check_subnets_overlap($_POST['external'], $_POST['subnet'], 
				$config['interfaces']['wan']['ipaddr'], 32))
			$input_errors[] = "WAN IP 不能用于1:1 映射规则。";
	}
	

	/* check for overlaps with other 1:1 */
	foreach ($a_1to1 as $natent) {
		if (isset($id) && ($a_1to1[$id]) && ($a_1to1[$id] === $natent))
			continue;
		
		if (check_subnets_overlap($_POST['external'], $_POST['subnet'], $natent['external'], $natent['subnet'])) {
			$input_errors[] = "其它的 1:1 映射规则与指定的外部子网有重叠。";
			break;
		} else if (check_subnets_overlap($_POST['internal'], $_POST['subnet'], $natent['internal'], $natent['subnet'])) {
			$input_errors[] = "其它的 1:1 映射规则与指定的内部子网有重叠。";
			break;
		}
	}
	
	/* check for overlaps with server NAT */
	if (is_array($config['nat']['servernat'])) {
		foreach ($config['nat']['servernat'] as $natent) {
			if (check_subnets_overlap($_POST['external'], $_POST['subnet'],
				$natent['ipaddr'], 32)) {
				$input_errors[] = "有服务器 NAT 规则与所指定的外部子网有重叠。";
				break;
			}
		}
	}
	
	/* check for overlaps with advanced outbound NAT */
	if (is_array($config['nat']['advancedoutbound']['rule'])) {
		foreach ($config['nat']['advancedoutbound']['rule'] as $natent) {
			if ($natent['target'] && 
				check_subnets_overlap($_POST['external'], $_POST['subnet'], $natent['target'], 32)) {
				$input_errors[] = "有高级转出 NAT 规则与所指定的外部子网有重叠。";
				break;
			}
		}
	}

	if (!$input_errors) {
		$natent = array();
		$natent['external'] = $_POST['external'];
		$natent['internal'] = $_POST['internal'];
		$natent['subnet'] = $_POST['subnet'];
		$natent['descr'] = $_POST['descr'];
		$natent['interface'] = $_POST['interface'];
		
		if (isset($id) && $a_1to1[$id])
			$a_1to1[$id] = $natent;
		else
			$a_1to1[] = $natent;
		
		touch($d_natconfdirty_path);

		if ($_POST['autoaddproxy']) {
			/* auto-generate a matching proxy arp entry */
			$arpent = array();           
			$arpent['interface'] = $_POST['interface'];
			$arpent['network'] = $_POST['external'] . "/" . $_POST['subnet'];
			$arpent['descr'] = "NAT " . $_POST['descr'];
			
			$config['proxyarp']['proxyarpnet'][] = $arpent;
			
			touch($d_proxyarpdirty_path);
		}
		
		write_config();
		
		header("Location: firewall_nat_1to1.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
            <form action="firewall_nat_1to1_edit.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
				<tr>
				  <td width="22%" valign="top" class="vncellreq">接口</td>
				  <td width="78%" class="vtable">
					<select name="interface" class="formfld">
						<?php
						$interfaces = array('wan' => 'WAN');
						for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
							$interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
						}
						foreach ($interfaces as $iface => $ifacename): ?>
						<option value="<?=$iface;?>" <?php if ($iface == $pconfig['interface']) echo "selected"; ?>>
						<?=htmlspecialchars($ifacename);?>
						</option>
						<?php endforeach; ?>
					</select><br>
				  <span class="vexpl">选择本规则将应用的网络接口。<br>
				  提示：在大多数情况下，这里选 WAN 。</span></td>
				</tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">外部子网</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="external" type="text" class="formfld" id="external" size="20" value="<?=htmlspecialchars($pconfig['external']);?>">
                    / 
                    <select name="subnet" class="formfld" id="subnet">
                      <?php for ($i = 32; $i >= 0; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['subnet']) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select>
                    <br>
                    <span class="vexpl">输入用于1:1映射的外部（WAN）子网。 若您只想对一个IP地址作此映射，只需要指定子网掩码为/32 。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">内部子网</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="internal" type="text" class="formfld" id="internal" size="20" value="<?=htmlspecialchars($pconfig['internal']);?>"> 
                    <br>
                     <span class="vexpl">输入用于1:1映射的外部（LAN）子网。子网的大小由前面外部子网设定确定，两者须相同。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">描述</td>
                  <td width="78%" class="vtable"> 
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>"> 
                    <br> <span class="vexpl">您可在此输入些描述信息以备日后参考（不会被解析）。</span></td>
                </tr><?php if (!(isset($id) && $a_1to1[$id])): ?>
		<tr> 
		  <td width="22%" valign="top">&nbsp;</td>
		  <td width="78%"> 
		    <input name="autoaddproxy" type="checkbox" id="autoaddproxy" value="yes" checked="checked">
		    <strong>自动为本接口添加一条<a href="services_proxyarp.php">ARP代理</a>规则。
		    </strong></td>
		</tr><?php endif; ?>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="保存"> 
                    <?php if (isset($id) && $a_1to1[$id]): ?>
                    <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>"> 
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<?php include("fend.inc"); ?>
