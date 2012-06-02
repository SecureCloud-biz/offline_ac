#!/usr/local/bin/php
<?php 
/*
	$Id: firewall_shaper_pipes.php 503 2012-04-06 16:16:09Z lgrahl $
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

$pgtitle = array("����ǽ", "��������", "�ܵ�");
require("guiconfig.inc");

if (!is_array($config['shaper']['pipe'])) {
	$config['shaper']['pipe'] = array();
}
if (!is_array($config['shaper']['queue'])) {
	$config['shaper']['queue'] = array();
}
$a_pipes = &$config['shaper']['pipe'];

if ($_GET['act'] == "del") {
	if ($a_pipes[$_GET['id']]) {
		/* check that no rule references this pipe */
		if (is_array($config['shaper']['rule'])) {
			foreach ($config['shaper']['rule'] as $rule) {
				if (isset($rule['targetpipe']) && ($rule['targetpipe'] == $_GET['id'])) {
					$input_errors[] = "�������������������ñ��ܵ����ʲ��ܱ�ɾ����";
					break;
				}
			}
		}
		
		/* check that no queue references this pipe */
		if (is_array($config['shaper']['queue'])) {
			foreach ($config['shaper']['queue'] as $queue) {
				if ($queue['targetpipe'] == $_GET['id']) {
					$input_errors[] = "�������������������ñ��ܵ����ʲ��ܱ�ɾ����";
					break;
				}
			}
		}
		
		if (!$input_errors) {
			// Scheduler: delete matching jobs
			croen_update_job('shaper-set_pipe_bandwidth', $a_pipes[$_GET['id']]['descr']);
			unset($a_pipes[$_GET['id']]);
			
			/* renumber all rules and queues */
			if (is_array($config['shaper']['rule'])) {
				for ($i = 0; isset($config['shaper']['rule'][$i]); $i++) {
					$currule = &$config['shaper']['rule'][$i];
					if (isset($currule['targetpipe']) && ($currule['targetpipe'] > $_GET['id']))
						$currule['targetpipe']--;
				}
			}
			if (is_array($config['shaper']['queue'])) {
				for ($i = 0; isset($config['shaper']['queue'][$i]); $i++) {
					$curqueue = &$config['shaper']['queue'][$i];
					if ($curqueue['targetpipe'] > $_GET['id'])
						$curqueue['targetpipe']--;
				}
			}
			
			write_config();
			touch($d_shaperconfdirty_path);
			header("Location: firewall_shaper_pipes.php");
			exit;
		}
	}
}
?>
<?php include("fbegin.inc"); ?>
<form action="firewall_shaper.php" method="post">
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_shaperconfdirty_path)): ?><p>
<?php print_info_box_np("�����������������Ѹı䣬<br>�����밴Ӧ��ťʹ֮��Ч��");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Ӧ�ø���"></p>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php 
   	$tabs = array('����' => 'firewall_shaper.php',
           		  '�ܵ�' => 'firewall_shaper_pipes.php',
           		  '����' => 'firewall_shaper_queues.php',
           		  '������' => 'firewall_shaper_easy.php',
				  '�߼�����' => 'firewall_shaper_advanced.php',
           		  '���ؾ���' => 'firewall_shaper_magic.php');
	dynamic_tab_menu($tabs);
?>       
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
              <table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
                      <tr> 
                        <td width="10%" class="listhdrr">���</td>
                        <td width="15%" class="listhdrr">����</td>
                        <td width="10%" class="listhdrr">��ʱ</td>
                        <td width="10%" class="listhdrr">������</td>
                        <td width="10%" class="listhdrr">�۳�</td>
                        <td width="15%" class="listhdrr">����</td>
                        <td width="20%" class="listhdr">����</td>
                        <td width="10%" class="list"></td>
                      </tr>
                      <?php $i = 0; foreach ($a_pipes as $pipe): ?>
                      <tr valign="top">
                        <td class="listlr"> 
                          <?=($i+1);?></td>
                        <td class="listr"> 
                          <?=htmlspecialchars($pipe['bandwidth']);?>
                          Kbit/s </td>
                        <td class="listr"> 
                          <?php if ($pipe['delay']): ?>
                          <?=$pipe['delay'];?>
                          ms 
                          <?php endif; ?>
                          &nbsp; </td>
                        <td class="listr"> 
                          <?php if ($pipe['plr']): ?>
                          <?=$pipe['plr'];?>
                          <?php endif; ?>
                          &nbsp; </td>
                        <td class="listr"> 
                          <?php if ($pipe['qsize']): ?>
                          <?=htmlspecialchars($pipe['qsize']);?>
                          <?php endif; ?>
                          &nbsp; </td>
                        <td class="listr"> 
                          <?php if ($pipe['mask']): ?>
                          <?=$pipe['mask'];?>
                          <?php endif; ?>
                          &nbsp; </td>
                        <td class="listbg"> 
                          <?=htmlspecialchars($pipe['descr']);?>
                          &nbsp; </td>
                        <td valign="middle" nowrap class="list"> <a href="firewall_shaper_pipes_edit.php?id=<?=$i;?>"><img src="e.gif" title="�༭�ܵ�" width="17" height="17" border="0"></a> 
                          &nbsp;<a href="firewall_shaper_pipes.php?act=del&amp;id=<?=$i;?>" onclick="return confirm('��ȷ����ɾ�����ܵ���')"><img src="x.gif" title="ɾ���ܵ�" width="17" height="17" border="0" alt="ɾ���ܵ�"></a></td>
                      </tr>
                      <?php $i++; endforeach; ?>
                      <tr> 
                        <td class="list" colspan="7"></td>
                        <td class="list"> <a href="firewall_shaper_pipes_edit.php"><img src="plus.gif" title="��ӹܵ�" width="17" height="17" border="0" alt="��ӹܵ�"></a></td>
                      </tr>
                    </table><br>
                    <strong><span class="red">˵����</span></strong> �ܵ�ֻ����û�б����������������õ�����²ſ��Ա�ɾ����</td>
	</tr>
</table>
            </form>
<?php include("fend.inc"); ?>
