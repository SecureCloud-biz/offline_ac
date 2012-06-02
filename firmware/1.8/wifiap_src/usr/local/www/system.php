#!/usr/local/bin/php
<?php 
/*
	$Id: system.php 430 2011-04-03 16:42:21Z awhite $
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

$pgtitle = array("基本设置", "系统");
require("guiconfig.inc");

$pconfig['enableipv6'] = isset($config['system']['enableipv6']);
$pconfig['hostname'] = $config['system']['hostname'];
$pconfig['domain'] = $config['system']['domain'];
list($pconfig['dns1'],$pconfig['dns2'],$pconfig['dns3']) = $config['system']['dnsserver'];
$pconfig['dnsallowoverride'] = isset($config['system']['dnsallowoverride']);
$pconfig['username'] = $config['system']['username'];
if (!$pconfig['username'])
	$pconfig['username'] = "admin";
$pconfig['webguiproto'] = $config['system']['webgui']['protocol'];
if (!$pconfig['webguiproto'])
	$pconfig['webguiproto'] = "http";
$pconfig['webguiport'] = $config['system']['webgui']['port'];
$pconfig['timezone'] = $config['system']['timezone'];
$pconfig['timeupdateinterval'] = $config['system']['time-update-interval'];
$pconfig['timeservers'] = $config['system']['timeservers'];

if (!isset($pconfig['timeupdateinterval']))
	$pconfig['timeupdateinterval'] = 300;
if (!$pconfig['timezone'])
	$pconfig['timezone'] = "Etc/UTC";
if (!$pconfig['timeservers'])
	$pconfig['timeservers'] = "pool.ntp.org";
	
function is_timezone($elt) {
	return !preg_match("/\/$/", $elt);
}

exec('/usr/bin/tar -tzf /usr/share/zoneinfo.tgz', $timezonelist);
$timezonelist = array_filter($timezonelist, 'is_timezone');
sort($timezonelist);

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = split(" ", "hostname domain username");
	$reqdfieldsn = split(",", "Hostname,Domain,Username");
	
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	
	if ($_POST['hostname'] && !is_hostname($_POST['hostname'])) {
		$input_errors[] = "主机名只能使用a-z, 0-9 and '-'";
	}
	if ($_POST['domain'] && !is_domain($_POST['domain'])) {
		$input_errors[] = "域名只能使用a-z, 0-9, '-' and '.'.";
	}
	if (($_POST['dns1'] && !is_ipaddr4or6($_POST['dns1'])) || ($_POST['dns2'] && !is_ipaddr4or6($_POST['dns2'])) || ($_POST['dns3'] && !is_ipaddr4or6($_POST['dns3']))) {
		$input_errors[] = "DNS服务器只能输入有效的IP地址。";
	}
	if ($_POST['username'] && !preg_match("/^[a-zA-Z0-9]*$/", $_POST['username'])) {
		$input_errors[] = "用户名只能使用a-z, A-Z and 0-9.";
	}
	if ($_POST['webguiport'] && (!is_numericint($_POST['webguiport']) || 
			($_POST['webguiport'] < 1) || ($_POST['webguiport'] > 65535))) {
		$input_errors[] = "必须输入有效的端口号（1~65535）。";
	}
	if (($_POST['password']) && ($_POST['password'] != $_POST['password2'])) {
		$input_errors[] = "两次密码不匹配。";
	}
	if ($_POST['password'] && strpos($_POST['password'], ":") !== false) {
		$input_errors[] = "密码不能包含冒号(:)。";
	}
	
	$t = (int)$_POST['timeupdateinterval'];
	if (($t < 0) || (($t > 0) && ($t < 6)) || ($t > 1440)) {
		$input_errors[] = "时间更新间隔只能输入0（禁用）或6~1440之间的数字。";
	}
	foreach (explode(' ', $_POST['timeservers']) as $ts) {
		if (!is_domain($ts)) {
			$input_errors[] = "NTP时间服务器只能使用a-z, 0-9, '-' and '.'";
		}
	}

	if (!$input_errors) {
		$config['system']['hostname'] = strtolower($_POST['hostname']);
		$config['system']['domain'] = strtolower($_POST['domain']);
		$oldwebguiproto = $config['system']['webgui']['protocol'];
		$config['system']['username'] = $_POST['username'];
		$config['system']['webgui']['protocol'] = $pconfig['webguiproto'];
		$oldwebguiport = $config['system']['webgui']['port'];
		$config['system']['webgui']['port'] = $pconfig['webguiport'];
		$config['system']['timezone'] = $_POST['timezone'];
		$config['system']['timeservers'] = strtolower($_POST['timeservers']);
		$config['system']['time-update-interval'] = $_POST['timeupdateinterval'];
		
		unset($config['system']['dnsserver']);
		if ($_POST['dns1'])
			$config['system']['dnsserver'][] = $_POST['dns1'];
		if ($_POST['dns2'])
			$config['system']['dnsserver'][] = $_POST['dns2'];
		if ($_POST['dns3'])
			$config['system']['dnsserver'][] = $_POST['dns3'];
		
		$olddnsallowoverride = $config['system']['dnsallowoverride'];
		$config['system']['dnsallowoverride'] = $_POST['dnsallowoverride'] ? true : false;
		$config['system']['enableipv6'] = $_POST['enableipv6'] ? true : false;
		
		if ($_POST['password']) {
			$config['system']['password'] = crypt($_POST['password']);
		}	
		
		$savemsgadd = "";
		/* when switching from HTTP to HTTPS, check if there's a user-specific certificate;
		   if not, auto-generate one */
		if ($config['system']['webgui']['protocol'] == "https" && $oldwebguiproto != $config['system']['webgui']['protocol']) {
			if (!$config['system']['webgui']['certificate']) {
				$ck = generate_self_signed_cert("wifiAP", $config['system']['hostname'] . "." . $config['system']['domain']);
				
				if ($ck === false) {
					$savemsgadd .= "<br><br>请先到 <a href=\"system_advanced.php\">高级配置</a> 页面设置系统的安全证书和私有密钥。";
				} else {
					$config['system']['webgui']['certificate'] = base64_encode($ck['cert']);
					$config['system']['webgui']['private-key'] = base64_encode($ck['key']);
					$savemsgadd .= "<br><br>HTTPS安全证书和私有密钥已经自动生成。";
				}
			}
		}
		
		write_config();
		
		if (($oldwebguiproto != $config['system']['webgui']['protocol']) ||
			($oldwebguiport != $config['system']['webgui']['port']))
			touch($d_sysrebootreqd_path);
		
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			$retval = system_hostname_configure();
			$retval |= system_hosts_generate();
			$retval |= system_resolvconf_generate();
			$retval |= system_password_configure();
			$retval |= services_dnsmasq_configure();
			$retval |= system_timezone_configure();
 			$retval |= system_ntp_configure();
 			
 			if ($olddnsallowoverride != $config['system']['dnsallowoverride'])
 				$retval |= interfaces_wan_configure();
 			
			config_unlock();
		}
		
		$savemsg = get_std_save_message($retval) . $savemsgadd;
	}
}
?>
<?php include("fbegin.inc"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
		<form action="system.php" method="post">
              <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
                  <tr> 
                  <td width="22%" valign="top" class="vncellreq">主机名</td>
                  <td width="78%" class="vtable"><?=$mandfldhtml;?><input name="hostname" type="text" class="formfld" id="hostname" size="40" value="<?=htmlspecialchars($pconfig['hostname']);?>"> 
                    <br> <span class="vexpl">设备名称/AP控制器(AC)名称<br>
                    e.g. <em>wifiAP</em></span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncellreq">域名</td>
                  <td width="78%" class="vtable"><?=$mandfldhtml;?><input name="domain" type="text" class="formfld" id="domain" size="40" value="<?=htmlspecialchars($pconfig['domain']);?>"> 
                    <br> <span class="vexpl">e.g. <em>mydomain.com</em> </span></td>
                </tr>
                 <tr style="display:none;"> 
                  <td width="22%" valign="top" class="vncell">IPv6支持</td>
                  <td width="78%" class="vtable"> 
                    <input name="enableipv6" type="checkbox" id="enableipv6" value="yes" <?php if ($pconfig['enableipv6']) echo "checked"; ?>>
                    <strong>启用IPv6</strong><br>
                    启用IPv6支持后IPv6地址在LAN和WAN接口显示，你可以添加IPv6防火墙规则.<br>
                    提示：你必须设备LAN接口的IPv6地址，并把设备运行于IPv6的网络上。
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">DNS服务器</td>
                  <td width="78%" class="vtable">
                      <input name="dns1" type="text" class="formfld" id="dns1" size="20" value="<?=htmlspecialchars($pconfig['dns1']);?>">
                      <br>
                      <input name="dns2" type="text" class="formfld" id="dns2" size="20" value="<?=htmlspecialchars($pconfig['dns2']);?>">
                      <br>
                      <input name="dns3" type="text" class="formfld" id="dns3" size="20" value="<?=htmlspecialchars($pconfig['dns3']);?>">
                      <br>
                      <span class="vexpl">请输入IP地址，此地址同时应用到DHCP服务、DNS转发和PPTP VPN客户端。<br>
                      <br>
                      <input name="dnsallowoverride" type="checkbox" id="dnsallowoverride" value="yes" <?php if ($pconfig['dnsallowoverride']) echo "checked"; ?>>
                      <strong>允许WAN/PPPoE接口获取的DNS服务器覆盖此配置。</span></td>
                </tr>
                <tr> 
                  <td valign="top" class="vncell">用户名</td>
                  <td class="vtable"> <input name="username" type="text" class="formfld" id="username" size="20" value="<?=htmlspecialchars($pconfig['username']);?>">
                    <br>
                     <span class="vexpl">访问WEB管理界面的用户名。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">密码</td>
                  <td width="78%" class="vtable"> <input name="password" type="password" class="formfld" id="password" size="20"> 
                    <br> <input name="password2" type="password" class="formfld" id="password2" size="20"> 
                    &nbsp;(重复) <br> <span class="vexpl">访问WEB管理界面的密码</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">WEB管理界面协议</td>
                  <td width="78%" class="vtable"> <input name="webguiproto" type="radio" value="http" <?php if ($pconfig['webguiproto'] == "http") echo "checked"; ?>>
                    HTTP &nbsp;&nbsp;&nbsp; <input type="radio" name="webguiproto" value="https" <?php if ($pconfig['webguiproto'] == "https") echo "checked"; ?>>
                    HTTPS</td>
                </tr>
                <tr> 
                  <td valign="top" class="vncell">访问端口</td>
                  <td class="vtable"> <input name="webguiport" type="text" class="formfld" id="webguiport" size="5" value="<?=htmlspecialchars($pconfig['webguiport']);?>"> 
                    <br>
                    <span class="vexpl">输入一下指定的端口数（留空即默认HTTP 80，HTTPS 443）。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">时区</td>
                  <td width="78%" class="vtable"> <select name="timezone" id="timezone">
                      <?php foreach ($timezonelist as $value): ?>
                      <option value="<?=htmlspecialchars($value);?>" <?php if ($value == $pconfig['timezone']) echo "selected"; ?>> 
                      <?=htmlspecialchars($value);?>
                      </option>
                      <?php endforeach; ?>
                    </select> <br> <span class="vexpl">选择本地对应的时区</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">时间更新间隔</td>
                  <td width="78%" class="vtable"> <input name="timeupdateinterval" type="text" class="formfld" id="timeupdateinterval" size="4" value="<?=htmlspecialchars($pconfig['timeupdateinterval']);?>"> 
                    <br> <span class="vexpl">单位：分钟；默认值：300；输入0即禁用</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top" class="vncell">NTP时间服务器</td>
                  <td width="78%" class="vtable"> <input name="timeservers" type="text" class="formfld" id="timeservers" size="40" value="<?=htmlspecialchars($pconfig['timeservers']);?>"> 
                    <br> <span class="vexpl">多个服务器之间用空格分开，如输入域名请确认DNS有效。</span></td>
                </tr>
                <tr> 
                  <td width="22%" valign="top">&nbsp;</td>
                  <td width="78%"> <input name="Submit" type="submit" class="formbtn" value="保存"> 
                  </td>
                </tr>
              </table>
</form>
<?php include("fend.inc"); ?>
