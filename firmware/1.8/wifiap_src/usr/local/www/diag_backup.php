#!/usr/local/bin/php
<?php 
/*
	$Id: diag_backup.php 238 2008-01-21 18:33:33Z mkasper $
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

$pgtitle = array("状态信息", "备份/恢复");

/* omit no-cache headers because it confuses IE with file downloads */
$omit_nocacheheaders = true;
require("guiconfig.inc"); 

if ($_POST) {

	unset($input_errors);
	
	if (stristr($_POST['Submit'], "恢复配置文件"))
		$mode = "restore";
	else if (stristr($_POST['Submit'], "下载配置文件"))
		$mode = "download";
		
	if ($mode) {
		if ($mode == "download") {
			config_lock();
			
			$fn = "config-" . $config['system']['hostname'] . "." . 
				$config['system']['domain'] . "-" . date("YmdHis") . ".xml";
			
			$fs = filesize($g['conf_path'] . "/config.xml");
			header("Content-Type: application/octet-stream"); 
			header("Content-Disposition: attachment; filename=$fn");
			header("Content-Length: $fs");
			readfile($g['conf_path'] . "/config.xml");
			config_unlock();
			exit;
		} else if ($mode == "restore") {
			if (is_uploaded_file($_FILES['conffile']['tmp_name'])) {
				if (config_install($_FILES['conffile']['tmp_name']) == 0) {
					system_reboot();
					$savemsg = "配置文件已经恢复，设备正在重启。";
				} else {
					$errstr = "配置文件有误，不能恢复。";
					if ($xmlerr)
						$errstr .= " (XML 错误: $xmlerr)";
					$input_errors[] = $errstr;
				}
			} else {
				$input_errors[] = "配置文件有误，不能恢复（文件上传出错）。";
			}
		}
	}
}
?>
<?php include("fbegin.inc"); ?>
            <form action="diag_backup.php" method="post" enctype="multipart/form-data">
            <?php if ($input_errors) print_input_errors($input_errors); ?>
            <?php if ($savemsg) print_info_box($savemsg); ?>
              <table width="100%" border="0" cellspacing="0" cellpadding="6" summary="inner content pane">
                <tr> 
                  <td colspan="2" class="listtopic">备份配置</td>
                </tr>
                <tr> 
                  <td width="22%" valign="baseline" class="vncell">&nbsp;</td>
                  <td width="78%" class="vtable"> 
                    点此按钮下载系统配置文件（XML格式）<br>
                      <br>
                      <input name="Submit" type="submit" class="formbtn" id="download" value="下载配置文件"></td>
                </tr>
                <tr> 
                  <td colspan="2" class="list" height="12"></td>
                </tr>
                <tr> 
                  <td colspan="2" class="listtopic">恢复配置</td>
                </tr>
                <tr> 
                  <td width="22%" valign="baseline" class="vncell">&nbsp;</td>
                  <td width="78%" class="vtable"> 
                    点击“浏览”按钮选择一个XML格式的配置文件，然后点下面按钮上载以恢复配置<br>
                      <br>
                      <strong><span class="red">注意:</span></strong><br>
                      在恢复配置后,设备将重新启动<br>
                      <br>
                      <input name="conffile" type="file" class="formfld" id="conffile" size="40">
                      <br>
                      <br>
                      <input name="Submit" type="submit" class="formbtn" id="restore" value="恢复配置文件">
                  </td>
                </tr>
              </table>
            </form>
<?php include("fend.inc"); ?>
