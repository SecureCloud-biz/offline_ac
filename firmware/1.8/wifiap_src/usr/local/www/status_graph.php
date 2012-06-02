#!/usr/local/bin/php
<?php 
/*
	$Id: status_graph.php 430 2011-04-03 16:42:21Z awhite $
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

$pgtitle = array("状态信息", "流量图示");
require("guiconfig.inc");

$curif = "wan";
if ($_GET['if'])
	$curif = $_GET['if'];
	
if ($curif == "wan")
	$ifnum = get_real_wan_interface();
elseif ($curif == "SixXS")
	if (isset($config['interfaces']['wan']['aiccu']['ayiya'])) {
		$ifnum = 'tun0';
	} else {
		$ifnum = 'gif0';
	}
else
	$ifnum = $config['interfaces'][$curif]['if'];
?>
<?php include("fbegin.inc"); ?>
<?php
$ifdescrs = array('wan' => 'WAN', 'lan' => 'LAN');
if (ipv6enabled() && ($config['interfaces']['wan']['ipaddr6'] == "aiccu")) {
	$ifdescrs['SixXS'] = 'SixXS';
}
for ($j = 1; isset($config['interfaces']['opt' . $j]); $j++) {
	$ifdescrs['opt' . $j] = $config['interfaces']['opt' . $j]['descr'];
}
?>
<form name="form1" action="" method="get" style="padding-bottom: 10px; margin-bottom: 14px; border-bottom: 1px solid #999999">
接口: 
<select name="if" class="formfld" onchange="document.form1.submit()">
<?php
foreach ($ifdescrs as $ifn => $ifd) {
	echo "<option value=\"$ifn\"";
	if ($ifn == $curif) echo " selected";
	echo ">" . htmlspecialchars($ifd) . "</option>\n";
}
?>
</select>
</form>
<div align="center">
<object data="graph.php?ifnum=<?=htmlspecialchars($ifnum);?>&amp;ifname=<?=rawurlencode($ifdescrs[$curif]);?>" type="image/svg+xml" width="550" height="275">
<param name="src" value="graph.php?ifnum=<?=htmlspecialchars($ifnum);?>&amp;ifname=<?=rawurlencode($ifdescrs[$curif]);?>" />
Your browser does not support the type SVG! You need to either use Firefox or download the Adobe SVG plugin.
</object>
</div>
<br><span class="red"><strong>说明:</strong></span> 如果你看不到图表, 你可能需要安装最新版本的<a href="http://www.mozilla.com/" target="_blank">Firefox</a> 浏览器或者安装<a href="http://www.adobe.com/svg/viewer/install/" target="_blank">Adobe SVG viewer</a>.
<?php include("fend.inc"); ?>
