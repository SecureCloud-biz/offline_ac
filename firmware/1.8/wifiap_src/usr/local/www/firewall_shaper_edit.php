#!/usr/local/bin/php
<?php 
/*
	$Id: firewall_shaper_edit.php 503 2012-04-06 16:16:09Z lgrahl $
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

$pgtitle = array("����ǽ", "��������", "�༭����");
require("guiconfig.inc");

if (!is_array($config['shaper']['rule'])) {
	$config['shaper']['rule'] = array();
}
$a_shaper = &$config['shaper']['rule'];

$specialsrcdst = explode(" ", "any lan pptp");

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];
	
$after = $_GET['after'];
if (isset($_POST['after']))
	$after = $_POST['after'];
	
if (isset($_GET['dup'])) {
	$id = $_GET['dup'];
	$after = $_GET['dup'];
}
	
function is_specialnet($net) {
	global $specialsrcdst;
	
	if (in_array($net, $specialsrcdst) || strstr($net, "opt"))
		return true;
	else
		return false;
}
	
function address_to_pconfig($adr, &$padr, &$pmask, &$pnot, &$pbeginport, &$pendport) {
		
	if (isset($adr['any']))
		$padr = "any";
	else if ($adr['network'])
		$padr = $adr['network'];
	else if ($adr['address']) {
		list($padr, $pmask) = explode("/", $adr['address']);
		if (!$pmask)
			$pmask = 32;
	}
	
	if (isset($adr['not']))
		$pnot = 1;
	else
		$pnot = 0;
	
	if ($adr['port']) {
		list($pbeginport, $pendport) = explode("-", $adr['port']);
		if (!$pendport)
			$pendport = $pbeginport;
	} else {
		$pbeginport = "any";
		$pendport = "any";
	}
}

function pconfig_to_address(&$adr, $padr, $pmask, $pnot, $pbeginport, $pendport) {
	
	$adr = array();
	
	if ($padr == "any")
		$adr['any'] = true;
	else if (is_specialnet($padr))
		$adr['network'] = $padr;
	else {
		$adr['address'] = $padr;
		if ($pmask != 32)
			$adr['address'] .= "/" . $pmask;
	}
	
	$adr['not'] = $pnot ? true : false;
	
	if (($pbeginport != 0) && ($pbeginport != "any")) {
		if ($pbeginport != $pendport)
			$adr['port'] = $pbeginport . "-" . $pendport;
		else
			$adr['port'] = $pbeginport;
	}
}

if (isset($id) && $a_shaper[$id]) {
	$pconfig['interface'] = $a_shaper[$id]['interface'];
	
	if (isset($a_shaper[$id]['protocol']))
		$pconfig['proto'] = $a_shaper[$id]['protocol'];
	else
		$pconfig['proto'] = "any";
	
	address_to_pconfig($a_shaper[$id]['source'], $pconfig['src'],
		$pconfig['srcmask'], $pconfig['srcnot'],
		$pconfig['srcbeginport'], $pconfig['srcendport']);
		
	address_to_pconfig($a_shaper[$id]['destination'], $pconfig['dst'],
		$pconfig['dstmask'], $pconfig['dstnot'],
		$pconfig['dstbeginport'], $pconfig['dstendport']);
	
	if (isset($a_shaper[$id]['targetpipe'])) {
		$pconfig['target'] = "targetpipe:" . $a_shaper[$id]['targetpipe'];
	} else if (isset($a_shaper[$id]['targetqueue'])) {
		$pconfig['target'] = "targetqueue:" . $a_shaper[$id]['targetqueue'];
	}
	
	$pconfig['direction'] = $a_shaper[$id]['direction'];
	$pconfig['iptos'] = $a_shaper[$id]['iptos'];
	$pconfig['iplen'] = $a_shaper[$id]['iplen'];
	$pconfig['tcpflags'] = $a_shaper[$id]['tcpflags'];
	$pconfig['descr'] = $a_shaper[$id]['descr'];
	$pconfig['disabled'] = isset($a_shaper[$id]['disabled']);
	
	if ($pconfig['srcbeginport'] == 0) {
		$pconfig['srcbeginport'] = "any";
		$pconfig['srcendport'] = "any";
	}
	if ($pconfig['dstbeginport'] == 0) {
		$pconfig['dstbeginport'] = "any";
		$pconfig['dstendport'] = "any";
	}
	
} else {
	/* defaults */
	$pconfig['src'] = "any";
	$pconfig['dst'] = "any";
}

if (isset($_GET['dup']))
	unset($id);

if ($_POST) {

	if (($_POST['proto'] != "tcp") && ($_POST['proto'] != "udp") && ($_POST['proto'] != "any")) {
		$_POST['srcbeginport'] = 0;
		$_POST['srcendport'] = 0;
		$_POST['dstbeginport'] = 0;
		$_POST['dstendport'] = 0;
	} else {
	
		if ($_POST['srcbeginport_cust'] && !$_POST['srcbeginport'])
			$_POST['srcbeginport'] = $_POST['srcbeginport_cust'];
		if ($_POST['srcendport_cust'] && !$_POST['srcendport'])
			$_POST['srcendport'] = $_POST['srcendport_cust'];
	
		if ($_POST['srcbeginport'] == "any") {
			$_POST['srcbeginport'] = 0;
			$_POST['srcendport'] = 0;
		} else {			
			if (!$_POST['srcendport'])
				$_POST['srcendport'] = $_POST['srcbeginport'];
		}
		if ($_POST['srcendport'] == "any")
			$_POST['srcendport'] = $_POST['srcbeginport'];
		
		if ($_POST['dstbeginport_cust'] && !$_POST['dstbeginport'])
			$_POST['dstbeginport'] = $_POST['dstbeginport_cust'];
		if ($_POST['dstendport_cust'] && !$_POST['dstendport'])
			$_POST['dstendport'] = $_POST['dstendport_cust'];
		
		if ($_POST['dstbeginport'] == "any") {
			$_POST['dstbeginport'] = 0;
			$_POST['dstendport'] = 0;
		} else {			
			if (!$_POST['dstendport'])
				$_POST['dstendport'] = $_POST['dstbeginport'];
		}
		if ($_POST['dstendport'] == "any")
			$_POST['dstendport'] = $_POST['dstbeginport'];		
	}
		
	if (is_specialnet($_POST['srctype'])) {
		$_POST['src'] = $_POST['srctype'];
		$_POST['srcmask'] = 0;
	} else if ($_POST['srctype'] == "single") {
		$_POST['srcmask'] = 32;
	}
	if (is_specialnet($_POST['dsttype'])) {
		$_POST['dst'] = $_POST['dsttype'];
		$_POST['dstmask'] = 0;
	}  else if ($_POST['dsttype'] == "single") {
		$_POST['dstmask'] = 32;
	}
	
	$intos = array();
	foreach ($iptos as $tos) {
		if ($_POST['iptos_' . $tos] == "on")
			$intos[] = $tos;
		else if ($_POST['iptos_' . $tos] == "off")
			$intos[] = "!" . $tos;
	}
	$_POST['iptos'] = join(",", $intos);
	
	$intcpflags = array();
	foreach ($tcpflags as $tcpflag) {
		if ($_POST['tcpflags_' . $tcpflag] == "on")
			$intcpflags[] = $tcpflag;
		else if ($_POST['tcpflags_' . $tcpflag] == "off")
			$intcpflags[] = "!" . $tcpflag;
	}
	$_POST['tcpflags'] = join(",", $intcpflags);
	
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "target proto src dst");
	$reqdfieldsn = explode(",", "Target,Protocol,Source,Destination");
	
	if (!(is_specialnet($_POST['srctype']) || ($_POST['srctype'] == "single"))) {
		$reqdfields[] = "srcmask";
		$reqdfieldsn[] = "Source bit count";
	}
	if (!(is_specialnet($_POST['dsttype']) || ($_POST['dsttype'] == "single"))) {
		$reqdfields[] = "dstmask";
		$reqdfieldsn[] = "Destination bit count";
	}
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	if (!$_POST['srcbeginport']) {
		$_POST['srcbeginport'] = 0;
		$_POST['srcendport'] = 0;
	}
	if (!$_POST['dstbeginport']) {
		$_POST['dstbeginport'] = 0;
		$_POST['dstendport'] = 0;
	}
	
	if (($_POST['srcbeginport'] && !is_port($_POST['srcbeginport']))) {
		$input_errors[] = "��ʼԴ�˿���Ϊ����1-65535��������";
	}
	if (($_POST['srcendport'] && !is_port($_POST['srcendport']))) {
		$input_errors[] = "��ֹԴ�˿���Ϊ����1-65535��������";
	}
	if (($_POST['dstbeginport'] && !is_port($_POST['dstbeginport']))) {
		$input_errors[] = "��ʼĿ�Ķ˿���Ϊ����1-65535��������";
	}
	if (($_POST['dstendport'] && !is_port($_POST['dstendport']))) {
		$input_errors[] = "��ֹĿ�Ķ˿���Ϊ����1-65535��������";
	}
	
	if (!is_specialnet($_POST['srctype'])) {
		if (($_POST['src'] && !is_ipaddroranyalias($_POST['src']))) {
			$input_errors[] = "������һ���Ϸ���ԴIP��ַ�������";
		}
		if (($_POST['srcmask'] && !is_numericint($_POST['srcmask']))) {
			$input_errors[] = "������һ���Ϸ���Դ��ַ���롣";
		}
	}
	if (!is_specialnet($_POST['dsttype'])) {
		if (($_POST['dst'] && !is_ipaddroranyalias($_POST['dst']))) {
			$input_errors[] = "������һ���Ϸ���Ŀ��IP��ַ�������";
		}
		if (($_POST['dstmask'] && !is_numericint($_POST['dstmask']))) {
			$input_errors[] = "������һ���Ϸ���Ŀ�ĵ�ַ���롣";
		}
	}
	
	if ($_POST['srcbeginport'] > $_POST['srcendport']) {
		/* swap */
		$tmp = $_POST['srcendport'];
		$_POST['srcendport'] = $_POST['srcbeginport'];
		$_POST['srcbeginport'] = $tmp;
	}
	if ($_POST['dstbeginport'] > $_POST['dstendport']) {
		/* swap */
		$tmp = $_POST['dstendport'];
		$_POST['dstendport'] = $_POST['dstbeginport'];
		$_POST['dstbeginport'] = $tmp;
	}
	
	if (($_POST['iplen'] && !preg_match("/^(\d+)(-(\d+))?$/", $_POST['iplen']))) {
		$input_errors[] = "IP���ݰ��ĳ�����Ϊ��������һ����Χ����-ֹ��";
	}

	if (!$input_errors) {
		$shaperent = array();
		$shaperent['interface'] = $_POST['interface'];
		
		if ($_POST['proto'] != "any")
			$shaperent['protocol'] = $_POST['proto'];
		else
			unset($shaperent['protocol']);
		
		pconfig_to_address($shaperent['source'], $_POST['src'],
			$_POST['srcmask'], $_POST['srcnot'],
			$_POST['srcbeginport'], $_POST['srcendport']);
			
		pconfig_to_address($shaperent['destination'], $_POST['dst'],
			$_POST['dstmask'], $_POST['dstnot'],
			$_POST['dstbeginport'], $_POST['dstendport']);
		
		$shaperent['direction'] = $_POST['direction'];
		$shaperent['iplen'] = $_POST['iplen'];
		$shaperent['iptos'] = $_POST['iptos'];
		$shaperent['tcpflags'] = $_POST['tcpflags'];
		$shaperent['descr'] = $_POST['descr'];
		$shaperent['disabled'] = $_POST['disabled'] ? true : false;
		
		list($targettype,$target) = explode(":", $_POST['target']);
		$shaperent[$targettype] = $target;
		
		if (isset($id) && $a_shaper[$id]) {
			// Scheduler: update matching jobs
			croen_update_job(Array('shaper-enable_rule', 'shaper-disable_rule'), $a_shaper[$id]['descr'], ($shaperent['descr'] != '' ? $shaperent['descr'] : FALSE));
			$a_shaper[$id] = $shaperent;
		} else {
			if (is_numeric($after))
				array_splice($a_shaper, $after+1, 0, array($shaperent));
			else
				$a_shaper[] = $shaperent;
		}
		
		write_config();
		touch($d_shaperconfdirty_path);
		header("Location: firewall_shaper.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<script type="text/javascript">
<!--
var portsenabled = 1;

function ext_change() {
	if ((document.iform.srcbeginport.selectedIndex == 0) && portsenabled) {
		document.iform.srcbeginport_cust.disabled = 0;
	} else {
		document.iform.srcbeginport_cust.value = "";
		document.iform.srcbeginport_cust.disabled = 1;
	}
	if ((document.iform.srcendport.selectedIndex == 0) && portsenabled) {
		document.iform.srcendport_cust.disabled = 0;
	} else {
		document.iform.srcendport_cust.value = "";
		document.iform.srcendport_cust.disabled = 1;
	}
	if ((document.iform.dstbeginport.selectedIndex == 0) && portsenabled) {
		document.iform.dstbeginport_cust.disabled = 0;
	} else {
		document.iform.dstbeginport_cust.value = "";
		document.iform.dstbeginport_cust.disabled = 1;
	}
	if ((document.iform.dstendport.selectedIndex == 0) && portsenabled) {
		document.iform.dstendport_cust.disabled = 0;
	} else {
		document.iform.dstendport_cust.value = "";
		document.iform.dstendport_cust.disabled = 1;
	}
	
	if (!portsenabled) {
		document.iform.srcbeginport.disabled = 1;
		document.iform.srcendport.disabled = 1;
		document.iform.dstbeginport.disabled = 1;
		document.iform.dstendport.disabled = 1;
	} else {
		document.iform.srcbeginport.disabled = 0;
		document.iform.srcendport.disabled = 0;
		document.iform.dstbeginport.disabled = 0;
		document.iform.dstendport.disabled = 0;
	}
}

function typesel_change() {
	switch (document.iform.srctype.selectedIndex) {
		case 1:	/* single */
			document.iform.src.disabled = 0;
			document.iform.srcmask.value = "";
			document.iform.srcmask.disabled = 1;
			break;
		case 2:	/* network */
			document.iform.src.disabled = 0;
			document.iform.srcmask.disabled = 0;
			break;
		default:
			document.iform.src.value = "";
			document.iform.src.disabled = 1;
			document.iform.srcmask.value = "";
			document.iform.srcmask.disabled = 1;
			break;
	}
	switch (document.iform.dsttype.selectedIndex) {
		case 1:	/* single */
			document.iform.dst.disabled = 0;
			document.iform.dstmask.value = "";
			document.iform.dstmask.disabled = 1;
			break;
		case 2:	/* network */
			document.iform.dst.disabled = 0;
			document.iform.dstmask.disabled = 0;
			break;
		default:
			document.iform.dst.value = "";
			document.iform.dst.disabled = 1;
			document.iform.dstmask.value = "";
			document.iform.dstmask.disabled = 1;
			break;
	}
}

function proto_change() {
	if (document.iform.proto.selectedIndex < 2 || document.iform.proto.selectedIndex == 8) {
		portsenabled = 1;
	} else {
		portsenabled = 0;
	}
	
	ext_change();
}

function src_rep_change() {
	document.iform.srcendport.selectedIndex = document.iform.srcbeginport.selectedIndex;
}
function dst_rep_change() {
	document.iform.dstendport.selectedIndex = document.iform.dstbeginport.selectedIndex;
}
//-->
</script>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if (is_array($config['shaper']['pipe']) && (count($config['shaper']['pipe']) > 0)): ?>
            <form action="firewall_shaper_edit.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
                <tr> 
                  <td valign="top" class="vncellreq">Ŀ��</td>
                  <td class="vtable"><select name="target" class="formfld">
                      <?php 
	       if (is_array($config['shaper']['pipe']) && count($config['shaper']['pipe']) > 0):
					  foreach ($config['shaper']['pipe'] as $pipei => $pipe): ?>
                      <option value="<?="targetpipe:$pipei";?>" <?php if ("targetpipe:$pipei" == $pconfig['target']) echo "selected"; ?>> 
                      <?php
					  	echo htmlspecialchars("Pipe " . ($pipei + 1));
						if ($pipe['descr'])
							echo htmlspecialchars(" (" . $pipe['descr'] . ")");
					  ?>
                      </option>
                      <?php endforeach; endif;
               if (is_array($config['shaper']['queue']) && count($config['shaper']['queue']) > 0):
					  foreach ($config['shaper']['queue'] as $queuei => $queue): ?>
                      <option value="<?="targetqueue:$queuei";?>" <?php if ("targetqueue:$queuei" == $pconfig['target']) echo "selected"; ?>> 
                      <?php
					  	echo htmlspecialchars("Queue " . ($queuei + 1));
						if ($queue['descr'])
							echo htmlspecialchars(" (" . $queue['descr'] . ")");
					  ?>
                      </option>
                      <?php endforeach; endif; ?>
                    </select> <br>
                    <span class="vexpl">ѡ��һ���ܵ�����й����Ϲ�������ݰ�ͨ����
                    </span></td>
                </tr>
                <tr>
                  <td valign="top" class="vncellreq">�ر�</td>
                  <td class="vtable">
                    <input name="disabled" type="checkbox" id="disabled" value="yes" <?php if ($pconfig['disabled']) echo "checked"; ?>>
                    <strong>�رձ�������</strong><br>
                    <span class="vexpl">�������ڹرձ������򣬶������б���ɾ����</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">�ӿ�</td>
                  <td width="78%" class="vtable"><select name="interface" class="formfld">
                      <?php $interfaces = array('lan' => 'LAN', 'wan' => 'WAN', 'pptp' => 'PPTP');
					  for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
					  	$interfaces['opt' . $i] = $config['interfaces']['opt' . $i]['descr'];
					  }
					  foreach ($interfaces as $iface => $ifacename): ?>
                      <option value="<?=$iface;?>" <?php if ($iface == $pconfig['interface']) echo "selected"; ?>> 
                      <?=htmlspecialchars($ifacename);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br>
                    <span class="vexpl">ѡ�����ݰ��Ľ���ӿڡ�</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Э��</td>
                  <td width="78%" class="vtable"><select name="proto" class="formfld" onchange="proto_change()">
                      <?php $protocols = explode(" ", "TCP UDP ICMP ESP AH GRE IPv6 IGMP any"); foreach ($protocols as $proto): ?>
                      <option value="<?=strtolower($proto);?>" <?php if (strtolower($proto) == $pconfig['proto']) echo "selected"; ?>> 
                      <?=htmlspecialchars($proto);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br> <span class="vexpl">ѡ�񱾹���Ӧ��������Э�顣<br>
                    ��ʾ�����������£�������Ҫѡ<em>TCP</em>��</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Դ��ַ</td>
                  <td width="78%" class="vtable"> <input name="srcnot" type="checkbox" id="srcnot" value="yes" <?php if ($pconfig['srcnot']) echo "checked"; ?>> 
                    <strong>��</strong><br>
                    ��ѡ�����ڷ�ѡ��<br> <br> 
                    <table border="0" cellspacing="0" cellpadding="0">
                      <tr> 
                        <td>���ͣ�&nbsp;&nbsp;</td>
						<td></td>
                        <td><select name="srctype" class="formfld" onChange="typesel_change()">
                            <?php $sel = is_specialnet($pconfig['src']); ?>
                            <option value="any" <?php if ($pconfig['src'] == "any") { echo "selected"; } ?>> 
                            ����</option>
                            <option value="single" <?php if (($pconfig['srcmask'] == 32) && !$sel) { echo "selected"; $sel = 1; } ?>> 
                            ���������</option>
                            <option value="network" <?php if (!$sel) echo "selected"; ?>> 
                            ����</option>
                            <option value="lan" <?php if ($pconfig['src'] == "lan") { echo "selected"; } ?>> 
                            LAN ����</option>
                            <option value="pptp" <?php if ($pconfig['src'] == "pptp") { echo "selected"; } ?>> 
                            PPTP �ͻ�</option>
                            <?php for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++): ?>
                            <option value="opt<?=$i;?>" <?php if ($pconfig['src'] == "opt" . $i) { echo "selected"; } ?>> 
                            <?=htmlspecialchars($config['interfaces']['opt' . $i]['descr']);?>
                            ����</option>
                            <?php endfor; ?>
                          </select></td>
                      </tr>
                      <tr> 
                        <td>��ַ��&nbsp;&nbsp;</td>
						<td><?=$mandfldhtmlspc;?></td>
                        <td><input name="src" type="text" class="formfldalias" id="src" size="20" value="<?php if (!is_specialnet($pconfig['src'])) echo htmlspecialchars($pconfig['src']);?>">
                          / 
                          <select name="srcmask" class="formfld" id="srcmask">
                            <?php for ($i = 31; $i > 0; $i--): ?>
                            <option value="<?=$i;?>" <?php if ($i == $pconfig['srcmask']) echo "selected"; ?>> 
                            <?=$i;?>
                            </option>
                            <?php endfor; ?>
                          </select></td>
                      </tr>
                    </table></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Դ�˿ڷ�Χ 
                  </td>
                  <td width="78%" class="vtable"> <table border="0" cellspacing="0" cellpadding="0" summary="from-to widget">
                      <tr> 
                        <td>�ԣ�&nbsp;&nbsp;</td>
                        <td><select name="srcbeginport" class="formfld" onchange="src_rep_change();ext_change()">
                            <option value="">��������</option>
                            <option value="any" <?php $bfound = 0; if ($pconfig['srcbeginport'] == "any") { echo "selected"; $bfound = 1; } ?>>����</option>
                            <?php foreach ($wkports as $wkport => $wkportdesc): ?>
                            <option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['srcbeginport']) {
																echo "selected";
																$bfound = 1;
															}?>> 
                            <?=htmlspecialchars($wkportdesc);?>
                            </option>
                            <?php endforeach; ?>
                          </select> <input name="srcbeginport_cust" type="text" size="5" value="<?php if (!$bfound && $pconfig['srcbeginport']) echo htmlspecialchars($pconfig['srcbeginport']); ?>"></td>
                      </tr>
                      <tr> 
                        <td>����</td>
                        <td><select name="srcendport" class="formfld" onchange="ext_change()">
                            <option value="">��������</option>
                            <option value="any" <?php $bfound = 0; if ($pconfig['srcendport'] == "any") { echo "selected"; $bfound = 1; } ?>>����</option>
                            <?php foreach ($wkports as $wkport => $wkportdesc): ?>
                            <option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['srcendport']) {
																echo "selected";
																$bfound = 1;
															}?>> 
                            <?=htmlspecialchars($wkportdesc);?>
                            </option>
                            <?php endforeach; ?>
                          </select> <input name="srcendport_cust" type="text" size="5" value="<?php if (!$bfound && $pconfig['srcendport']) echo htmlspecialchars($pconfig['srcendport']); ?>"></td>
                      </tr>
                    </table>
                    <br> <span class="vexpl">ָ�����������õ����ݰ�Դ�˿ڻ�˿ڷ�Χ��<br>
                    ��ʾ������ֻ����Ե��˿ڹ��˵Ļ���<em>��������</em>�����ռ��ɡ�</span></td>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Ŀ�ĵ�ַ</td>
                  <td width="78%" class="vtable"> <input name="dstnot" type="checkbox" id="dstnot" value="yes" <?php if ($pconfig['dstnot']) echo "checked"; ?>> 
                    <strong>��</strong><br>
                    ��ѡ�����ڷ�ѡ��<br> <br> 
                    <table border="0" cellspacing="0" cellpadding="0">
                      <tr> 
                        <td>���ͣ�&nbsp;&nbsp;</td>
						<td></td>
                        <td><select name="dsttype" class="formfld" onChange="typesel_change()">
                            <?php $sel = is_specialnet($pconfig['dst']); ?>
                            <option value="any" <?php if ($pconfig['dst'] == "any") { echo "selected"; } ?>> 
                            ����</option>
                            <option value="single" <?php if (($pconfig['dstmask'] == 32) && !$sel) { echo "selected"; $sel = 1; } ?>> 
                            ���������</option>
                            <option value="network" <?php if (!$sel) echo "selected"; ?>> 
                            ����</option>
                            <option value="lan" <?php if ($pconfig['dst'] == "lan") { echo "selected"; } ?>> 
                            LAN ����</option>
                            <option value="pptp" <?php if ($pconfig['dst'] == "pptp") { echo "selected"; } ?>> 
                            PPTP �ͻ�</option>
                            <?php for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++): ?>
                            <option value="opt<?=$i;?>" <?php if ($pconfig['dst'] == "opt" . $i) { echo "selected"; } ?>> 
                            <?=htmlspecialchars($config['interfaces']['opt' . $i]['descr']);?>
                            ����</option>
                            <?php endfor; ?>
                          </select> </td>
                      </tr>
                      <tr> 
                        <td>��ַ��&nbsp;&nbsp;</td>
						<td><?=$mandfldhtmlspc;?></td>
                        <td><input name="dst" type="text" class="formfldalias" id="dst" size="20" value="<?php if (!is_specialnet($pconfig['dst'])) echo htmlspecialchars($pconfig['dst']);?>">
                          / 
                          <select name="dstmask" class="formfld" id="dstmask">
                            <?php for ($i = 31; $i > 0; $i--): ?>
                            <option value="<?=$i;?>" <?php if ($i == $pconfig['dstmask']) echo "selected"; ?>> 
                            <?=$i;?>
                            </option>
                            <?php endfor; ?>
                          </select></td>
                      </tr>
                    </table></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Ŀ�Ķ˿ڷ�Χ</td>
                  <td width="78%" class="vtable"> <table border="0" cellspacing="0" cellpadding="0">
                      <tr> 
                        <td>�ԣ�&nbsp;&nbsp;</td>
                        <td><select name="dstbeginport" class="formfld" onchange="dst_rep_change();ext_change()">
                            <option value="">��������</option>
                            <option value="any" <?php $bfound = 0; if ($pconfig['dstbeginport'] == "any") { echo "selected"; $bfound = 1; } ?>>����</option>
                            <?php foreach ($wkports as $wkport => $wkportdesc): ?>
                            <option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['dstbeginport']) {
																echo "selected";
																$bfound = 1;
															}?>> 
                            <?=htmlspecialchars($wkportdesc);?>
                            </option>
                            <?php endforeach; ?>
                          </select> <input name="dstbeginport_cust" type="text" size="5" value="<?php if (!$bfound && $pconfig['dstbeginport']) echo htmlspecialchars($pconfig['dstbeginport']); ?>"></td>
                      </tr>
                      <tr> 
                        <td>����</td>
                        <td><select name="dstendport" class="formfld" onchange="ext_change()">
                            <option value="">��������</option>
                            <option value="any" <?php $bfound = 0; if ($pconfig['dstendport'] == "any") { echo "selected"; $bfound = 1; } ?>>����</option>
                            <?php foreach ($wkports as $wkport => $wkportdesc): ?>
                            <option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['dstendport']) {
																echo "selected";
																$bfound = 1;
															}?>> 
                            <?=htmlspecialchars($wkportdesc);?>
                            </option>
                            <?php endforeach; ?>
                          </select> <input name="dstendport_cust" type="text" size="5" value="<?php if (!$bfound && $pconfig['dstendport']) echo htmlspecialchars($pconfig['dstendport']); ?>"></td>
                      </tr>
                    </table>
                    <br> <span class="vexpl">ָ�����������õ����ݰ�Ŀ�Ķ˿ڻ�˿ڷ�Χ��<br>
                    ��ʾ������ֻ����˵����˿ڣ�ֻ�轫<em>��������</em> �����ա�</span></td>
                <tr> 
                  <td valign="top" class="vncell">����</td>
                  <td class="vtable"> <select name="direction" class="formfld">
                      <option value="" <?php if (!$pconfig['direction']) echo "selected"; ?>>����</option>
                      <option value="in" <?php if ($pconfig['direction'] == "in") echo "selected"; ?>>��</option>
                      <option value="out" <?php if ($pconfig['direction'] == "out") echo "selected"; ?>>��</option>
                    </select> <br>
                    �Դ��޶�������ָ���ӿ����Բ�ͬ����ͨ�������ݰ������ӷ���ǽ�ĽǶȿ��� </td>
                </tr>
				<tr> 
                  <td width="22%" valign="top" class="vncell">IP�ķ�������(TOS)</td>
                  <td width="78%" class="vtable"> <table border="0" cellspacing="0" cellpadding="0">
                      <?php 
				  $iniptos = explode(",", $pconfig['iptos']);
				  foreach ($iptos as $tos): $dontcare = true; ?>
                      <tr> 
                        <td width="80" nowrap><strong> 
			  <?echo $tos;?>
                          </strong></td>
                        <td nowrap> <input type="radio" name="iptos_<?=$tos;?>" value="on" <?php if (array_search($tos, $iniptos) !== false) { echo "checked"; $dontcare = false; }?>>
                          ��&nbsp;&nbsp;&nbsp;</td>
                        <td nowrap> <input type="radio" name="iptos_<?=$tos;?>" value="off" <?php if (array_search("!" . $tos, $iniptos) !== false) { echo "checked"; $dontcare = false; }?>>
                          ��&nbsp;&nbsp;&nbsp;</td>
                        <td nowrap> <input type="radio" name="iptos_<?=$tos;?>" value="" <?php if ($dontcare) echo "checked";?>>
                          ������</td>
                      </tr>
                      <?php endforeach; ?>
                    </table>
                    <span class="vexpl">������ѡ����IP�������ͣ�TOS��ֵ��ƥ�����ݰ���
                    </span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">IP���ݰ�����</td>
                  <td width="78%" class="vtable"><input name="iplen" type="text" id="iplen" size="10" value="<?=htmlspecialchars($pconfig['iplen']);?>"> 
                    <br>
                    �ڴ��趨һ��������ƥ�����ݰ��������ǵ���ֵ����һ��������-ֹ���﷨ָ���ķ�Χ�����磺0-80��</td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">TCP���</td>
                  <td width="78%" class="vtable"> <table border="0" cellspacing="0" cellpadding="0">
                      <?php 
				  $inflags = explode(",", $pconfig['tcpflags']);
				  foreach ($tcpflags as $tcpflag): $dontcare = true; ?>
                      <tr> 
                        <td width="40" nowrap><strong> 
                          <?=strtoupper($tcpflag);?>
                          </strong></td>
                        <td nowrap> <input type="radio" name="tcpflags_<?=$tcpflag;?>" value="on" <?php if (array_search($tcpflag, $inflags) !== false) { echo "checked"; $dontcare = false; }?>>
                          ��λ&nbsp;&nbsp;&nbsp;</td>
                        <td nowrap> <input type="radio" name="tcpflags_<?=$tcpflag;?>" value="off" <?php if (array_search("!" . $tcpflag, $inflags) !== false) { echo "checked"; $dontcare = false; }?>>
                          ����&nbsp;&nbsp;&nbsp;</td>
                        <td nowrap> <input type="radio" name="tcpflags_<?=$tcpflag;?>" value="" <?php if ($dontcare) echo "checked";?>>
                          ������</td>
                      </tr>
                      <?php endforeach; ?>
                    </table>
                    <span class="vexpl">������ѡ����TCP�����ƥ�����ݰ���</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">����</td>
                  <td width="78%" class="vtable"> <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>"> 
                    <br> <span class="vexpl">�����ڴ�����Щ������Ϣ�Ա��պ�ο������ᱻ��������</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> <input name="Submit" type="submit" class="formbtn" value="����"> 
                    <?php if (isset($id) && $a_shaper[$id]): ?>
                    <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>">
                    <?php endif; ?>
					<input name="after" type="hidden" value="<?=htmlspecialchars($after);?>">
                  </td>
                </tr>
              </table>
</form>
<script type="text/javascript">
<!--
ext_change();
typesel_change();
proto_change();
//-->
</script>
<?php else: ?>
<p><strong>������µĹ���ǰ����Ҫ�ȴ����ܵ�����С�</strong></p>
<?php endif; ?>
<?php include("fend.inc"); ?>
