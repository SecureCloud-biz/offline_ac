#!/usr/local/bin/php
<?php 
/*
	$Id: interfaces_vlan_edit.php 446 2011-05-01 10:07:17Z mkasper $
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

$pgtitle = array("网卡", "接口分配VLAN", "修改VLAN");
require("guiconfig.inc");

if (!is_array($config['vlans']['vlan']))
	$config['vlans']['vlan'] = array();

$a_vlans = &$config['vlans']['vlan'];

$portlist = get_interface_list(true, false);

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_vlans[$id]) {
	$pconfig['if'] = $a_vlans[$id]['if'];
	$pconfig['tag'] = $a_vlans[$id]['tag'];
	$pconfig['descr'] = $a_vlans[$id]['descr'];
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "if tag");
	$reqdfieldsn = explode(",", "Parent interface,VLAN tag");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	if ($_POST['tag'] && (!is_numericint($_POST['tag']) || ($_POST['tag'] < '1') || ($_POST['tag'] > '4094'))) {
		$input_errors[] = "VLAN标记必须是整数（1~4094）。";
	}

	foreach ($a_vlans as $vlan) {
		if (isset($id) && ($a_vlans[$id]) && ($a_vlans[$id] === $vlan))
			continue;
		
		if (($vlan['if'] == $_POST['if']) && ($vlan['tag'] == $_POST['tag'])) {
			$input_errors[] = "此标记 {$vlan['tag']} 已经被使用。";
			break;
		}	
	}

	if (!$input_errors) {
		$vlan = array();
		$vlan['if'] = $_POST['if'];
		$vlan['tag'] = $_POST['tag'];
		$vlan['descr'] = $_POST['descr'];

		if (isset($id) && $a_vlans[$id])
			$a_vlans[$id] = $vlan;
		else
			$a_vlans[] = $vlan;
		
		write_config();		
		touch($d_sysrebootreqd_path);
		header("Location: interfaces_vlan.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
            <form action="interfaces_vlan_edit.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
				<tr>
                  <td width="22%" valign="top" class="vncellreq">父接口</td>
                  <td width="78%" class="vtable"> 
                    <select name="if" class="formfld">
                      <?php
					  foreach ($portlist as $ifn => $ifinfo): ?>
                      <option value="<?=htmlspecialchars($ifn);?>" <?php if ($ifn == $pconfig['if']) echo "selected"; ?>> 
					  <?php if ($ifinfo['drvname'])
							echo htmlspecialchars($ifn . " (" . $ifinfo['drvname'] . ", " .  $ifinfo['mac'] . ")");
						else
							echo htmlspecialchars($ifn . " (" . $ifinfo['mac'] . ")");
					  ?>
                      </option>
                      <?php endforeach; ?>
                    </select></td>
                </tr>
				<tr>
                  <td valign="top" class="vncellreq">VLAN标记</td>
                  <td class="vtable">
                    <?=$mandfldhtml;?><input name="tag" type="text" class="formfld" id="tag" size="6" value="<?=htmlspecialchars($pconfig['tag']);?>">
                    <br>
                    <span class="vexpl">802.1Q VLAN标记(1~4094) </span></td>
			    </tr>
				<tr>
                  <td width="22%" valign="top" class="vncell">描述</td>
                  <td width="78%" class="vtable"> 
                    <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>">
                    <br> <span class="vexpl">必填项（不能含空格）。</span></td>
                </tr>
                <tr>
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> 
                    <input name="Submit" type="submit" class="formbtn" value="保存">
                    <?php if (isset($id) && $a_vlans[$id]): ?>
                    <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>">
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<?php include("fend.inc"); ?>
