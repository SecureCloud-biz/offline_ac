#!/usr/local/bin/php
<?php 
/*
    $Id: status_captiveportal.php 411 2010-11-12 12:58:55Z mkasper $
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

$pgtitle = array("״̬��Ϣ", "�û���֤");
require("guiconfig.inc");
?>
<?php include("fbegin.inc"); ?>
<?php

if ($_GET['act'] == "del") {
    captiveportal_disconnect_client($_GET['id'],6);
}

flush();

function clientcmp($a, $b) {
    global $order;
    return strcmp($a[$order], $b[$order]);
}

$cpdb = array();
captiveportal_lock();
$fp = @fopen("{$g['vardb_path']}/captiveportal.db","r");

if ($fp) {
    while (!feof($fp)) {
        $line = trim(fgets($fp));
        if ($line) {
            $cpent = explode(",", $line);
            $volume = getVolume($cpent[1]);
            $cpent[7] = $volume['output_bytes'];
            $cpent[8] = $volume['input_bytes'];
            if ($_GET['showact'])
                $cpent[9] = captiveportal_get_last_activity($cpent[1]);
            $cpdb[] = $cpent;
        }
    }

    fclose($fp);

    if ($_GET['order']) {
        if ($_GET['order'] == "ip")
            $order = 2;
        else if ($_GET['order'] == "mac")
            $order = 3;
        else if ($_GET['order'] == "user")
            $order = 4;
        else if ($_GET['order'] == "download")
            $order = 7;
        else if ($_GET['order'] == "upload")
            $order = 8;
        else if ($_GET['order'] == "lastact")
            $order = 9;
        else
            $order = 0;
        usort($cpdb, "clientcmp");
    }
}
captiveportal_unlock();
?>

<?php if (isset($config['voucher']['enable'])): ?>
<form action="status_captiveportal.php" method="post" enctype="multipart/form-data" name="iform" id="iform">
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
<tr><td class="tabnavtbl">
<ul id="tabnav">
<?php 
$tabs = array('Users' => 'status_captiveportal.php',
        'Active Vouchers' => 'status_captiveportal_vouchers.php',
        'Voucher Rolls' => 'status_captiveportal_voucher_rolls.php',
        'Test Vouchers' => 'status_captiveportal_test.php');
    dynamic_tab_menu($tabs);
?> 
</ul>
</td></tr>
<tr>
<td class="tabcont">
<?php endif; ?>

<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
  <tr>
    <td class="listhdrr"><a href="?order=ip&amp;showact=<?=htmlspecialchars($_GET['showact']);?>">IP ��ַ</a></td>
    <td class="listhdrr"><a href="?order=mac&amp;showact=<?=htmlspecialchars($_GET['showact']);?>">MAC ��ַ</a></td>
    <td class="listhdrr"><a href="?order=start&amp;showact=<?=htmlspecialchars($_GET['showact']);?>">���ε�¼ʼ��</a></td>
    <td class="listhdrr"><a href="?order=download&amp;showact=<?=htmlspecialchars($_GET['showact']);?>">����</a></td>
    <td class="listhdrr"><a href="?order=upload&amp;showact=<?=htmlspecialchars($_GET['showact']);?>">�ϴ�</a></td>
    <?php if ($_GET['showact']): ?>
    <td class="listhdrr"><a href="?order=lastact&amp;showact=<?=htmlspecialchars($_GET['showact']);?>">�����</a></td>
    <?php endif; ?>
    <td class="listhdr"><a href="?order=user&amp;showact=<?=htmlspecialchars($_GET['showact']);?>">�û���</a></td>
    <td class="list"></td>
  </tr>
<?php foreach ($cpdb as $cpent): ?>
  <tr>
    <td class="listlr"><?=$cpent[2];?></td>
    <td class="listr"><?=$cpent[3];?>&nbsp;</td>
    <td class="listr"><?=htmlspecialchars(date("m/d/Y H:i:s", $cpent[0]));?></td>
    <td class="listr"><?=format_bytes($cpent[7]);?></td>
    <td class="listr"><?=format_bytes($cpent[8]);?></td>
    <?php if ($_GET['showact']): ?>
    <td class="listr"><?php if ($cpent[9]) echo htmlspecialchars(date("m/d/Y H:i:s", $cpent[9]));?></td>
    <?php endif; ?>
    <td class="listr"><?=$cpent[4];?>&nbsp;</td>
    <td valign="middle" class="list" nowrap>
    <a href="?order=<?=htmlspecialchars($_GET['order']);?>&amp;showact=<?=htmlspecialchars($_GET['showact']);?>&amp;act=del&amp;id=<?=$cpent[1];?>" onclick="return confirm('��ȷ��Ҫ�Ͽ����û���������')"><img src="x.gif" title="�Ͽ����û�" width="17" height="17" border="0" alt="�Ͽ����û�"></a></td>
  </tr>
<?php endforeach; ?>
</table>

<?php if (isset($config['voucher']['enable'])): ?>
</td>
</tr>
</table>
</form>
<?php endif; ?>

<p><!-- TODO: paragraph is not valid here -->
<form action="status_captiveportal.php" method="GET">
<input type="hidden" name="order" value="<?=htmlspecialchars($_GET['order']);?>">
<?php if ($_GET['showact']): ?>
<input type="hidden" name="showact" value="0">
<input type="submit" class="formbtn" value="����ʾ���ʱ��">
<?php else: ?>
<input type="hidden" name="showact" value="1">
<input type="submit" class="formbtn" value="��ʾ���ʱ��">
<?php endif; ?>
</form>
</p>
<?php include("fend.inc"); ?>
