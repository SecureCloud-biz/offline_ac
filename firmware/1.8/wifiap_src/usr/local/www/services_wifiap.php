#!/usr/local/bin/php
<?php 
/*
	$Id: reboot.php 72 2006-02-10 11:13:01Z jdegraeve $
	part of wifiAP (http://wifiap.cn)
	
	Copyright (C) 2003-2006 Manuel Kasper <mk@neon1.net>.
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

$pgtitle = array("高级服务", "wifiAP云管理中心");
require("guiconfig.inc");

//if ($_POST) {
//	if ($_POST['Submit'] == "是") {
//		system_reboot();
//		$rebootmsg = "系统将重新启动，视硬件配置不同，耗时约1分钟。";
//	} else {
//		header("Location: index.php");
//		exit;
//	}
//}
?>
<?php include("fbegin.inc"); ?>
<iframe src="http://c.<?=$g['wifiapdomain'] ?>/wifiap_mo_center.php?lic=<?=$g['wifiap_key'] ?>" width="550px" height="330px" scrolling="no" marginwidth="0" marginheight="0" frameborder="0">你的浏览器不支持iframe标签，请更换浏览器再试。</iframe>
<?php include("fend.inc"); ?>
