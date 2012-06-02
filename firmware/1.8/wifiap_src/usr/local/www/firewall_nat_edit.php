#!/usr/local/bin/php
<?php 
/*
	$Id: firewall_nat_edit.php 415 2010-12-29 14:28:18Z awhite $
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

$pgtitle = array("防火墙", "NAT", "编辑");
require("guiconfig.inc");

if (!is_array($config['nat']['rule'])) {
	$config['nat']['rule'] = array();
}
nat_rules_sort();
$a_nat = &$config['nat']['rule'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_nat[$id]) {
	$pconfig['extaddr'] = $a_nat[$id]['external-address'];
	$pconfig['proto'] = $a_nat[$id]['protocol'];
	list($pconfig['beginport'],$pconfig['endport']) = explode("-", $a_nat[$id]['external-port']);
	$pconfig['localip'] = $a_nat[$id]['target'];
	$pconfig['localbeginport'] = $a_nat[$id]['local-port'];
	$pconfig['descr'] = $a_nat[$id]['descr'];
	$pconfig['interface'] = $a_nat[$id]['interface'];
	if (!$pconfig['interface'])
		$pconfig['interface'] = "wan";
} else {
	$pconfig['interface'] = "wan";
}

if ($_POST) {

	if ($_POST['beginport_cust'] && !$_POST['beginport'])
		$_POST['beginport'] = $_POST['beginport_cust'];
	if ($_POST['endport_cust'] && !$_POST['endport'])
		$_POST['endport'] = $_POST['endport_cust'];
	if ($_POST['localbeginport_cust'] && !$_POST['localbeginport'])
		$_POST['localbeginport'] = $_POST['localbeginport_cust'];
		
	if (!$_POST['endport'])
		$_POST['endport'] = $_POST['beginport'];
	
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "interface proto beginport localip localbeginport");
	$reqdfieldsn = explode(",", "Interface,Protocol,Start port,NAT IP,Local port");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	if ($_POST['extaddr'] == 'wan' && $_POST['interface'] == 'wan') {
		$input_errors[] = "当外部地址设备为WAN地址时，此接口不能再设置为WAN。";
	}
	
	if (($_POST['beginport'] && !is_port($_POST['beginport']))) {
		$input_errors[] = "起始端口号须为介于1-65535的整数。";
	}
	if (($_POST['endport'] && !is_port($_POST['endport']))) {
		$input_errors[] = "终止端口号须为介于1-65535的整数。";
	}
	if (($_POST['localbeginport'] && !is_port($_POST['localbeginport']))) {
		$input_errors[] = "本地端口号须为介于1-65535的整数。";
	}
	if (($_POST['localip'] && !is_ipaddroralias($_POST['localip']))) {
		$input_errors[] = "须指定一个合法的 NAT IP 地址或相应的主机别名。";
	}
	
	if ($_POST['beginport'] > $_POST['endport']) {
		/* swap */
		$tmp = $_POST['endport'];
		$_POST['endport'] = $_POST['beginport'];
		$_POST['beginport'] = $tmp;
	}
	
	if (!$input_errors) {
		if (($_POST['endport'] - $_POST['beginport'] + $_POST['localbeginport']) > 65535)
			$input_errors[] = "目标端口号须为介于1-65535的整数。";
	}
	
	/* check for overlaps */
	foreach ($a_nat as $natent) {
		if (isset($id) && ($a_nat[$id]) && ($a_nat[$id] === $natent))
			continue;
		if ($natent['interface'] != $_POST['interface'])
			continue;
		if ($natent['external-address'] != $_POST['extaddr'])
			continue;
		if (($natent['protocol'] != $_POST['proto']) && ($natent['protocol'] != "tcp/udp") && ($_POST['proto'] != "tcp/udp"))
			continue;
		
		list($begp,$endp) = explode("-", $natent['external-port']);
		if (!$endp)
			$endp = $begp;
		
		if (!(   (($_POST['beginport'] < $begp) && ($_POST['endport'] < $begp))
		      || (($_POST['beginport'] > $endp) && ($_POST['endport'] > $endp)))) {
			
			$input_errors[] = "外部端口号范围与现有规则相重叠。";
			break;
		}
	}

	if (!$input_errors) {
		$natent = array();
		if ($_POST['extaddr'])
			$natent['external-address'] = $_POST['extaddr'];
		$natent['protocol'] = $_POST['proto'];
		
		if ($_POST['beginport'] == $_POST['endport'])
			$natent['external-port'] = $_POST['beginport'];
		else
			$natent['external-port'] = $_POST['beginport'] . "-" . $_POST['endport'];
		
		$natent['target'] = $_POST['localip'];
		$natent['local-port'] = $_POST['localbeginport'];
		$natent['interface'] = $_POST['interface'];
		$natent['descr'] = $_POST['descr'];
		
		if (isset($id) && $a_nat[$id])
			$a_nat[$id] = $natent;
		else
			$a_nat[] = $natent;
		
		touch($d_natconfdirty_path);
		
		if ($_POST['autoadd'] && ($_POST['extaddr'] != 'wan')) {
			/* auto-generate a matching firewall rule */
			$filterent = array();		
			$filterent['interface'] = $_POST['interface'];
			$filterent['protocol'] = $_POST['proto'];
			$filterent['source']['any'] = "";
			$filterent['destination']['address'] = $_POST['localip'];
			
			$dstpfrom = $_POST['localbeginport'];
			$dstpto = $dstpfrom + $_POST['endport'] - $_POST['beginport'];
			
			if ($dstpfrom == $dstpto)
				$filterent['destination']['port'] = $dstpfrom;
			else
				$filterent['destination']['port'] = $dstpfrom . "-" . $dstpto;
			
			$filterent['descr'] = "NAT " . $_POST['descr'];
			
			$config['filter']['rule'][] = $filterent;
			
			touch($d_filterconfdirty_path);
		}
		
		write_config();
		
		header("Location: firewall_nat.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<script type="text/javascript">
<!--
function ext_change() {
	if (document.iform.beginport.selectedIndex == 0) {
		document.iform.beginport_cust.disabled = 0;
	} else {
		document.iform.beginport_cust.value = "";
		document.iform.beginport_cust.disabled = 1;
	}
	if (document.iform.endport.selectedIndex == 0) {
		document.iform.endport_cust.disabled = 0;
	} else {
		document.iform.endport_cust.value = "";
		document.iform.endport_cust.disabled = 1;
	}
	if (document.iform.localbeginport.selectedIndex == 0) {
		document.iform.localbeginport_cust.disabled = 0;
	} else {
		document.iform.localbeginport_cust.value = "";
		document.iform.localbeginport_cust.disabled = 1;
	}
}
function ext_rep_change() {
	document.iform.endport.selectedIndex = document.iform.beginport.selectedIndex;
	document.iform.localbeginport.selectedIndex = document.iform.beginport.selectedIndex;
}
//-->
</script>
<?php if ($input_errors) print_input_errors($input_errors); ?>
            <form action="firewall_nat_edit.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
			  	<tr>
                  <td width="22%" valign="top" class="vncellreq">网络接口</td>
                  <td width="78%" class="vtable">
					<select name="interface" class="formfld">
						<?php
						$interfaces = array('wan' => 'WAN', 'lan' => 'LAN');
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
                  <td width="22%" valign="top" class="vncellreq">外部IP地址</td>
                  <td width="78%" class="vtable"> 
                    <select name="extaddr" class="formfld">
					  <option value="" <?php if (!$pconfig['extaddr']) echo "selected"; ?>>接口所用IP</option>
					  <option value="wan" <?php if ($pconfig['extaddr'] == 'wan' ) echo "selected"; ?>>WAN地址</option>		
                      <?php
					  if (is_array($config['nat']['servernat'])):
						  foreach ($config['nat']['servernat'] as $sn): ?>
                      <option value="<?=$sn['ipaddr'];?>" <?php if ($sn['ipaddr'] == $pconfig['extaddr']) echo "selected"; ?>><?=htmlspecialchars("{$sn['ipaddr']} ({$sn['descr']})");?></option>
                      <?php endforeach; endif; ?>
                    </select><br>
                    <span class="vexpl">
					若您想在这个网络接口上使用另外一个IP地址来进行NAT，在这里选择。您必须先在
					<a href="firewall_nat_server.php">服务器 NAT</a>页面里设好将使用的IP。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">协议</td>
                  <td width="78%" class="vtable"> 
                    <select name="proto" class="formfld">
                      <?php $protocols = explode(" ", "TCP UDP TCP/UDP"); foreach ($protocols as $proto): ?>
                      <option value="<?=strtolower($proto);?>" <?php if (strtolower($proto) == $pconfig['proto']) echo "selected"; ?>><?=htmlspecialchars($proto);?></option>
                      <?php endforeach; ?>
                    </select> <br> <span class="vexpl">选择本规则适用的IP协议。<br>
                    提示：在大多数情况下，您需要在这里指定 <em>TCP</em> &nbsp;</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">外部端口范围 
                      </td>
                  <td width="78%" class="vtable"> 
                    <table border="0" cellspacing="0" cellpadding="0">
                      <tr> 
                        <td>自：&nbsp;&nbsp;</td>
                        <td><select name="beginport" class="formfld" onChange="ext_rep_change();ext_change()">
                            <option value="">(其它)</option>
                            <?php $bfound = 0; foreach ($wkports as $wkport => $wkportdesc): ?>
                            <option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['beginport']) {
																echo "selected";
																$bfound = 1;
															}?>>
							<?=htmlspecialchars($wkportdesc);?>
							</option>
                            <?php endforeach; ?>
                          </select> <input name="beginport_cust" type="text" size="5" value="<?php if (!$bfound) echo htmlspecialchars($pconfig['beginport']); ?>"></td>
                      </tr>
                      <tr> 
                        <td>到：</td>
                        <td><select name="endport" class="formfld" onChange="ext_change()">
                            <option value="">(其它)</option>
                            <?php $bfound = 0; foreach ($wkports as $wkport => $wkportdesc): ?>
                            <option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['endport']) {
																echo "selected";
																$bfound = 1;
															}?>>
							<?=htmlspecialchars($wkportdesc);?>
							</option>
							<?php endforeach; ?>
                          </select> <input name="endport_cust" type="text" size="5" value="<?php if (!$bfound) echo htmlspecialchars($pconfig['endport']); ?>"></td>
                      </tr>
                    </table>
                    <br> <span class="vexpl">指定在外部IP地址上使用的端口或端口范围。<br>
                    提示：若您只想作一个单端口映射，可将 <em>'到：'</em> 框留空。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">NAT的IP地址</td>
                  <td width="78%" class="vtable"> 
                    <?=$mandfldhtml;?><input name="localip" type="text" class="formfldalias" id="localip" size="20" value="<?=htmlspecialchars($pconfig['localip']);?>"> 
                    <br> <span class="vexpl">输入内部服务器的IP地址。<br>
                    例如： <em>192.168.1.12</em></span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">本地端口</td>
                  <td width="78%" class="vtable"> 
                    <select name="localbeginport" class="formfld" onChange="ext_change()">
                      <option value="">(其它)</option>
                      <?php $bfound = 0; foreach ($wkports as $wkport => $wkportdesc): ?>
                      <option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['localbeginport']) {
																echo "selected";
																$bfound = 1;
															}?>>
					  <?=htmlspecialchars($wkportdesc);?>
					  </option>
                      <?php endforeach; ?>
                    </select> <input name="localbeginport_cust" type="text" size="5" value="<?php if (!$bfound) echo htmlspecialchars($pconfig['localbeginport']); ?>"> 
                    <br>
                    <span class="vexpl">为上面选定的主机IP地址指定端口号。若是一个端口范围，
					只需指定起始的端口号（终止端口号会自动算出）。<br>
                    提示：这里的值通常与上面外部端口号中的 “自：”区相同。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">描述</td>
                  <td width="78%" class="vtable"> 
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>"> 
                    <br> <span class="vexpl">您可在此输入些描述信息以备日后参考（不会被解析）。</span></td>
                </tr><?php if (!(isset($id) && $a_nat[$id])): ?>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="autoadd" type="checkbox" id="autoadd" value="yes">
                    <strong>在防火墙中自动添加一条允许NAT规则通过的过滤规则。 
                    </strong></td>
                </tr><?php endif; ?>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="保存"> 
                    <?php if (isset($id) && $a_nat[$id]): ?>
                    <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>"> 
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<script type="text/javascript">
<!--
ext_change();
//-->
</script>
<?php include("fend.inc"); ?>
