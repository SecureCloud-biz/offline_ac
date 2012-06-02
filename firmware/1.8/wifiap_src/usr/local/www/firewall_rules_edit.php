#!/usr/local/bin/php
<?php 
/*
	$Id: firewall_rules_edit.php 468 2011-06-06 19:34:13Z awhite $
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
	$maxnetmask = 128;
} else {
	$configname = 'rule';
	$typelink = '';
	$maxnetmask = 32;
}
$pgtitle = array("����ǽ", "����", "�༭");	/* make group manager happy */
$pgtitle = array("Firewall", ipv6enabled() ? ($ipv6rules ? 'IPv6 Rules' : 'IPv4 Rules') : 'Rules', "Edit");

$specialsrcdst = explode(" ", "any wanip lan pptp");

if (!is_array($config['filter'][$configname])) {
	$config['filter'][$configname] = array();
}
filter_rules_sort();
$a_filter = &$config['filter'][$configname];

$id = $_GET['id'];
if (is_numeric($_POST['id']))
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
	
	if (in_array($net, $specialsrcdst) || (strstr($net, "opt") && !is_alias($net)))
		return true;
	else
		return false;
}

function address_to_pconfig($adr, &$padr, &$pmask, &$pnot, &$pbeginport, &$pendport) {
	global $maxnetmask;
		
	if (isset($adr['any']))
		$padr = "any";
	else if ($adr['network'])
		$padr = $adr['network'];
	else if ($adr['address']) {
		list($padr, $pmask) = explode("/", $adr['address']);
		if (!$pmask)
			$pmask = $maxnetmask;
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
	global $maxnetmask;
	
	$adr = array();
	
	if ($padr == "any")
		$adr['any'] = true;
	else if (is_specialnet($padr))
		$adr['network'] = $padr;
	else {
		$adr['address'] = $padr;
		if ($pmask != $maxnetmask)
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

if (isset($id) && $a_filter[$id]) {
	$pconfig['interface'] = $a_filter[$id]['interface'];
	
	if (!isset($a_filter[$id]['type']))
		$pconfig['type'] = "pass";
	else
		$pconfig['type'] = $a_filter[$id]['type'];
	
	if (isset($a_filter[$id]['protocol']))
		$pconfig['proto'] = $a_filter[$id]['protocol'];
	else
		$pconfig['proto'] = "any";
	
	if ($a_filter[$id]['protocol'] == "icmp" || $a_filter[$id]['protocol'] == "ipv6-icmp")
		$pconfig['icmptype'] = $a_filter[$id]['icmptype'];
	
	address_to_pconfig($a_filter[$id]['source'], $pconfig['src'],
		$pconfig['srcmask'], $pconfig['srcnot'],
		$pconfig['srcbeginport'], $pconfig['srcendport']);
		
	address_to_pconfig($a_filter[$id]['destination'], $pconfig['dst'],
		$pconfig['dstmask'], $pconfig['dstnot'],
		$pconfig['dstbeginport'], $pconfig['dstendport']);

	$pconfig['disabled'] = isset($a_filter[$id]['disabled']);
	$pconfig['log'] = isset($a_filter[$id]['log']);
	$pconfig['frags'] = isset($a_filter[$id]['frags']);
	$pconfig['descr'] = $a_filter[$id]['descr'];
	
} else {
	/* defaults */
	if ($_GET['if'])
		$pconfig['interface'] = $_GET['if'];
	$pconfig['type'] = "pass";
	$pconfig['src'] = "any";
	$pconfig['dst'] = "any";
}

if (isset($_GET['dup']))
	unset($id);

if ($_POST) {

	if (($_POST['proto'] != "tcp") && ($_POST['proto'] != "udp") && ($_POST['proto'] != "tcp/udp")) {
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
		$_POST['srcmask'] = $maxnetmask;
	}
	if (is_specialnet($_POST['dsttype'])) {
		$_POST['dst'] = $_POST['dsttype'];
		$_POST['dstmask'] = 0;
	}  else if ($_POST['dsttype'] == "single") {
		$_POST['dstmask'] = $maxnetmask;
	}
	
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "type interface proto src dst");
	$reqdfieldsn = explode(",", "Type,Interface,Protocol,Source,Destination");
	
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
	
	if (($_POST['type'] == "reject") && ($_POST['proto'] != "tcp") && ($_POST['proto'] != "udp")) {
		$input_errors[] = "�ܾ�����ֻ���TCP��UDP���ݰ���";
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

	if (!$input_errors) {
		$filterent = array();
		$filterent['type'] = $_POST['type'];
		$filterent['interface'] = $_POST['interface'];
		
		if ($_POST['proto'] != "any")
			$filterent['protocol'] = $_POST['proto'];
		else
			unset($filterent['protocol']);
	
		if (($_POST['proto'] == "icmp" || $_POST['proto'] == "ipv6-icmp") && $_POST['icmptype'])
			$filterent['icmptype'] = $_POST['icmptype'];
		else
			unset($filterent['icmptype']);
		
		pconfig_to_address($filterent['source'], $_POST['src'],
			$_POST['srcmask'], $_POST['srcnot'],
			$_POST['srcbeginport'], $_POST['srcendport']);
			
		pconfig_to_address($filterent['destination'], $_POST['dst'],
			$_POST['dstmask'], $_POST['dstnot'],
			$_POST['dstbeginport'], $_POST['dstendport']);
		
		$filterent['disabled'] = $_POST['disabled'] ? true : false;
		$filterent['log'] = $_POST['log'] ? true : false;
		$filterent['frags'] = $_POST['frags'] ? true : false;
		$filterent['descr'] = $_POST['descr'];
		
		if (isset($id) && $a_filter[$id])
			$a_filter[$id] = $filterent;
		else {
			if (is_numeric($after))
				array_splice($a_filter, $after+1, 0, array($filterent));
			else
				$a_filter[] = $filterent;
		}
		
		write_config();
		touch($d_filterconfdirty_path);
		
		header("Location: firewall_rules.php?if=" . $_POST['interface'] . $typelink);
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
	var o = document.iform.proto;
	portsenabled = (o.selectedIndex < 3);

	if (o.selectedIndex == 3) {
		document.iform.icmptype.disabled = 0;
	} else {
		document.iform.icmptype.disabled = 1;
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
            <form action="firewall_rules_edit.php<?=($typelink?'?' . $typelink:'')?>" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">����</td>
                  <td width="78%" class="vtable">
					<select name="type" class="formfld">
                      <?php $types = explode(" ", "Pass Block Reject"); foreach ($types as $type): ?>
                      <option value="<?=strtolower($type);?>" <?php if (strtolower($type) == strtolower($pconfig['type'])) echo "selected"; ?>>
                      <?=htmlspecialchars($type);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br>
                    <span class="vexpl">��Է�������ָ�����������ݰ�ѡ��һ������������<br>
��ʾ����reject�ܾ�����������ڡ�block��ֹ����������������һ�����ݰ������TCP��RST�����UDP�ġ��˿ڲ��ɵ��ICMP���������ߣ�������ֹ��ֻ�ǰ�����ϡ�������ͬ����ԭ���ݰ����ᱻ���������ܾ���ֻ����������Э�������еġ�TCP����UDP�� �����������ڡ�TCP/UDP����</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">�ر�</td>
                  <td width="78%" class="vtable"> 
                    <input name="disabled" type="checkbox" id="disabled" value="yes" <?php if ($pconfig['disabled']) echo "checked"; ?>>
                    <strong>�رձ�������</strong><br>
                    <span class="vexpl">�������ڹرձ������򣬶������б���ɾ����
					</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">�ӿ�</td>
                  <td width="78%" class="vtable">
					<select name="interface" class="formfld">
                      <?php
					  if ($ipv6rules)
					      $interfaces = array('wan' => 'WAN', 'lan' => 'LAN', 'ipsec' => 'IPsec');
					  else
					      $interfaces = array('wan' => 'WAN', 'lan' => 'LAN', 'pptp' => 'PPTP', 'ipsec' => 'IPsec');
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
                  <td width="78%" class="vtable">
                    <select name="proto" class="formfld" onchange="proto_change()">
                        <?php
                        $protocols = explode(' ', 'TCP UDP TCP/UDP ' .
                            (!$ipv6rules ? 'ICMP IPv6 IGMP' : 'IPv6-ICMP IPv6-NONXT IPv6-OPTS IPv6-ROUTE IPv6-FRAG') .
                            ' ESP AH GRE any');
                        foreach ($protocols as $proto): ?>
                            <option value="<?=strtolower($proto);?>" <?php if (strtolower($proto) == $pconfig['proto'])
                            echo "selected"; ?>><?=htmlspecialchars($proto);?></option>
                        <?php endforeach; ?>
                    </select> <br>
                    <span class="vexpl">ѡ�񱾹���Ӧ��������Э��<br>
                    ��ʾ�����������£�������Ҫѡ<em>TCP</em> ��</span></td>
                </tr>
                <tr>
                  <td valign="top" class="vncell">ICMP����</td>
                  <td class="vtable">
                    <select name="icmptype" class="formfld">
                      <?php
					  
					  if (!$ipv6rules) {
                          $icmptypes = array(
					  	"" => "����",
					  	"unreach" => "Ŀ�Ĳ��ɵ���",
						"echo" => "����",
						"echorep" => "����Ӧ��",
						"squench" => "Դ����",
						"redir" => "�ض���",
						"timex" => "��ʱ",
						"paramprob" => "��������",
						"timest" => "ʱ���",
						"timestrep" => "ʱ���Ӧ��",
						"inforeq" => "��Ϣ����",
						"inforep" => "��ϢӦ��",
						"maskreq" => "��ַ��������",
						"maskrep" => "��ַ����Ӧ��"
                          );
                      } else {
                          $icmptypes = array(
                            '' => 'any',
                            1 => 'Destination Unreachable',
                            2 => 'Packet Too Big',
                            3 => 'Time Exceeded',
                            4 => 'Parameter Problem',
                            128 => 'Echo Request',
                            129 => 'Echo Reply',
                            130 => 'Multicast Listener Query',
                            131 => 'Multicast Listener Report',
                            132 => 'Multicast Listener Done',
                            133 => 'Router Solicitation',
                            134 => 'Router Advertisement',
                            135 => 'Neighbor Solicitation',
                            136 => 'Neighbor Advertisement',
                            137 => 'Redirect Message'
                          );
                      }
					  
					  foreach ($icmptypes as $icmptype => $descr): ?>
                      <option value="<?=$icmptype;?>" <?php if ($icmptype == $pconfig['icmptype']) echo "selected"; ?>>
                      <?=htmlspecialchars($descr);?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                    <br>
                    <span class="vexpl">�����������Э����ѡ����ICMP������Ҫ������ѡ��ICMP�����͡�</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Դ��ַ</td>
                  <td width="78%" class="vtable">
<input name="srcnot" type="checkbox" id="srcnot" value="yes" <?php if ($pconfig['srcnot']) echo "checked"; ?>>
                    <strong>��</strong><br>
                    ��ѡ�����ڷ�ѡ��<br>
                    <br>
                    <table border="0" cellspacing="0" cellpadding="0" summary="type-address widget">
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
                            <option value="wanip" <?php if ($pconfig['src'] == "wanip") { echo "selected"; } ?>>
                            WAN ��ַ</option>
                            <option value="lan" <?php if ($pconfig['src'] == "lan") { echo "selected"; } ?>>
                            LAN ����</option>
							<?php if (!$ipv6rules): ?>
                            <option value="pptp" <?php if ($pconfig['src'] == "pptp") { echo "selected"; } ?>>
                            PPTP clients</option>
							<?php endif; ?>
							<?php for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++): ?>
                            <option value="opt<?=$i;?>" <?php if ($pconfig['src'] == "opt" . $i) { echo "selected"; } ?>>
                            <?=htmlspecialchars($config['interfaces']['opt' . $i]['descr']);?> subnet</option>
							<?php endfor; ?>
                          </select></td>
                      </tr>
                      <tr> 
                        <td>��ַ��&nbsp;&nbsp;</td>
						<td><?=$mandfldhtmlspc;?></td>
                        <td><input name="src" type="text" class="<?=($ipv6rules ? 'formfld' : 'formfldalias')?>" id="src" size="20" value="<?php if (!is_specialnet($pconfig['src'])) echo htmlspecialchars($pconfig['src']);?>">
                        /
						<select name="srcmask" class="formfld" id="srcmask">
						<?php for ($i = ($maxnetmask - 1); $i > 0; $i--): ?>
						<option value="<?=$i;?>" <?php if ($i == $pconfig['srcmask']) echo "selected"; ?>><?=$i;?></option>
						<?php endfor; ?>
						</select>
						</td>
					  </tr>
                    </table></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Դ�˿ڷ�Χ
                  </td>
                  <td width="78%" class="vtable"> 
                    <table border="0" cellspacing="0" cellpadding="0" summary="from-to widget">
                      <tr> 
                        <td>from:&nbsp;&nbsp;</td>
                        <td><select name="srcbeginport" class="formfld" onchange="src_rep_change();ext_change()">
                            <option value="">(����)</option>
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
                            <option value="">(����)</option>
                            <option value="any" <?php $bfound = 0; if ($pconfig['srcendport'] == "any") { echo "selected"; $bfound = 1; } ?>>any</option>
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
                    <br> 
                    <span class="vexpl">ָ�����������õ����ݰ�Դ�˿ڻ�˿ڷ�Χ�� �����ֵһ����Ŀ�Ķ˿ڷ�Χ��ͬ��ͨ��ѡ�����⡱�� <br>
                    ��ʾ������ֻ����Ե��˿ڹ��˵Ļ�����<em>���ԣ���</em> �����ռ��ɡ�</span></td>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Ŀ�ĵ�ַ</td>
                  <td width="78%" class="vtable"> 
                    <input name="dstnot" type="checkbox" id="dstnot" value="yes" <?php if ($pconfig['dstnot']) echo "checked"; ?>> 
                    <strong>��</strong><br>
                    ��ѡ�����ڷ�ѡ��<br>
                    <br>
                    <table border="0" cellspacing="0" cellpadding="0" summary="type-address widget">
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
                            <option value="wanip" <?php if ($pconfig['dst'] == "wanip") { echo "selected"; } ?>>
                            WAN ��ַ</option>
                            <option value="lan" <?php if ($pconfig['dst'] == "lan") { echo "selected"; } ?>>
                            LAN ����</option>
							<?php if (!$ipv6rules): ?>
                            <option value="pptp" <?php if ($pconfig['dst'] == "pptp") { echo "selected"; } ?>>
                            PPTP clients</option>
							<?php endif; ?>
							<?php for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++): ?>
                            <option value="opt<?=$i;?>" <?php if ($pconfig['dst'] == "opt" . $i) { echo "selected"; } ?>>
                            <?=htmlspecialchars($config['interfaces']['opt' . $i]['descr']);?> subnet</option>
							<?php endfor; ?>
                          </select></td>
                      </tr>
                      <tr> 
                        <td>��ַ��&nbsp;&nbsp;</td>
						<td><?=$mandfldhtmlspc;?></td>
                        <td><input name="dst" type="text" class="<?=($ipv6rules ? 'formfld' : 'formfldalias')?>" id="dst" size="20" value="<?php if (!is_specialnet($pconfig['dst'])) echo htmlspecialchars($pconfig['dst']);?>">
                          / 
                          <select name="dstmask" class="formfld" id="dstmask">
						<?php for ($i = ($maxnetmask - 1); $i > 0; $i--): ?>
						<option value="<?=$i;?>" <?php if ($i == $pconfig['dstmask']) echo "selected"; ?>><?=$i;?></option>
						<?php endfor; ?>
						</select></td>
                      </tr>
                    </table></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Ŀ�Ķ˿ڷ�Χ
                      </td>
                  <td width="78%" class="vtable"> 
                    <table border="0" cellspacing="0" cellpadding="0" summary="from-to widget">
                      <tr> 
                        <td>�ԣ�&nbsp;&nbsp;</td>
                        <td><select name="dstbeginport" class="formfld" onchange="dst_rep_change();ext_change()">
                            <option value="">(����)</option>
                            <option value="any" <?php $bfound = 0; if ($pconfig['dstbeginport'] == "any") { echo "selected"; $bfound = 1; } ?>>any</option>
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
                            <option value="">(����)</option>
                            <option value="any" <?php $bfound = 0; if ($pconfig['dstendport'] == "any") { echo "selected"; $bfound = 1; } ?>>any</option>
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
                    ��ʾ������ֻ����˵����˿ڣ�ֻ�轫<em>��������</em> �����ա�
                    </span></td>
                
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">��Ƭ</td>
                  <td width="78%" class="vtable"> 
                    <input name="frags" type="checkbox" id="frags" value="yes" <?php if ($pconfig['frags']) echo "checked"; ?>>
                    <strong>�����Ƭ���ݰ�</strong><br>
                    <span class="vexpl">��ʾ�������ѡ��������ǽ��������ĸ�����ʹ������DoS������
                    ���������£���������򿪡�ֻ�ڵ���������ĳЩվ���������ʱ�ɳ��Դ�����</span></td>
                </tr>
                <tr style="display:none;"> 
                  <td width="22%" valign="top" class="vncellreq">��־</td>
                  <td width="78%" class="vtable"> 
                    <input name="log" type="checkbox" id="log" value="yes" <?php if ($pconfig['log']) echo "checked"; ?>>
                    <strong>���ù�����������ݰ�������־��</strong><br>
                    <span class="vexpl">��ʾ������ǽ�ı�����־�ռ����ޡ���Ҫ������־
                    ��¼�������������־�����Կ���ʹ��������־��¼�������� 
                    ���ɲ鿴 <a href="diag_logs_settings.php">���: ϵͳ 
                    ��־: ����</a> ҳ�棩��</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">����</td>
                  <td width="78%" class="vtable"> 
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>"> 
                    <br> <span class="vexpl">�����ڴ�����Щ������Ϣ�Ա��պ�ο������ᱻ��������</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="����"> 
                    <?php if (isset($id) && $a_filter[$id]): ?>
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
<?php include("fend.inc"); ?>
