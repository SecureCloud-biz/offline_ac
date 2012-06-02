#!/usr/local/bin/php
<?php 
/*
	$Id: firewall_shaper_pipes_edit.php 503 2012-04-06 16:16:09Z lgrahl $
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

$pgtitle = array("����ǽ", "��������", "�༭�ܵ�");
require("guiconfig.inc");

$a_pipes = &$config['shaper']['pipe'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];
	
if (isset($id) && $a_pipes[$id]) {
	$pconfig['bandwidth'] = $a_pipes[$id]['bandwidth'];
	$pconfig['delay'] = $a_pipes[$id]['delay'];
	$pconfig['plr'] = $a_pipes[$id]['plr'];
	$pconfig['qsize'] = $a_pipes[$id]['qsize'];
	$pconfig['mask'] = $a_pipes[$id]['mask'];
	$pconfig['descr'] = $a_pipes[$id]['descr'];
}

if ($_POST) {
	
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "bandwidth");
	$reqdfieldsn = explode(",", "Bandwidth");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	if (($_POST['bandwidth'] && !is_numericint($_POST['bandwidth']))) {
		$input_errors[] = "���������Ϊ������";
	}
	if (($_POST['delay'] && !is_numericint($_POST['delay']))) {
		$input_errors[] = "��ʱ������Ϊ������";
	}
	if ($_POST['plr'] && (!is_numeric($_POST['plr']) || $_POST['plr'] < 0 || $_POST['plr'] > 1)) {
		$input_errors[] = "�����ʲ�����Ϊ����0-1��С����";
	}
	if ($_POST['qsize'] && (!is_numericint($_POST['qsize']) || $_POST['qsize'] < 2 || $_POST['qsize'] > 100)) {
		$input_errors[] = "queue�Ĵ�С��Ϊ����2-100��������";
	}

	if (!$input_errors) {
		$pipe = array();
		
		$pipe['bandwidth'] = $_POST['bandwidth'];
		if ($_POST['delay'])
			$pipe['delay'] = $_POST['delay'];
		if ($_POST['plr'])
			$pipe['plr'] = $_POST['plr'];
		if ($_POST['qsize'])
			$pipe['qsize'] = $_POST['qsize'];
		if ($_POST['mask'])
			$pipe['mask'] = $_POST['mask'];
		$pipe['descr'] = $_POST['descr'];
		
		if (isset($id) && $a_pipes[$id]) {
			// Scheduler: update matching jobs
			croen_update_job('shaper-set_pipe_bandwidth', $a_pipes[$id]['descr'], ($pipe['descr'] != '' ? $pipe['descr'] : FALSE));
			$a_pipes[$id] = $pipe;
		} else
			$a_pipes[] = $pipe;
		
		write_config();
		touch($d_shaperconfdirty_path);
		header("Location: firewall_shaper_pipes.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
            <form action="firewall_shaper_pipes_edit.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">����</td>
                  <td width="78%" class="vtable"><?=$mandfldhtml;?><input name="bandwidth" type="text" id="bandwidth" size="5" value="<?=htmlspecialchars($pconfig['bandwidth']);?>"> 
                    &nbsp;Kbit/s</td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">��ʱ</td>
                  <td width="78%" class="vtable"> <input name="delay" type="text" id="delay" size="5" value="<?=htmlspecialchars($pconfig['delay']);?>"> 
                    &nbsp;ms<br> <span class="vexpl">��ʾ�����������£� 
                    ����Ӧ��Ϊ0���������գ���</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">������</td>
                  <td width="78%" class="vtable"> <input name="plr" type="text" id="plr" size="5" value="<?=htmlspecialchars($pconfig['plr']);?>"> 
                    <br> <span class="vexpl">��ʾ�����������£�����Ӧ��Ϊ0���������գ��������Ҫ�����ⶪ������Ļ���0.001����ζ��ǧ��֮һ�Ķ����ʡ�</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">�۳�</td>
                  <td width="78%" class="vtable"> <input name="qsize" type="text" id="qsize" size="8" value="<?=htmlspecialchars($pconfig['qsize']);?>"> 
                    &nbsp;slots�ۣ���λ��<br> 
                    <span class="vexpl">��ʾ�����������£��������ռ��ɡ����н���ܵ������ݰ��ȱ�����
					һ���̶����ȵ����ݲ��У�Ȼ������������ʱ��ֵ�����ӳ٣������Ŀ�ĵء�
				������̫����˵�����ֵĬ��Ϊ50slots��</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">����</td>
                  <td width="78%" class="vtable"> <select name="mask" class="formfld">
                      <option value="" <?php if (!$pconfig['mask']) echo "selected"; ?>>��</option>
                      <option value="source" <?php if ($pconfig['mask'] == "source") echo "selected"; ?>>Դ</option>
                      <option value="destination" <?php if ($pconfig['mask'] == "destination") echo "selected"; ?>>Ŀ��</option>
                    </select> <br>
                    <span class="vexpl">��ѡ���ˡ�Դ����Ŀ�ġ���monowall���������������Ĵ�����ʱ�������ʺͲ۳��ֱ�Ϊ
					ÿһ��Դ��Ŀ��IP����һ����̬�Ĺܵ����������Ϳ��Ժܱ�ݵ�Ϊÿ̨����ָ��������޶���<br>ע�⣺������ʮ��
					ʵ�á�
                    </span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">����</td>
                  <td width="78%" class="vtable"> <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>"> 
                    <br> <span class="vexpl">�����ڴ�����Щ������Ϣ�Ա��պ�ο������ᱻ��������</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> <input name="Submit" type="submit" class="formbtn" value="����"> 
                    <?php if (isset($id) && $a_pipes[$id]): ?>
                    <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>">
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<?php include("fend.inc"); ?>
