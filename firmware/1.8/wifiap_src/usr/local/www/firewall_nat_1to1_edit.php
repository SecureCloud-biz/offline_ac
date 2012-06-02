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

$pgtitle = array("����ǽ", "NAT", "�༭ 1:1");
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
		$input_errors[] = "��ָ��һ���Ϸ����ⲿ������";
	}
	if (($_POST['internal'] && !is_ipaddr($_POST['internal']))) {
		$input_errors[] = "��ָ��һ���Ϸ����ڲ�������";
	}
	
	/*  return the subnet address given a host address and a subnet bit count */
	if ($extsubnetip = gen_subnet($_POST['external'], $_POST['subnet'])) {
		$_POST['external'] = $extsubnetip;
	} else {
		$input_errors[] = "δ�����������ⲿIP��ַ������λ�ó��Ϸ���������ַ��";
	}

	if ($intsubnetip = gen_subnet($_POST['internal'], $_POST['subnet'])) {
		$_POST['internal'] = $intsubnetip;
	} else {
		$input_errors[] = "δ�����������ڲ�IP��ַ������λ�ó��Ϸ���������ַ��";
	}

	if (is_ipaddr($config['interfaces']['wan']['ipaddr'])) {
		if (check_subnets_overlap($_POST['external'], $_POST['subnet'], 
				$config['interfaces']['wan']['ipaddr'], 32))
			$input_errors[] = "WAN IP ��������1:1 ӳ�����";
	}
	

	/* check for overlaps with other 1:1 */
	foreach ($a_1to1 as $natent) {
		if (isset($id) && ($a_1to1[$id]) && ($a_1to1[$id] === $natent))
			continue;
		
		if (check_subnets_overlap($_POST['external'], $_POST['subnet'], $natent['external'], $natent['subnet'])) {
			$input_errors[] = "������ 1:1 ӳ�������ָ�����ⲿ�������ص���";
			break;
		} else if (check_subnets_overlap($_POST['internal'], $_POST['subnet'], $natent['internal'], $natent['subnet'])) {
			$input_errors[] = "������ 1:1 ӳ�������ָ�����ڲ��������ص���";
			break;
		}
	}
	
	/* check for overlaps with server NAT */
	if (is_array($config['nat']['servernat'])) {
		foreach ($config['nat']['servernat'] as $natent) {
			if (check_subnets_overlap($_POST['external'], $_POST['subnet'],
				$natent['ipaddr'], 32)) {
				$input_errors[] = "�з����� NAT ��������ָ�����ⲿ�������ص���";
				break;
			}
		}
	}
	
	/* check for overlaps with advanced outbound NAT */
	if (is_array($config['nat']['advancedoutbound']['rule'])) {
		foreach ($config['nat']['advancedoutbound']['rule'] as $natent) {
			if ($natent['target'] && 
				check_subnets_overlap($_POST['external'], $_POST['subnet'], $natent['target'], 32)) {
				$input_errors[] = "�и߼�ת�� NAT ��������ָ�����ⲿ�������ص���";
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
				  <td width="22%" valign="top" class="vncellreq">�ӿ�</td>
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
				  <span class="vexpl">ѡ�񱾹���Ӧ�õ�����ӿڡ�<br>
				  ��ʾ���ڴ��������£�����ѡ WAN ��</span></td>
				</tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">�ⲿ����</td>
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
                    <span class="vexpl">��������1:1ӳ����ⲿ��WAN�������� ����ֻ���һ��IP��ַ����ӳ�䣬ֻ��Ҫָ����������Ϊ/32 ��</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">�ڲ�����</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="internal" type="text" class="formfld" id="internal" size="20" value="<?=htmlspecialchars($pconfig['internal']);?>"> 
                    <br>
                     <span class="vexpl">��������1:1ӳ����ⲿ��LAN�������������Ĵ�С��ǰ���ⲿ�����趨ȷ������������ͬ��</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">����</td>
                  <td width="78%" class="vtable"> 
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>"> 
                    <br> <span class="vexpl">�����ڴ�����Щ������Ϣ�Ա��պ�ο������ᱻ��������</span></td>
                </tr><?php if (!(isset($id) && $a_1to1[$id])): ?>
		<tr> 
		  <td width="22%" valign="top">&nbsp;</td>
		  <td width="78%"> 
		    <input name="autoaddproxy" type="checkbox" id="autoaddproxy" value="yes" checked="checked">
		    <strong>�Զ�Ϊ���ӿ����һ��<a href="services_proxyarp.php">ARP����</a>����
		    </strong></td>
		</tr><?php endif; ?>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="����"> 
                    <?php if (isset($id) && $a_1to1[$id]): ?>
                    <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>"> 
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<?php include("fend.inc"); ?>
