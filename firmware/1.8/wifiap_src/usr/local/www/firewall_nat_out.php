#!/usr/local/bin/php
<?php 
/*
	$Id: firewall_nat_out.php 460 2011-05-13 00:39:32Z awhite $
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

$pgtitle = array("防火墙", "NAT", "转出");
require("guiconfig.inc");

if (!is_array($config['nat']['advancedoutbound']['rule']))
    $config['nat']['advancedoutbound']['rule'] = array();
    
$a_out = &$config['nat']['advancedoutbound']['rule'];
nat_out_rules_sort();

if ($_POST) {

    $pconfig = $_POST;

    $config['nat']['advancedoutbound']['enable'] = ($_POST['enable']) ? true : false;
    write_config();
    
    $retval = 0;
    
    if (!file_exists($d_sysrebootreqd_path)) {
		config_lock();
        $retval |= filter_configure(true);
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

if ($_GET['act'] == "del") {
    if ($a_out[$_GET['id']]) {
        unset($a_out[$_GET['id']]);
        write_config();
        touch($d_natconfdirty_path);
        header("Location: firewall_nat_out.php");
        exit;
    }
}
?>
<?php include("fbegin.inc"); ?>
<form action="firewall_nat_out.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_natconfdirty_path)): ?><p>
<?php print_info_box_np("NAT 设置已改变，<br>您还须按应用钮使之生效。");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="应用更改"></p>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
<tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php
   	$tabs = array('转入' => 'firewall_nat.php',
           		  '服务器 NAT' => 'firewall_nat_server.php',
           		  '1:1' => 'firewall_nat_1to1.php',
           		  '转出' => 'firewall_nat_out.php');
	dynamic_tab_menu($tabs);
?>    
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
              <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="info pane">
                <tr> 
                  <td class="vtable">
                      <input name="enable" type="checkbox" id="enable" value="yes" <?php if (isset($config['nat']['advancedoutbound']['enable'])) echo "checked";?>>
                      <strong>启动高级转出NAT</strong></td>
                </tr>
                <tr> 
                  <td> <input name="submit" type="submit" class="formbtn" value="保存"> 
                  </td>
                </tr>
                <tr>
                  <td><p><span class="vexpl"><span class="red"><strong>Note:<br>
                      </strong></span>若启动了高级转出NAT功能，wifiAP只使用您在下列给出的映射规则，
                      而不自动生成转出NAT规则。 若关闭本功能，针对每个接口子网（除了WAN）的NAT规则将自动生成，
					  下列人工指定的映射将被忽略。</span>
                     若您使用不同于WAN接口的其他目标IP，那么根据
                      <span class="vexpl"> 您的 WAN 联接设置情况，您还需要
                      <a href="services_proxyarp.php"> ARP代理</a>.</span><br>
                      <br>
                      您可在下列输入自定的映射：</p>
                    </td>
                </tr>
              </table>
              <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
                <tr> 
                  <td width="10%" class="listhdrr">接口</td>
                  <td width="20%" class="listhdrr">源地址</td>
                  <td width="20%" class="listhdrr">目的地</td>
                  <td width="20%" class="listhdrr">目标</td>
                  <td width="25%" class="listhdr">说明</td>
                  <td width="5%" class="list"></td>
                </tr>
              <?php $i = 0; foreach ($a_out as $natent): ?>
                <tr valign="top"> 
                  <td class="listlr">
                    <?php
					if (!$natent['interface'] || ($natent['interface'] == "wan"))
					  	echo "WAN";
					else
						echo htmlspecialchars($config['interfaces'][$natent['interface']]['descr']);
					?>
                  </td>
                  <td class="listr"> 
                    <?=$natent['source']['network'];?>
                  </td>
                  <td class="listr"> 
                    <?php
                      if (isset($natent['destination']['any']))
                          echo "*";
                      else {
                          if (isset($natent['destination']['not']))
                              echo "!&nbsp;";
                          echo $natent['destination']['network'];
                      }
                    ?>
                  </td>
                  <td class="listr"> 
                    <?php
                      if (!$natent['target'])
                          echo "*";
                      else
                          echo $natent['target'];
                         
                      if (isset($natent['noportmap']))
                          echo "<br>(no portmap)";
                    ?>
                  </td>
                  <td class="listbg"> 
                    <?=htmlspecialchars($natent['descr']);?>&nbsp;
                  </td>
                  <td class="list" nowrap> <a href="firewall_nat_out_edit.php?id=<?=$i;?>"><img src="e.gif" title="编辑映射" width="17" height="17" border="0" alt="编辑映射"></a>
                     &nbsp;<a href="firewall_nat_out.php?act=del&amp;id=<?=$i;?>" onclick="return confirm('您确认删除本条映射？')"><img src="x.gif" title="删除映射" width="17" height="17" border="0" alt="delete mapping"></a></td>
                </tr>
              <?php $i++; endforeach; ?>
                <tr> 
                  <td class="list" colspan="5"></td>
                  <td class="list"> <a href="firewall_nat_out_edit.php"><img src="plus.gif" title="添加映射" width="17" height="17" border="0" alt="添加映射"></a></td>
                </tr>
              </table>
</td>
  </tr>
</table>
            </form>
<?php include("fend.inc"); ?>
