#!/usr/local/bin/php
<?php 
/*
	$Id: firewall_shaper_queues_edit.php 503 2012-04-06 16:16:09Z lgrahl $
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

$a_queues = &$config['shaper']['queue'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_queues[$id]) {
	$pconfig['targetpipe'] = $a_queues[$id]['targetpipe'];
	$pconfig['weight'] = $a_queues[$id]['weight'];
	$pconfig['mask'] = $a_queues[$id]['mask'];
	$pconfig['descr'] = $a_queues[$id]['descr'];
}

if ($_POST) {
	
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "weight");
	$reqdfieldsn = explode(",", "Weight");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	if ($_POST['weight'] && (!is_numericint($_POST['weight'])
			|| ($_POST['weight'] < 1) || ($_POST['weight'] > 100))) {
		$input_errors[] = "Ȩ�ز�����Ϊ����1-100��������";
	}

	if (!$input_errors) {
		$queue = array();
		
		$queue['targetpipe'] = $_POST['targetpipe'];
		$queue['weight'] = $_POST['weight'];
		if ($_POST['mask'])
			$queue['mask'] = $_POST['mask'];
		$queue['descr'] = $_POST['descr'];
		
		if (isset($id) && $a_queues[$id]) {
			// Scheduler: update matching jobs
			croen_update_job('shaper-set_queue_weight', $a_queues[$id]['descr'], ($queue['descr'] != '' ? $queue['descr'] : FALSE));
			$a_queues[$id] = $queue;
		} else
			$a_queues[] = $queue;
		
		write_config();
		touch($d_shaperconfdirty_path);
		header("Location: firewall_shaper_queues.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if (is_array($config['shaper']['pipe']) && (count($config['shaper']['pipe']) > 0)): ?>
            <form action="firewall_shaper_queues_edit.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
                <tr> 
                  <td valign="top" class="vncellreq">�ܵ�</td>
                  <td class="vtable"><select name="targetpipe" class="formfld">
                      <?php 
					  foreach ($config['shaper']['pipe'] as $pipei => $pipe): ?>
                      <option value="<?=$pipei;?>" <?php if ($pipei == $pconfig['targetpipe']) echo "selected"; ?>> 
                      <?php
					  	echo htmlspecialchars("Pipe " . ($pipei + 1));
						if ($pipe['descr'])
							echo htmlspecialchars(" (" . $pipe['descr'] . ")");
					  ?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br>
                    <span class="vexpl">ѡ�񱾶��йҽӵĹܵ���</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">Ȩ��</td>
                  <td width="78%" class="vtable"><?=$mandfldhtml;?><input name="weight" type="text" id="weight" size="5" value="<?=htmlspecialchars($pconfig['weight']);?>"> 
                    <br> <span class="vexpl">�Ϸ��ķ�ΧΪ��1-100��<br>
                    ���йҽ���ͬһ���ܵ��ϵĶ��н���Ȩ�ر�������ùܵ��Ĵ�����Ȩ�� = �ϸ߱����Ĵ���)�� 
                   <br>ע�⣺Ȩ�ز������ȼ�����ʹ��Ȩ�صĶ��г������������Ŷӣ����ڵ�Ȩ�صĶ����е����ݰ�Ҳ�ܻ�����ǲ��ִ��� </span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">����</td>
                  <td width="78%" class="vtable"> <select name="mask" class="formfld">
                      <option value="" <?php if (!$pconfig['mask']) echo "selected"; ?>>��</option>
                      <option value="source" <?php if ($pconfig['mask'] == "source") echo "selected"; ?>>Դ</option>
                      <option value="destination" <?php if ($pconfig['mask'] == "destination") echo "selected"; ?>>Ŀ��</option>
                    </select> <br> <span class="vexpl">��ѡ���ˡ�Դ����Ŀ�ġ���monowall��Ϊ�������������Ĺܵ���Ȩ�طֱ�Ϊÿһ��Դ��Ŀ��IP����һ����̬���С�</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">����</td>
                  <td width="78%" class="vtable"> <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>"> 
                    <br> <span class="vexpl">�����ڴ�����Щ������Ϣ�Ա��պ�ο������ᱻ��������</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> <input name="Submit" type="submit" class="formbtn" value="����"> 
                    <?php if (isset($id) && $a_queues[$id]): ?>
                    <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>"> 
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<?php else: ?>
<p><strong>������¶���֮ǰ����Ҫ�ȴ���һ���ܵ��Թ��ҽӡ�</strong></p>
<?php endif; ?>
<?php include("fend.inc"); ?>
