#!/usr/local/bin/php
<?php 
/*
	$Id: diag_defaults.php 211 2007-07-28 13:17:00Z mkasper $
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

$pgtitle = array("状态信息", "恢复出厂设置");

require("guiconfig.inc");

if ($_POST) {
	if ($_POST['Submit'] != " 取消 ") {
		reset_factory_defaults();
		system_reboot();
		$rebootmsg = "系统已被恢复为出厂状态并重启设备，这大概需要一分钟时间";
	} else {
		header("Location: index.php");
		exit;
	}
}
?>
<?php include("fbegin.inc"); ?>
<?php if ($rebootmsg): echo print_info_box($rebootmsg); else: ?>
<form action="diag_defaults.php" method="post">
              <p><strong>假如您点击“确认恢复”，设备将被恢复为出厂设置并立即重新启动。
              	所有系统设置将会被覆盖。本机局域网LAN口IP地址将被设置成192.168.10.1，
              	系统运行DHCP服务，用户名将被设置成“admin”，密码将被设置成“wifiap”。<br>
                <br>
                你确认需要这样做吗？</strong></p>
        <p> 
          <input name="Submit" type="submit" class="formbtn" value=" 确认恢复 ">
          <input name="Submit" type="submit" class="formbtn" value=" 取消 ">
        </p>
      </form>
<?php endif; ?>
<?php include("fend.inc"); ?>
