#!/usr/local/bin/php
<?php 
/*
	$Id: firewall_shaper_magic.php 503 2012-04-06 16:16:09Z lgrahl $
    part of wifiAP (http://wifiap.cn)
    
    Copyright (C) 2004 Justin Ellison <justin@techadvise.com> 
    Copyright (C) 2004 Dinesh Nair <dinesh@alphaque.com>

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

$pgtitle = array("����ǽ", "��������", "���ؾ���");
require("guiconfig.inc");

function wipe_magic () {
  global $config;

  /* wipe previous */
  $types=array("pipe","queue","rule");
  foreach ($types as $type) {
    foreach (array_keys($config['shaper'][$type]) as $num) {
    if (substr($config['shaper'][$type][$num]['descr'],0,2) == "m_") {
	  // Scheduler: delete matching jobs
	  croen_update_job(($type == 'rule' ? Array('shaper-enable_rule', 'shaper-disable_rule') : ($type == 'pipe' ? 'shaper-set_pipe_bandwidth' : 'shaper-set_queue_weight')), $config['shaper'][$type][$num]['descr']);
      unset($config['shaper'][$type][$num]);
    }
    }
  }
  /* Although we don't delete user-defined rules, it's probably best to
     disable the shaper to prevent bad things from happening */
  $config['shaper']['enable'] = FALSE;
}

function populate_p2p(&$rulei) {
  global $config;
  
  /* To add p2p clients, push Descr,Protocol,Start,End,src/dest/both onto p2plist */
  $p2plist[] = array('BitTorrent','tcp','6881','6999','both');
  $p2plist[] = array('DirectConnect','','412','412','both');
  $p2plist[] = array('DirectFileExpress','','1044','1045','both');
  $p2plist[] = array('FastTrack','','1214','1214','both');
  $p2plist[] = array('CuteMX','','2340','2340','both');
  $p2plist[] = array('iMest','','4329','4329','both');
  $p2plist[] = array('EDonkey2000','','4661','4665','both');
  $p2plist[] = array('SongSpy','','5190','5190','both');
  $p2plist[] = array('HotlineConnect','','5500','5503','both');
  $p2plist[] = array('Gnutella','','6346','6346','both');
  $p2plist[] = array('dcc','','6666','6668','both');
  $p2plist[] = array('Napster','','6699','6701','both');
  $p2plist[] = array('Aimster','','7668','7668','both');
  $p2plist[] = array('BuddyShare','','7788','7788','both');
  $p2plist[] = array('Scour','','8311','8311','both');
  $p2plist[] = array('OpenNap','','8888','8889','both');
  $p2plist[] = array('hotComm','','28864','28865','both');

  /* Set up/down p2p as lowest weight */
  $direction = array("in","out");
  foreach ($p2plist as $p2pclient) {
   foreach ($direction as $dir) {
     foreach (array('source','destination') as $srcdest) {
       if (($p2pclient[4] == $srcdest) || ($p2pclient[4] == 'both')) { 
         $config['shaper']['rule'][$rulei]['descr'] = "m_P2P $p2pclient[0]";
         $config['shaper']['rule'][$rulei]['interface'] = "wan";
	     $config['shaper']['rule'][$rulei]['direction'] = "$dir";
         $config['shaper']['rule'][$rulei]['source']['any'] = 1;
         $config['shaper']['rule'][$rulei]['destination']['any'] = 1;
         $config['shaper']['rule'][$rulei][$srcdest]['port'] = $p2pclient[2]."-".$p2pclient[3];
         if($p2pclient[1] != '')
           $config['shaper']['rule'][$rulei]['protocol'] = $p2pclient[1];
         if ($dir == "out") {
           $config['shaper']['rule'][$rulei]['targetqueue'] = 4;
         } else {
           $config['shaper']['rule'][$rulei]['targetqueue'] = 6;
         }
         $rulei++;
       }
     }
   }
  }
}

function create_magic ($maxup, $maxdown, $p2plow,$maskq) {
  global $config;

  $config['shaper']['enable'] = TRUE;
  $pipei = 0;
  $queuei = 0;
  $rulei = 0;

  /* Create new pipes */
  $config['shaper']['pipe'][$pipei]['descr'] = "m_���ϴ�";
  $config['shaper']['pipe'][$pipei]['bandwidth'] = round($maxup * .90);
  $pipei++;
  $config['shaper']['pipe'][$pipei]['descr'] = "m_������";
  $config['shaper']['pipe'][$pipei]['bandwidth'] = round($maxdown * .95);
  $pipei++;

  /* Create new queues */
  $config['shaper']['queue'][$queuei]['descr'] = "m_�����ȼ��ϴ� #1";
  $config['shaper']['queue'][$queuei]['targetpipe'] = 0;
  $config['shaper']['queue'][$queuei]['weight'] = 50;
  $queuei++;
  $config['shaper']['queue'][$queuei]['descr'] = "m_�����ȼ��ϴ� #2";
  $config['shaper']['queue'][$queuei]['targetpipe'] = 0;
  $config['shaper']['queue'][$queuei]['weight'] = 30;
  $queuei++;
  $config['shaper']['queue'][$queuei]['descr'] = "m_�����ȼ��ϴ� #3";
  $config['shaper']['queue'][$queuei]['targetpipe'] = 0;
  $config['shaper']['queue'][$queuei]['weight'] = 15;
  $queuei++;
  $config['shaper']['queue'][$queuei]['descr'] = "m_��ͨ�ϴ�";
  $config['shaper']['queue'][$queuei]['targetpipe'] = 0;
  $config['shaper']['queue'][$queuei]['weight'] = 4;
  $queuei++;
  $config['shaper']['queue'][$queuei]['descr'] = "m_P2P �ϴ�";
  $config['shaper']['queue'][$queuei]['targetpipe'] = 0;
  $config['shaper']['queue'][$queuei]['weight'] = 1;
  $queuei++;
  $config['shaper']['queue'][$queuei]['descr'] = "m_��ͨ����";
  $config['shaper']['queue'][$queuei]['targetpipe'] = 1;
  $config['shaper']['queue'][$queuei]['weight'] = 30;
  $queuei++;
  $config['shaper']['queue'][$queuei]['descr'] = "m_P2P ����";
  $config['shaper']['queue'][$queuei]['targetpipe'] = 1;
  $config['shaper']['queue'][$queuei]['weight'] = 10;
  $queuei++;
  $config['shaper']['queue'][$queuei]['descr'] = "m_�����ȼ�����";
  $config['shaper']['queue'][$queuei]['targetpipe'] = 1;
  $config['shaper']['queue'][$queuei]['weight'] = 60;
  $queuei++;
  if ($maskq) {
  	for ($i = 0; $i < $queuei; $i++) {
	    if (stristr($config['shaper']['queue'][$i]['descr'],"upload")) {
			$config['shaper']['queue'][$i]['mask'] = 'source';
	    } else if (stristr($config['shaper']['queue'][$i]['descr'],"download")) {
			$config['shaper']['queue'][$i]['mask'] = 'destination';
	    }
	}
  }

  /* Create new rules */
  if ($p2plow) 
    populate_p2p($rulei);

  $config['shaper']['rule'][$rulei]['descr'] = "m_TCP ACK �ϴ�";
  $config['shaper']['rule'][$rulei]['targetqueue'] = 2;
  $config['shaper']['rule'][$rulei]['interface'] = "wan";
  $config['shaper']['rule'][$rulei]['direction'] = "out";
  $config['shaper']['rule'][$rulei]['source']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['destination']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['iplen'] = "0-80";
  $config['shaper']['rule'][$rulei]['protocol'] = "tcp";
  $config['shaper']['rule'][$rulei]['tcpflags'] = "ack";
  $rulei++; 
  $config['shaper']['rule'][$rulei]['descr'] = "m_С���ϴ�";
  $config['shaper']['rule'][$rulei]['targetqueue'] = 0;
  $config['shaper']['rule'][$rulei]['interface'] = "wan";
  $config['shaper']['rule'][$rulei]['direction'] = "out";
  $config['shaper']['rule'][$rulei]['source']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['destination']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['iplen'] = "0-100";
  $rulei++;
  $config['shaper']['rule'][$rulei]['descr'] = "m_DNS ��ѯ";
  $config['shaper']['rule'][$rulei]['targetqueue'] = 0;
  $config['shaper']['rule'][$rulei]['interface'] = "wan";
  $config['shaper']['rule'][$rulei]['direction'] = "out";
  $config['shaper']['rule'][$rulei]['source']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['destination']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['destination']['port'] = 53;
  $config['shaper']['rule'][$rulei]['protocol'] = "udp";
  $rulei++;
  $config['shaper']['rule'][$rulei]['descr'] = "m_AH �ϴ�";
  $config['shaper']['rule'][$rulei]['targetqueue'] = 0;
  $config['shaper']['rule'][$rulei]['interface'] = "wan";
  $config['shaper']['rule'][$rulei]['direction'] = "out";
  $config['shaper']['rule'][$rulei]['source']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['destination']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['protocol'] = "ah";
  $rulei++;
  $config['shaper']['rule'][$rulei]['descr'] = "m_ESP �ϴ�";
  $config['shaper']['rule'][$rulei]['targetqueue'] = 0;
  $config['shaper']['rule'][$rulei]['interface'] = "wan";
  $config['shaper']['rule'][$rulei]['direction'] = "out";
  $config['shaper']['rule'][$rulei]['source']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['destination']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['protocol'] = "esp";
  $rulei++;
  $config['shaper']['rule'][$rulei]['descr'] = "m_GRE �ϴ�";
  $config['shaper']['rule'][$rulei]['targetqueue'] = 0;
  $config['shaper']['rule'][$rulei]['interface'] = "wan";
  $config['shaper']['rule'][$rulei]['direction'] = "out";
  $config['shaper']['rule'][$rulei]['source']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['destination']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['protocol'] = "gre";
  $rulei++;
  $config['shaper']['rule'][$rulei]['descr'] = "m_ICMP �ϴ�";
  $config['shaper']['rule'][$rulei]['targetqueue'] = 1;
  $config['shaper']['rule'][$rulei]['interface'] = "wan";
  $config['shaper']['rule'][$rulei]['direction'] = "out";
  $config['shaper']['rule'][$rulei]['source']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['destination']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['protocol'] = "icmp";
  $rulei++;
  $config['shaper']['rule'][$rulei]['descr'] = "m_�����ϴ�";
  $config['shaper']['rule'][$rulei]['targetqueue'] = 3;
  $config['shaper']['rule'][$rulei]['interface'] = "wan";
  $config['shaper']['rule'][$rulei]['direction'] = "out";
  $config['shaper']['rule'][$rulei]['source']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['destination']['any'] = TRUE;
  $rulei++;
  $config['shaper']['rule'][$rulei]['descr'] = "m_ICMP ����";
  $config['shaper']['rule'][$rulei]['targetqueue'] = 7;
  $config['shaper']['rule'][$rulei]['interface'] = "wan";
  $config['shaper']['rule'][$rulei]['direction'] = "in";
  $config['shaper']['rule'][$rulei]['source']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['destination']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['protocol'] = "icmp";
  $rulei++;
  $config['shaper']['rule'][$rulei]['descr'] = "m_С������";
  $config['shaper']['rule'][$rulei]['targetqueue'] = 7;
  $config['shaper']['rule'][$rulei]['interface'] = "wan";
  $config['shaper']['rule'][$rulei]['direction'] = "in";
  $config['shaper']['rule'][$rulei]['source']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['destination']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['iplen'] = "0-100";
  $rulei++;
  $config['shaper']['rule'][$rulei]['descr'] = "m_AH ����";
  $config['shaper']['rule'][$rulei]['targetqueue'] = 7;
  $config['shaper']['rule'][$rulei]['interface'] = "wan";
  $config['shaper']['rule'][$rulei]['direction'] = "in";
  $config['shaper']['rule'][$rulei]['source']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['destination']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['protocol'] = "ah";
  $rulei++;
  $config['shaper']['rule'][$rulei]['descr'] = "m_ESP ����";
  $config['shaper']['rule'][$rulei]['targetqueue'] = 7;
  $config['shaper']['rule'][$rulei]['interface'] = "wan";
  $config['shaper']['rule'][$rulei]['direction'] = "in";
  $config['shaper']['rule'][$rulei]['source']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['destination']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['protocol'] = "esp";
  $rulei++;
  $config['shaper']['rule'][$rulei]['descr'] = "m_GRE ����";
  $config['shaper']['rule'][$rulei]['targetqueue'] = 7;
  $config['shaper']['rule'][$rulei]['interface'] = "wan";
  $config['shaper']['rule'][$rulei]['direction'] = "in";
  $config['shaper']['rule'][$rulei]['source']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['destination']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['protocol'] = "gre";
  $rulei++;
  $config['shaper']['rule'][$rulei]['descr'] = "m_��������";
  $config['shaper']['rule'][$rulei]['targetqueue'] = 5;
  $config['shaper']['rule'][$rulei]['interface'] = "wan";
  $config['shaper']['rule'][$rulei]['direction'] = "in";
  $config['shaper']['rule'][$rulei]['source']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['destination']['any'] = TRUE;
  $rulei++;
}

if (!is_array($config['shaper']['rule'])) {
    $config['shaper']['rule'] = array();
}
if (!is_array($config['shaper']['pipe'])) {
    $config['shaper']['pipe'] = array();
}
if (!is_array($config['shaper']['queue'])) {
    $config['shaper']['queue'] = array();
}

$a_shaper = &$config['shaper']['rule'];
$a_queues = &$config['shaper']['queue'];
$a_pipes = &$config['shaper']['pipe'];

$pconfig['p2plow'] = isset($config['shaper']['magic']['p2plow']);
$pconfig['maskq'] = isset($config['shaper']['magic']['maskq']);
$pconfig['maxup'] = $config['shaper']['magic']['maxup'];
$pconfig['maxdown'] = $config['shaper']['magic']['maxdown'];

if ($_POST) {

    if ($_POST['install']) {
        unset($input_errors);
        $pconfig = $_POST;
        $reqdfields = explode(" ", "maxup maxdown");
        $reqdfieldsn = explode(",", "Max. Upload,Max.Download");
        do_input_validation($_POST,$reqdfields, $reqdfieldsn, &$input_errors);
        if (($_POST['maxup'] && !is_numericint($_POST['maxup']))) {
            $input_errors[] = "�����ٶȱ�����һ��������";
        }
        if (($_POST['maxdown'] && !is_numericint($_POST['maxdown']))) {
            $input_errors[] = "�����ٶȱ�����һ��������";
        }
        if (!$input_errors) {
          if ($_POST['install']) {
		     // Scheduler: delete matching jobs
		     croen_update_job(Array('shaper-enable_rule', 'shaper-disable_rule', 'shaper-set_pipe_bandwidth', 'shaper-set_queue_weight'));
	     	 unset ($config['shaper']);
             create_magic($_POST['maxup'],$_POST['maxdown'],$_POST['p2plow']?TRUE:FALSE,$_POST['maskq']?TRUE:FALSE);
             touch($d_shaperconfdirty_path);
          }
          $config['shaper']['magic']['p2plow'] = $_POST['p2plow'] ? TRUE : FALSE;
          $config['shaper']['magic']['maskq'] = $_POST['maskq'] ? TRUE : FALSE;
          $config['shaper']['magic']['maxup'] = $_POST['maxup'];
          $config['shaper']['magic']['maxdown'] = $_POST['maxdown'];
          write_config();
        }
    }
    if ($_POST['remove']) {
		wipe_magic();
		$note = '<p><span class="red"><strong>˵�����������ƹ��ܱ��رա�<br>�����������й��򣯹ܵ����������ö�����������</strong></span><strong><br>';
		touch($d_shaperconfdirty_path);
		write_config();
    }
    if ($_POST['apply']) {
        $retval = 0;
        if (!file_exists($d_sysrebootreqd_path)) {
            config_lock();
            $retval = shaper_configure();
            config_unlock();
        }
        $savemsg = get_std_save_message($retval);
        if ($retval == 0) {
            if (file_exists($d_shaperconfdirty_path))
                unlink($d_shaperconfdirty_path);
        }
    }
}

?>
<?php include("fbegin.inc"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<form action="firewall_shaper_magic.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_shaperconfdirty_path)): ?><p>
<?php print_info_box_np("�������������Ѹı䣬<br>�����밴Ӧ��ťʹ֮��Ч��$note");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Ӧ�ø���"></p>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
  <tr><td class="tabnavtbl">
  <ul id="tabnav">
<?php 
   	$tabs = array('����' => 'firewall_shaper.php',
           		  '�ܵ�' => 'firewall_shaper_pipes.php',
           		  '����' => 'firewall_shaper_queues.php',
           		  '������' => 'firewall_shaper_easy.php',
				  '�߼�����' => 'firewall_shaper_advanced.php',
           		  '���ؾ���' => 'firewall_shaper_magic.php');
	dynamic_tab_menu($tabs);
?>       
  </ul>
  </td></tr>
  <tr> 
    <td class="tabcont">
     <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
		<tr> 
		  <td width="22%" valign="top" class="vtable">&nbsp;</td>
		  <td width="78%" class="vtable">
			  <input name="p2plow" type="checkbox" id="p2plow" value="yes" <?php if ($pconfig['p2plow']) echo "checked";?>>
			  ��P2P������Ϊ��͵����ȼ�</td>
		</tr>
		<tr> 
		  <td width="22%" valign="top" class="vtable">&nbsp;</td>
		  <td width="78%" class="vtable">
			  <input name="maskq" type="checkbox" id="maskq" value="yes" <?php if ($pconfig['maskq']) echo "checked";?>>
			  �ھ�������ƽ���ط������</td>
		</tr>
        <tr valign="top">
          <td width="22%" class="vncellreq">����<br>
            �ٶ� </td>
          <td width="78%" class="vtable">
              <?=$mandfldhtml;?><input name="maxdown" type="text" size="10" value="<?php if ($pconfig['maxdown']) echo htmlspecialchars($pconfig['maxdown']); ?>"> 
              kbps<br>
              �������������WAN���������ٶȡ�</td>
		</tr>
        <tr valign="top">
          <td width="22%" class="vncellreq">����<br>
            �ٶ�</td>
          <td width="78%" class="vtable"><?=$mandfldhtml;?><input name="maxup" type="text" size="10" value="<?php if ($pconfig['maxup']) echo $pconfig['maxup']; ?>">
              kbps<br>
              ��������������WAN���������ٶȡ�</td>
		</tr>
		<tr> 
		  <td width="22%">&nbsp;</td>
		  <td width="78%">
		        <input name="install" type="submit" class="formbtn" id="install" value="��װ/����"> 
		      &nbsp;
			    <input name="remove" type="submit" class="formbtn" id="remove" value="���">
		  <br><br>
		    <span class="red"><strong>һ�������¡���װ�����¡���ť���������е���������<strong>���򣯹ܵ�������</strong>���ö����ᱻ�����������ȥ֮ǰ��Ϊ�����������ñ��ݣ� </strong></span></td>
		</tr>
	  </table><br>
		<span class="vexpl"><span class="red"><strong>˵����</strong></span><strong><br>
		</strong>�������������к������ٶȲ��������¡���װ�����¡���ť�����ؾ����Ϊ��������ѵĹ��򣯹ܵ������С�
		��Щ���ûᱣ֤����������������������ʱ��ά��һ���ɽ��ܵĽ�����Ӧ�ٶȡ�
		<br>
		<span class="red"><strong>��������<a href="firewall_shaper_easy.php">������</a>�Լ�<a href="firewall_shaper_advanced.php">�߼�����</a>����ͬʱʹ�á� </strong></span></span>
	</td>
    </tr>
</table>
</form>
<?php include("fend.inc"); ?>
