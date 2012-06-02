#!/usr/local/bin/php
<?php 
/*
	$Id: firewall_nat_out_edit.php 421 2011-02-09 12:49:39Z mkasper $
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

$pgtitle = array("防火墙", "NAT", "编辑转出映射");
require("guiconfig.inc");

if (!is_array($config['nat']['advancedoutbound']['rule']))
    $config['nat']['advancedoutbound']['rule'] = array();
    
$a_out = &$config['nat']['advancedoutbound']['rule'];
nat_out_rules_sort();

$id = $_GET['id'];
if (isset($_POST['id']))
    $id = $_POST['id'];

function network_to_pconfig($adr, &$padr, &$pmask, &$pnot) {

    if (isset($adr['any']))
        $padr = "any";
    else if ($adr['network']) {
        list($padr, $pmask) = explode("/", $adr['network']);
        if (!$pmask)
            $pmask = 32;
    }

    if (isset($adr['not']))
        $pnot = 1;
    else
        $pnot = 0;
}

if (isset($id) && $a_out[$id]) {
    list($pconfig['source'],$pconfig['source_subnet']) = explode('/', $a_out[$id]['source']['network']);
    network_to_pconfig($a_out[$id]['destination'], $pconfig['destination'],
	   $pconfig['destination_subnet'], $pconfig['destination_not']);
    $pconfig['target'] = $a_out[$id]['target'];
    $pconfig['interface'] = $a_out[$id]['interface'];
	if (!$pconfig['interface'])
		$pconfig['interface'] = "wan";
    $pconfig['descr'] = $a_out[$id]['descr'];
    $pconfig['noportmap'] = isset($a_out[$id]['noportmap']);
} else {
    $pconfig['source_subnet'] = 24;
    $pconfig['destination'] = "any";
    $pconfig['destination_subnet'] = 24;
	$pconfig['interface'] = "wan";
    $pconfig['noportmap'] = false;
}

if ($_POST) {
    
    if ($_POST['destination_type'] == "any") {
        $_POST['destination'] = "any";
        $_POST['destination_subnet'] = 24;
    }
    
    unset($input_errors);
    $pconfig = $_POST;

    /* input validation */
    $reqdfields = explode(" ", "interface source source_subnet destination destination_subnet");
    $reqdfieldsn = explode(",", "Interface,Source,Source bit count,Destination,Destination bit count");
    
    do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

    if ($_POST['source'] && !is_ipaddr($_POST['source'])) {
        $input_errors[] = "请输入一个合法的源地址。";
    }
    if ($_POST['source_subnet'] && !is_numericint($_POST['source_subnet'])) {
        $input_errors[] = "请为源地址输入一个合法的掩码";
    }
    if ($_POST['destination_type'] != "any") {
        if ($_POST['destination'] && !is_ipaddr($_POST['destination'])) {
            $input_errors[] = "请输入一个合法的目的地址。";
        }
        if ($_POST['destination_subnet'] && !is_numericint($_POST['destination_subnet'])) {
            $input_errors[] = "请为目的地址输入一个合法的掩码";
        }
    }
    if ($_POST['target'] && !is_ipaddr($_POST['target'])) {
        $input_errors[] = "请输入一个合法的目标IP。";
    }
    
    /* check for existing entries */
    $osn = gen_subnet($_POST['source'], $_POST['source_subnet']) . "/" . $_POST['source_subnet'];
    if ($_POST['destination_type'] == "any")
        $ext = "any";
    else
        $ext = gen_subnet($_POST['destination'], $_POST['destination_subnet']) . "/"
            . $_POST['destination_subnet'];
			
	if ($_POST['target']) {
		/* check for clashes with 1:1 NAT (Server NAT is OK) */
		if (is_array($config['nat']['onetoone'])) {
			foreach ($config['nat']['onetoone'] as $natent) {
				if (check_subnets_overlap($_POST['target'], 32, $natent['external'], $natent['subnet'])) {
					$input_errors[] = "您所指定的目标IP与 1:1 NAT 映射设置有重叠。";
					break;
				}
			}
		}
	}
    
    foreach ($a_out as $natent) {
        if (isset($id) && ($a_out[$id]) && ($a_out[$id] === $natent))
            continue;
        
		if (!$natent['interface'])
			$natent['interface'] == "wan";
		
		if (($natent['interface'] == $_POST['interface']) && ($natent['source']['network'] == $osn)) {
			if (isset($natent['destination']['not']) == isset($_POST['destination_not'])) {
				if ((isset($natent['destination']['any']) && ($ext == "any")) ||
						($natent['destination']['network'] == $ext)) {
					$input_errors[] = "已存在一条转出NAT规则与您的设置相同。";
					break;
				}
			}
		}
    }

    if (!$input_errors) {
        $natent = array();
        $natent['source']['network'] = $osn;
        $natent['descr'] = $_POST['descr'];
        $natent['target'] = $_POST['target'];
        $natent['interface'] = $_POST['interface'];
        $natent['noportmap'] = $_POST['noportmap'] ? true : false;
        
        if ($ext == "any")
            $natent['destination']['any'] = true;
        else
            $natent['destination']['network'] = $ext;
        
        if (isset($_POST['destination_not']) && $ext != "any")
            $natent['destination']['not'] = true;
        
        if (isset($id) && $a_out[$id])
            $a_out[$id] = $natent;
        else
            $a_out[] = $natent;
        
        touch($d_natconfdirty_path);
        
        write_config();
        
        header("Location: firewall_nat_out.php");
        exit;
    }
}
?>
<?php include("fbegin.inc"); ?>
<script type="text/javascript">
<!--
function typesel_change() {
    switch (document.iform.destination_type.selectedIndex) {
        case 1: // network
            document.iform.destination.disabled = 0;
            document.iform.destination_subnet.disabled = 0;
            break;
        default:
            document.iform.destination.value = "";
            document.iform.destination.disabled = 1;
            document.iform.destination_subnet.value = "24";
            document.iform.destination_subnet.disabled = 1;
            break;
    }
}
//-->
</script>
<?php if ($input_errors) print_input_errors($input_errors); ?>
            <form action="firewall_nat_out_edit.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
			      <tr>
                  <td width="22%" valign="top" class="vncellreq">网络接口</td>
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
                     <span class="vexpl">选择本条规则将作用的网络接口。<br>
                     提示：大多数情况下，这里您应该选WAN。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">源地址</td>
                  <td width="78%" class="vtable">
					<?=$mandfldhtml;?><input name="source" type="text" class="formfld" id="source" size="20" value="<?=htmlspecialchars($pconfig['source']);?>">
                     
                  / 
                    <select name="source_subnet" class="formfld" id="source_subnet">
                      <?php for ($i = 32; $i >= 0; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['source_subnet']) echo "selected"; ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select>
                    <br>
                     <span class="vexpl">请输入将进行转出NAT映射的源网络地址。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">目的</td>
                  <td width="78%" class="vtable">
				<input name="destination_not" type="checkbox" id="destination_not" value="yes" <?php if ($pconfig['destination_not']) echo "checked"; ?>>
                    <strong>非</strong><br>
                    用这个选项来反选。<br>
                    <br>
                    <table border="0" cellspacing="0" cellpadding="0">
                      <tr> 
                        <td>类型：&nbsp;&nbsp;</td>
						<td></td>
                        <td><select name="destination_type" class="formfld" onChange="typesel_change()">
                            <option value="any" <?php if ($pconfig['destination'] == "any") echo "selected"; ?>> 
                            任意</option>
                            <option value="network" <?php if ($pconfig['destination'] != "any") echo "selected"; ?>> 
                            网络</option>
                          </select></td>
                      </tr>
                      <tr> 
                        <td>地址：&nbsp;&nbsp;</td>
						<td><?=$mandfldhtmlspc;?></td>
                        <td><input name="destination" type="text" class="formfld" id="destination" size="20" value="<?=htmlspecialchars($pconfig['destination']);?>">
                          / 
                          <select name="destination_subnet" class="formfld" id="destination_subnet">
                            <?php for ($i = 32; $i >= 0; $i--): ?>
                            <option value="<?=$i;?>" <?php if ($i == $pconfig['destination_subnet']) echo "selected"; ?>> 
                            <?=$i;?>
                            </option>
                            <?php endfor; ?>
                          </select> </td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
						<td></td>
                        <td><span class="vexpl">输入要进行转出NAT映射的目的网络。</span></td>
                      </tr>
                    </table></td>
                </tr>
                <tr> 
                  <td valign="top" class="vncell">目标</td>
                  <td class="vtable">
<input name="target" type="text" class="formfld" id="target" size="20" value="<?=htmlspecialchars($pconfig['target']);?>">
                    <br>
                     <span class="vexpl">符合本条规则的数据包会被NAT转换为这里给出的目标IP。若留空则转换为所选网络接口的IP。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">端口转换</td>
                  <td width="78%" class="vtable">
					<input name="noportmap" type="checkbox" id="noportmap" value="1" <?php if ($pconfig['noportmap']) echo "checked"; ?>> <strong>不启用端口转换</strong>
                    <br>
                     <span class="vexpl">这个选项可以关闭在进行转出NAT时源端口号到目标端口号的转换。这对于有些作NAT时需要端口不变
				 的软件（比如一些IPsec VPN 网关软件）有利。但是启用本功能会使本地客户不能同时以同一源端口与同一服务器联接。</span></td>
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
                    <?php if (isset($id) && $a_out[$id]): ?>
                    <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>"> 
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<script type="text/javascript">
<!--
typesel_change();
//-->
</script>
<?php include("fend.inc"); ?>
