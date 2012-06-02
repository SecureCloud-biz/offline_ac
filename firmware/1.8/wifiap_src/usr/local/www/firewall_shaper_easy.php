#!/usr/local/bin/php
<?php 

$pgtitle = array("����ǽ", "��������", "������");
require("guiconfig.inc");

function wipe_magic () {
  global $config;

  /* wipe previous */
  $types=array("pipe","queue","rule");
  foreach ($types as $type) {
    foreach (array_keys($config['shaper'][$type]) as $num) {
    if (substr($config['shaper'][$type][$num]['descr'],0,2) == "e_") {
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
  
       }

function create_magic ($maxup, $maxdown, $p2plow,$maskq) {
  global $config;

  $config['shaper']['enable'] = TRUE;
  $pipei = 0;
  $queuei = 0;
  $rulei = 0;

  /* Create new pipes */
  $config['shaper']['pipe'][$pipei]['descr'] = "e_�ϴ�";
  $config['shaper']['pipe'][$pipei]['bandwidth'] = round($maxup * .99);
  $config['shaper']['pipe'][$pipei]['qsize'] = 50;
  $config['shaper']['pipe'][$pipei]['mask'] = source;
  $pipei++;
  $config['shaper']['pipe'][$pipei]['descr'] = "e_����";
  $config['shaper']['pipe'][$pipei]['bandwidth'] = round($maxdown * .99);
  $config['shaper']['pipe'][$pipei]['qsize'] = 50;
  $config['shaper']['pipe'][$pipei]['mask'] = destination;
  $pipei++;

  /* Create new queues */


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

  $config['shaper']['rule'][$rulei]['descr'] = "e_�ϴ�";
  $config['shaper']['rule'][$rulei]['targetpipe'] = 0;
  $config['shaper']['rule'][$rulei]['interface'] = "wan";
  $config['shaper']['rule'][$rulei]['direction'] = "out";
  $config['shaper']['rule'][$rulei]['source']['any'] = TRUE;
  $config['shaper']['rule'][$rulei]['destination']['any'] = TRUE;
  $rulei++; 
  $config['shaper']['rule'][$rulei]['descr'] = "e_����";
  $config['shaper']['rule'][$rulei]['targetpipe'] = 1;
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
            $input_errors[] = "��IP�����ٶȱ�����һ������.";
        }
        if (($_POST['maxdown'] && !is_numericint($_POST['maxdown']))) {
            $input_errors[] = "��IP�����ٶȱ�����һ������.";
        }
        if (!$input_errors) {
          if ($_POST['install']) {
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
<form action="firewall_shaper_easy.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (file_exists($d_shaperconfdirty_path)): ?><p>
<?php print_info_box_np("�������������Ѹı䣬<br>�����밴Ӧ��ťʹ֮��Ч��$note");?><br>
<input name="apply" type="submit" class="formbtn" id="apply" value="Ӧ�ø���"></p>
<?php endif; ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
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
     <table width="100%" border="0" cellpadding="6" cellspacing="0"
        <tr valign="top">
          <td width="22%" class="vncellreq">��IP����<br>
            �ٶ� </td>
          <td width="78%" class="vtable">
              <?=$mandfldhtml;?><input name="maxdown" type="text" size="10" value="<?php if ($pconfig['maxdown']) echo $pconfig['maxdown']; ?>"> 
              kbps<br>
              ������������Ҫ���Ƶĵ�IP���������ٶȡ�</td>
		</tr>
        <tr valign="top">
          <td width="22%" class="vncellreq">��IP����<br>
            �ٶ�</td>
          <td width="78%" class="vtable"><?=$mandfldhtml;?><input name="maxup" type="text" size="10" value="<?php if ($pconfig['maxup']) echo $pconfig['maxup']; ?>">
              kbps<br>
              ������������Ҫ���Ƶĵ�IP���������ٶȡ�</td>
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
		��Щ���ÿ��Ա���ÿ̨�����������ȶ��Ĵ��������׳����⡣��������ȫ�������д���
     <br>
		<span class="red"><strong>��������<a href="firewall_shaper_advanced.php">�߼�����</a>�Լ�<a href="firewall_shaper_magic.php">���ؾ���</a>����ͬʱʹ�á� </strong></span></span>
	</td>
    </tr>
</table>
</form>
<?php include("fend.inc"); ?>
