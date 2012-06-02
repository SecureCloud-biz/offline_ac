#!/usr/local/bin/php
<?php 
/*
	$Id: firewall_nat_server_edit.php 411 2010-11-12 12:58:55Z mkasper $
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

$pgtitle = array("防火墙", "NAT", "编辑服务器 NAT");
require("guiconfig.inc");

if (!is_array($config['nat']['servernat'])) {
	$config['nat']['servernat'] = array();
}
nat_server_rules_sort();
$a_snat = &$config['nat']['servernat'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_snat[$id]) {
	$pconfig['ipaddr'] = $a_snat[$id]['ipaddr'];
	$pconfig['descr'] = $a_snat[$id]['descr'];
}

if ($_POST) {
	
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "ipaddr");
	$reqdfieldsn = explode(",", "External IP address");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if (($_POST['ipaddr'] && !is_ipaddr($_POST['ipaddr']))) {
		$input_errors[] = "需输入一个合法的外部 IP 地址。";
	}
	
	if ($_POST['ipaddr'] == $config['interfaces']['wan']['ipaddr'])
		$input_errors[] = "在服务器NAT记录中不能使用 WAN IP 地址。";
	
	/* check for overlaps with other server NAT */
	foreach ($a_snat as $natent) {
		if (isset($id) && ($a_snat[$id]) && ($a_snat[$id] === $natent))
			continue;
		
		if ($_POST['ipaddr'] == $natent['ipaddr']) {
			$input_errors[] = "您输入的外部IP已有一条服务器NAT记录。";
			break;
		}
	}
	
	/* check for overlaps with 1:1 NAT */
	if (is_array($config['nat']['onetoone'])) {
		foreach ($config['nat']['onetoone'] as $natent) {
			if (check_subnets_overlap($_POST['ipaddr'], 32, $natent['external'], $natent['subnet'])) {
				$input_errors[] = "已有一条 1:1 NAT 映射与您输入的外部IP重叠。";
				break;
			}
		}
	}

	if (!$input_errors) {
		$natent = array();
		$natent['ipaddr'] = $_POST['ipaddr'];
		$natent['descr'] = $_POST['descr'];
		
		if (isset($id) && $a_snat[$id]) {
			/* modify all inbound NAT rules with this address */
			for ($i = 0; isset($config['nat']['rule'][$i]); $i++) {
				if ($config['nat']['rule'][$i]['external-address'] == $a_snat[$id]['ipaddr'])
					$config['nat']['rule'][$i]['external-address'] = $natent['ipaddr'];
			}
			$a_snat[$id] = $natent;
		} else
			$a_snat[] = $natent;
		
		touch($d_natconfdirty_path);
		
		write_config();
		
		header("Location: firewall_nat_server.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
            <form action="firewall_nat_server_edit.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0">
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">外部 IP 地址</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="ipaddr" type="text" class="formfld" id="ipaddr" size="20" value="<?=htmlspecialchars($pconfig['ipaddr']);?>">
                    </td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">描述</td>
                  <td width="78%" class="vtable"> 
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>"> 
                    <br> <span class="vexpl">您可在此输入些描述信息以备日后参考（不会被解析）。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="保存"> 
                    <?php if (isset($id) && $a_snat[$id]): ?>
                    <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>"> 
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<?php include("fend.inc"); ?>
