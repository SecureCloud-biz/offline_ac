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

$pgtitle = array("����ǽ", "NAT", "ת��");
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
<?php print_info_box_np("NAT �����Ѹı䣬<br>�����밴Ӧ��ťʹ֮��Ч��");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Ӧ�ø���"></p>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
<tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php
   	$tabs = array('ת��' => 'firewall_nat.php',
           		  '������ NAT' => 'firewall_nat_server.php',
           		  '1:1' => 'firewall_nat_1to1.php',
           		  'ת��' => 'firewall_nat_out.php');
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
                      <strong>�����߼�ת��NAT</strong></td>
                </tr>
                <tr> 
                  <td> <input name="submit" type="submit" class="formbtn" value="����"> 
                  </td>
                </tr>
                <tr>
                  <td><p><span class="vexpl"><span class="red"><strong>Note:<br>
                      </strong></span>�������˸߼�ת��NAT���ܣ�wifiAPֻʹ���������и�����ӳ�����
                      �����Զ�����ת��NAT���� ���رձ����ܣ����ÿ���ӿ�����������WAN����NAT�����Զ����ɣ�
					  �����˹�ָ����ӳ�佫�����ԡ�</span>
                     ����ʹ�ò�ͬ��WAN�ӿڵ�����Ŀ��IP����ô����
                      <span class="vexpl"> ���� WAN �������������������Ҫ
                      <a href="services_proxyarp.php"> ARP����</a>.</span><br>
                      <br>
                      ���������������Զ���ӳ�䣺</p>
                    </td>
                </tr>
              </table>
              <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
                <tr> 
                  <td width="10%" class="listhdrr">�ӿ�</td>
                  <td width="20%" class="listhdrr">Դ��ַ</td>
                  <td width="20%" class="listhdrr">Ŀ�ĵ�</td>
                  <td width="20%" class="listhdrr">Ŀ��</td>
                  <td width="25%" class="listhdr">˵��</td>
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
                  <td class="list" nowrap> <a href="firewall_nat_out_edit.php?id=<?=$i;?>"><img src="e.gif" title="�༭ӳ��" width="17" height="17" border="0" alt="�༭ӳ��"></a>
                     &nbsp;<a href="firewall_nat_out.php?act=del&amp;id=<?=$i;?>" onclick="return confirm('��ȷ��ɾ������ӳ�䣿')"><img src="x.gif" title="ɾ��ӳ��" width="17" height="17" border="0" alt="delete mapping"></a></td>
                </tr>
              <?php $i++; endforeach; ?>
                <tr> 
                  <td class="list" colspan="5"></td>
                  <td class="list"> <a href="firewall_nat_out_edit.php"><img src="plus.gif" title="����ӳ��" width="17" height="17" border="0" alt="����ӳ��"></a></td>
                </tr>
              </table>
</td>
  </tr>
</table>
            </form>
<?php include("fend.inc"); ?>