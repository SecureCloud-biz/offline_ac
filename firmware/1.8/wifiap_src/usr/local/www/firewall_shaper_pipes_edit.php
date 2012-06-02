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

$pgtitle = array("防火墙", "流量控制", "编辑管道");
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
		$input_errors[] = "带宽参数须为整数。";
	}
	if (($_POST['delay'] && !is_numericint($_POST['delay']))) {
		$input_errors[] = "延时参数须为整数。";
	}
	if ($_POST['plr'] && (!is_numeric($_POST['plr']) || $_POST['plr'] < 0 || $_POST['plr'] > 1)) {
		$input_errors[] = "丢包率参数须为介于0-1的小数。";
	}
	if ($_POST['qsize'] && (!is_numericint($_POST['qsize']) || $_POST['qsize'] < 2 || $_POST['qsize'] > 100)) {
		$input_errors[] = "queue的大小须为介于2-100的整数。";
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
                  <td width="22%" valign="top" class="vncellreq">带宽</td>
                  <td width="78%" class="vtable"><?=$mandfldhtml;?><input name="bandwidth" type="text" id="bandwidth" size="5" value="<?=htmlspecialchars($pconfig['bandwidth']);?>"> 
                    &nbsp;Kbit/s</td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">延时</td>
                  <td width="78%" class="vtable"> <input name="delay" type="text" id="delay" size="5" value="<?=htmlspecialchars($pconfig['delay']);?>"> 
                    &nbsp;ms<br> <span class="vexpl">提示：大多数情况下， 
                    这里应设为0（或者留空）。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">丢包率</td>
                  <td width="78%" class="vtable"> <input name="plr" type="text" id="plr" size="5" value="<?=htmlspecialchars($pconfig['plr']);?>"> 
                    <br> <span class="vexpl">提示：大多数情况下，这里应设为0（或者留空）。如果您要做摸拟丢包试验的话，0.001就意味着千分之一的丢包率。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">槽长</td>
                  <td width="78%" class="vtable"> <input name="qsize" type="text" id="qsize" size="8" value="<?=htmlspecialchars($pconfig['qsize']);?>"> 
                    &nbsp;slots槽（单位）<br> 
                    <span class="vexpl">提示：大多数情况下，本栏留空即可。所有进入管道的数据包先被排入
					一个固定长度的数据槽中，然后按上面所给的时延值进行延迟，最后发往目的地。
				对于以太网来说，这个值默认为50slots。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">掩码</td>
                  <td width="78%" class="vtable"> <select name="mask" class="formfld">
                      <option value="" <?php if (!$pconfig['mask']) echo "selected"; ?>>无</option>
                      <option value="source" <?php if ($pconfig['mask'] == "source") echo "selected"; ?>>源</option>
                      <option value="destination" <?php if ($pconfig['mask'] == "destination") echo "selected"; ?>>目的</option>
                    </select> <br>
                    <span class="vexpl">若选择了“源”或“目的”，monowall会依据上面所给的带宽、延时、丢包率和槽长分别为
					每一个源／目的IP创建一个动态的管道。这样，就可以很便捷地为每台主机指定带宽等限定。<br>注意：本功能十分
					实用。
                    </span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">描述</td>
                  <td width="78%" class="vtable"> <input name="descr" type="text" class="formfld" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>"> 
                    <br> <span class="vexpl">您可在此输入些描述信息以备日后参考（不会被解析）。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> <input name="Submit" type="submit" class="formbtn" value="保存"> 
                    <?php if (isset($id) && $a_pipes[$id]): ?>
                    <input name="id" type="hidden" value="<?=htmlspecialchars($id);?>">
                    <?php endif; ?>
                  </td>
                </tr>
              </table>
</form>
<?php include("fend.inc"); ?>
