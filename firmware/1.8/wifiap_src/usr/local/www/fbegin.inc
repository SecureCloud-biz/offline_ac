<?php
/*
	$Id: fbegin.inc 492 2012-01-30 13:10:33Z mkasper $
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

$navlevelsep = ": ";	/* navigation level separator string */
$mandfldhtml = "";		/* display this before mandatory input fields */
$mandfldhtmlspc = "";	/* same as above, but with spacing */

function gentitle($title) {
	global $navlevelsep;
	return join($navlevelsep, $title);
}

function genhtmltitle($title) {
	global $config;
	return $config['system']['hostname'] . "." . $config['system']['domain'] . " - " . gentitle($title);
}

function dynamic_menu(&$menu, $menu_title) {
	global $allowed, $config;

	// Make sure the admin can see everything
	if ($_SERVER['REMOTE_USER'] === $config['system']['username']) {
		unset($allowed);
   		foreach ($menu as $item) {
        			$allowed[] = $item[0];
		}
	}

	// Workaround for IPv6 pages
	if (ipv6enabled() && in_array('firewall_rules.php', $allowed))
		$allowed[] = 'firewall_rules.php?type=ipv6';
	if (ipv6enabled() && in_array('system_routes.php', $allowed))
		$allowed[] = 'system_routes.php?type=ipv6';
	
	foreach ($menu as $desc => $links) {
		if ($intersect = array_intersect($menu[$desc],$allowed)) {
			if (!isset($header_displayed) && !($menu_title=='Diagnostics')) {
				echo "\n			</td></tr><tr><td style='font-size:13px; color:#FFFFFF; font-weight:bold;' background='topbg3.gif' align='center'>".$menu_title.'</td><tr><td>';
				$header_displayed = true;
			}
			else { 
				if (!isset($header_displayed)) {
					if (isset($config['system']['webgui']['expanddiags']) || strstr($_SERVER['SCRIPT_FILENAME'], "diag_") || strstr($_SERVER['SCRIPT_FILENAME'], "reboot")): ?>
					<a href="javascript:showhide('diag','tri_diag')"><img src="/tri_c.gif" id="tri_diag" width="14" height="10" border="0"></a><strong><a href="javascript:showhide('diag','tri_diag')" class="navlnk">���</a></strong><br>
					<span id="diag">
					<?php else: ?>
					<a href="javascript:showhide('diag','tri_diag')"><img src="/tri_o.gif" id="tri_diag" width="14" height="10" border="0"></a><strong><a href="javascript:showhide('diag','tri_diag')" class="navlnk">���</a></strong><br>
					<span id="diag" style="display: ">
					<?php endif;
					$header_displayed = true;			  
				}
				
			}
			// $intersect may contain one or more links, but we only need the first one
			// BUT, we are doing a foreach here because it might not be element [0]
			foreach ($intersect as $link) { 
				// Below lines are spaced out oddly to help improve the look of resulting HTML code.
				?>

				&nbsp;&nbsp;&nbsp;&nbsp;<img src="/topbg1.gif" />&nbsp;<a href="/<?=$link?>" class="navlnk"><?=$desc?></a><br><?
	   			// Only need the first one, so break out of foreach
	   			break;
			}
		}
	}
}
        
// Check for issues when changing the web admin username
// $allowed is set in auth.inc *IF* the user is not the webGUI admin
// so, if it's not set here, they were the admin user when they submited the admin username change.
if ((!is_array($allowed)) and 
    ($_SERVER['PHP_SELF'] == '/system.php') and 
    ($_SERVER['REMOTE_USER'] != $config['system']['username'])) {
	// webGUI username was just changed, let us change what PHP sees the remote user as so
	// the menu will build properly.  When user hits next link, they will be prompted to reauthenticate.
	$_SERVER['REMOTE_USER'] = $config['system']['username'];	
   
}

//$menu['System']['General setup'] = array('system.php');
//if (ipv6enabled()) {
//	$menu['System']['IPv4 Static routes'] = array('system_routes.php');
//	$menu['System']['IPv6 Static routes'] = array('system_routes.php?type=ipv6');
//} else {
//	$menu['System']['Static routes'] = array('system_routes.php');
//}
//$menu['System']['Firmware']      = array('system_firmware.php');
//$menu['System']['Advanced']      = array('system_advanced.php');
//if ($_SERVER['REMOTE_USER'] === $config['system']['username']) {
//	$menu['System']['User manager']  = array('system_usermanager.php');
//}
//else {
//	$menu['System']['User password']  = array('system_usermanager.php');
//}

if (ipv6enabled()) {
	$menu['Firewall']['IPv4����']          = array('firewall_rules.php');
	$menu['Firewall']['IPv6����']          = array('firewall_rules.php?type=ipv6');
} else {
	$menu['Firewall']['����']          = array('firewall_rules.php');
}
$menu['Firewall']['NAT']            = array('firewall_nat.php',
											'firewall_nat_server.php',
							  				'firewall_nat_1to1.php',
							  				'firewall_nat_out.php');
$menu['Firewall']['��������'] = array('firewall_shaper.php',
											'firewall_shaper_pipes.php',
							  				'firewall_shaper_queues.php',
											'firewall_shaper_magic.php');
//$menu['Firewall']['Aliases']        = array('firewall_aliases.php');

//$menu['Services']['DNS forwarder']	= array('services_dnsmasq.php');
//$menu['Services']['Dynamic DNS'] 	= array('services_dyndns.php');
$menu['Services']['web��֤'] = array('services_captiveportal.php');
$menu['Services']['wifiAP������']	= array('services_wifiap.php');
$menu['Services']['DHCP����']	= array('services_dhcp.php');
if (ipv6enabled()) {
	$menu['Services']['IPv4��̬·��'] = array('system_routes.php');
	$menu['Services']['IPv6��̬·��'] = array('system_routes.php?type=ipv6');
} else {
	$menu['Services']['��̬·��'] = array('system_routes.php');
}
//$menu['Services']['DHCP relay'] 	= array('services_dhcp_relay.php');
//$menu['Services']['SNMP']  			= array('services_snmp.php');
//$menu['Services']['Proxy ARP']  	= array('services_proxyarp.php');
//$menu['Services']['Captive portal'] = array('services_captiveportal.php',
//											'services_captiveportal_mac.php',
//							                'services_captiveportal_ip.php',
//			                  				'services_captiveportal_users.php',
//							  				'services_captiveportal_elementmanager.php');
//$menu['Services']['Wake on LAN']  	= array('services_wol.php');
//$menu['Services']['�ƻ�����']  	= array('services_croen.php');

//$menu['VPN']['IPsec']  				= array('vpn_ipsec.php',
//											'vpn_ipsec_mobile.php',
//							  				'vpn_ipsec_keys.php',
//											'vpn_ipsec_ca.php');
//$menu['VPN']['PPTP']  				= array('vpn_pptp.php',
//											'vpn_pptp_users.php');
//											
//$menu['Status']['System']			= array('index.php');
$menu['Status']['����ӿ�']		= array('status_interfaces.php');

$menu['Status']['����ͼʾ']	= array('status_graph.php');
//$menu['Status']['����']			= array('status_wireless.php');
if (isset($config['captiveportal']['enable'])) {
	$menu['Status']['��֤״̬']	= array('status_captiveportal.php');
}
//���Ŀ¼
$menu['Status']['DHCP��Լ'] 	= array('diag_dhcp_leases.php');
//$menu['Diagnostics']['IPsec'] 			= array('diag_ipsec_sad.php',
//												'diag_ipsec_spd.php');
//$menu['Diagnostics']['Ping/Traceroute'] = array('diag_ping.php',
//												'diag_traceroute.php');
$menu['Diagnostics']['ARP״̬��'] 		= array('diag_arp.php');
$menu['Status']['�û�����״̬']      = array('ipshow.php');
//$menu['Diagnostics']['����ǽ״̬'] = array('diag_ipfstat.php');
//$menu['Status']['IP����״̬'] = array('diag_ipfstat_gather.php');
//$menu['Diagnostics']['����״̬��'] 	= array('diag_resetstate.php');
$menu['Status']['���ݣ���ԭ'] 	= array('diag_backup.php');
$menu['Status']['��������']= array('diag_defaults.php');
$menu['Status']['�̼�����']= array('system_firmware.php');
$menu['Status']['����ϵͳ'] 	= array('reboot.php');

// Add Extensions, if they exist
if (is_dir("{$g['www_path']}/ext")){
      $dh = @opendir("{$g['www_path']}/ext");
      if ($dh) {
        while (($extd = readdir($dh)) !== false) {
          if (($extd === ".") || ($extd === "..") || ($extd === "index.html"))
            continue;
          $extfiles=explode("\n",trim(file_get_contents("{$g['www_path']}/ext/" . $extd . "/menu2.inc")));
          $menu['Extensions'][array_shift($extfiles)]=$extfiles;
         }
        closedir($dh);
      }
}

//$menu['Diagnostics']['Logs']			= array('diag_logs.php',
//												'diag_logs_filter.php',
//							  					'diag_logs_dhcp.php',
//							  					'diag_logs_portal.php',
//							  					'diag_logs_vpn.php',
//							  					'diag_logs_settings.php');  
//$menu['Diagnostics']['DHCP Leases'] 	= array('diag_dhcp_leases.php');
//$menu['Diagnostics']['CPU Graph']		= array('status_graph_cpu.php');
//$menu['Diagnostics']['IPsec'] 			= array('diag_ipsec_sad.php',
//												'diag_ipsec_spd.php');
//$menu['Diagnostics']['Ping/Traceroute'] = array('diag_ping.php',
//												'diag_traceroute.php');
//if (ipv6enabled()){
//$menu['Diagnostics']['ARP/NDP Table'] 		= array('diag_arp.php');
//} else {
//$menu['Diagnostics']['ARP Table'] 		= array('diag_arp.php');
//}
//$menu['Diagnostics']['Firewall states'] = array('diag_ipfstat.php');
//$menu['Diagnostics']['Reset state'] 	= array('diag_resetstate.php');
//$menu['Diagnostics']['Backup/Restore'] 	= array('diag_backup.php');
//$menu['Diagnostics']['Factory Defaults']= array('diag_defaults.php');
//$menu['Diagnostics']['Reboot system'] 	= array('reboot.php');

@$licinfos=file("/tmp/licinfo");
if($licinfos!=null && strlen($licinfos[0])>0){
	$licinfo = $licinfos[0];
}else{
	$licinfo = '';
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?=genhtmltitle($pgtitle);?></title>
<meta http-equiv="Content-Type" content="text/html; charset=gb2312">
<link href="gui.css" rel="stylesheet" type="text/css">
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
</head>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<script type="text/javascript">
<!--
var tri_open = "";
var tri_closed = "";

window.onload = preload;

function preload() {
	if (document.images) {
		tri_open = new Image(14,10);
		tri_closed = new Image(14,10);
		tri_open.src = "/tri_o.gif";
		tri_closed.src = "/tri_c.gif";
	}
}

function showhide(tspan, tri) {
	tspanel = document.getElementById(tspan);
	triel = document.getElementById(tri);
	if (tspanel.style.display == 'none') {
		tspanel.style.display = '';
		triel.src = "/tri_o.gif";
	} else {
		tspanel.style.display = 'none';
		triel.src = "/tri_c.gif";
	}
}
-->
</script>
<table width="750" border="0" cellspacing="0" cellpadding="2" align="center">
  <tr valign="bottom"> 
    <td width="150" height="65" align="center" valign="middle" background="logo.gif">
    	<!--strong><a href="./"><img src="/logo.gif" border="0"></a></strong-->
    </td>
    <td width="5" background="topbg.gif"></td>
    <td height="65" background="topbg.gif">
	<table border="0" cellspacing="0" cellpadding="0" width="100%">
	<tr><td align="left" valign="bottom"><span class="tfrtitle">&nbsp;WEB�������� <?php if(strlen($licinfo)>0){ ?>[<?=$licinfo ?>] <?php } ?></span></td>
	  <td align="right" valign="bottom">
	  <span class="hostname"><?=$config['system']['hostname'] . "." . $config['system']['domain'];?>&nbsp;</span>
	  </td></tr></table>
	</td>
  </tr>
  <tr><td height="3"></td></tr>
  <tr valign="top"> 
    <td width="150" bgcolor="#FFFFFF">
			<table width="100%" border="0" cellpadding="6" cellspacing="0">
        <tr>
					<td height="26" background="topbg0.gif"></td>
				</tr>
	      <tr>
	        <td>
	        	<span class="navlnk"><font color="#011B68"> 
            <?php 
	            echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='/index.php' class='navlnk'>��ҳ</a>";
							//dynamic_menu($menu['System'], 'ϵͳ');
            
// The Interfaces menu is different than the rest because of the Assign link and
// The dynamic nature of the optional interfaces.
$if_menu = array('(assign)' => 'interfaces_assign.php',
    			 'LAN' => 'interfaces_lan.php',
			  	 'WAN' => 'interfaces_wan.php',
			     'OPTx' => 'interfaces_opt.php');
if ($_SERVER['REMOTE_USER'] === $config['system']['username']) {
	$allowed = $if_menu;
}
if (array_intersect($if_menu,$allowed)) {
								echo '</td></tr><tr><td style="font-size:13px; color:#FFFFFF; font-weight:bold;" background="topbg3.gif" align="center">��������';
	if ((!isset($config['system']['webgui']['noassigninterfaces'])) && 
	    (in_array('interfaces_assign.php',$allowed))) {
									echo '<a href="/interfaces_assign.php" class="navlnks"> <font color="#FFFFFF">(����)</a></a></td></tr><tr><td>';
	}
							    //echo '<br>';
									//$menu['System']['��������'] = array('system.php');
									echo '&nbsp;&nbsp;&nbsp;&nbsp;<img src="/topbg1.gif" />&nbsp;<a href="/system.php" class="navlnk">��������</a><br>';
    if (in_array('interfaces_lan.php',$allowed)) {
							    	echo '&nbsp;&nbsp;&nbsp;&nbsp;<img src="/topbg1.gif" />&nbsp;<a href="/interfaces_lan.php" class="navlnk">����LAN</a><br>';
    }
    if (in_array('interfaces_wan.php',$allowed)) {
							    	echo '&nbsp;&nbsp;&nbsp;&nbsp;<img src="/topbg1.gif" />&nbsp;<a href="/interfaces_wan.php" class="navlnk">����WAN</a><br>';
    }
    if (in_array('interfaces_opt.php',$allowed)) {
    	for ($i = 1; isset($config['interfaces']['opt' . $i]); $i++) {
										echo '&nbsp;&nbsp;&nbsp;&nbsp;<img src="/topbg1.gif" />&nbsp;<a href="/interfaces_opt.php?index=' .$i.
			     '" class="navlnk">'.htmlspecialchars($config['interfaces']['opt' . $i]['descr']).'</a><br>';
    	}
    }
}             
							dynamic_menu($menu['Services'], '�߼�����');
							dynamic_menu($menu['Firewall'], '����ǽ');
							//dynamic_menu($menu['VPN'], 'VPNר��');
							dynamic_menu($menu['Status'], '״̬��Ϣ');
							// If Extensions exist, add them to the menu
							if (isset($menu['Extensions'])) 
							    dynamic_menu($menu['Extensions'], '��չ');
							//dynamic_menu($menu['Diagnostics'], 'Diagnostics');
?>
			  </span>
			  </font></span>
            </td>
	      </tr>
	    </table>
	  </td>
    <td bgcolor="#C7E1F8"></td>
	  <td width="600" bgcolor="#FFFFFF">
	  	<table width="100%" border="0" cellpadding="10" cellspacing="0">
				<tr>
					<td height="26" background="topbg2.gif"></td>
				</tr>
        <tr><td>
<?php if (!$pgtitle_omit): ?>
      <p class="pgtitle"><?=gentitle($pgtitle);?></p>
<?php endif; ?>
