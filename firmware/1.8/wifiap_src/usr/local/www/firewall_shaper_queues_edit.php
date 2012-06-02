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

$pgtitle = array("防火墙", "流量控制", "编辑队列");
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
		$input_errors[] = "权重参数须为介于1-100的整数。";
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
                  <td valign="top" class="vncellreq">管道</td>
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
                    <span class="vexpl">选择本队列挂接的管道。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">权重</td>
                  <td width="78%" class="vtable"><?=$mandfldhtml;?><input name="weight" type="text" id="weight" size="5" value="<?=htmlspecialchars($pconfig['weight']);?>"> 
                    <br> <span class="vexpl">合法的范围为：1-100。<br>
                    所有挂接在同一个管道上的队列将按权重比例分享该管道的带宽（高权重 = 较高比例的带宽)。 
                   <br>注意：权重不是优先级，即使高权重的队列持续地有数据排队，排在低权重的队列中的数据包也能获得它那部分带宽。 </span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">掩码</td>
                  <td width="78%" class="vtable"> <select name="mask" class="formfld">
                      <option value="" <?php if (!$pconfig['mask']) echo "selected"; ?>>无</option>
                      <option value="source" <?php if ($pconfig['mask'] == "source") echo "selected"; ?>>源</option>
                      <option value="destination" <?php if ($pconfig['mask'] == "destination") echo "selected"; ?>>目的</option>
                    </select> <br> <span class="vexpl">若选择了“源”或“目的”，monowall会为依据上面所给的管道和权重分别为每一个源／目的IP创建一个动态队列。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">描述</td>
                  <td width="78%" class="vtable"> <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>"> 
                    <br> <span class="vexpl">您可在此输入些描述信息以备日后参考（不会被解析）。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> <input name="Submit" type="submit" class="formbtn" value="保存"> 
                    <?php if (isset($id) && $a_queues[$id]): ?>
                    <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>"> 
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<?php else: ?>
<p><strong>在添加新队列之前您需要先创建一个管道以供挂接。</strong></p>
<?php endif; ?>
<?php include("fend.inc"); ?>
