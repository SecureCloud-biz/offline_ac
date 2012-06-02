#!/usr/local/bin/php
<?php
	if( $_GET['f'] ){
		header ("Expires:1980-1-1"); 
		echo "<pre>";
		echo microtime()."\r\n";
		exec("/sbin/ipfw pipe show",$r );
		echo join("\r\n",$r);
		exit;
	}
	$pgtitle[1]	= "状态信息: 流量查看";
	$omit_nocacheheaders = true;
	require("guiconfig.inc");
	include("fbegin.inc"); 
?>
<style type="text/css">
	div table {font-size:9pt;text-align:right;border-collapse:collapse;color:#307090;}
	tr {height:20;}
	td {padding:2 3 0;}
	.tblbg{background:#e0e0e0;cursor:hand;}
</style>
<script language="JavaScript">
//刷新间隔：毫秒
var renew = 2500;
//超时间隔：毫秒
var too = 10000;
//默认排序字段
var sortby = 1;

var url,http_request;
var renewout,tooout;
var reg1,reg2,reg3,reg4;
var ipArrlen = 5;
var ipArr = new Array(256);
var ms1,ms2;
var countloop = 0;
var tbl1htm;

function initApp() {
	for(var i = 0;i<256;++i){
		ipArr[i]= new Array(ipArrlen);
		for(var j = 0;j<ipArrlen;++j){
			ipArr[i][j] = 0;
		}
	}
	url = location.href + "?f=1";
	tbl1htm="<td align=center onclick='changesort(0)'>IP地址</td><td onclick='changesort(1)'>下载速度(Kbps)↓</td><td onclick='changesort(2)'>上传速度(Kbps)</td><td onclick='changesort(3)'>下载字节(MByte)</td><td onclick='changesort(4)'>上传字节(MByte)</td>"
	if (typeof XMLHttpRequest != 'undefined') {
		http_request = new XMLHttpRequest();
	}
	else {
		try {
			http_request = new ActiveXObject('Msxml2.XMLHTTP');
		} catch (e) {
			try {
				http_request = new ActiveXObject('Microsoft.XMLHTTP');
			} catch (e) {alert("你的浏览器不支持XML，升级吧。");return false;}
		}
	}
	lansubip = getLansubIP();
	reg1 = new RegExp(lansubip      + "\\d+\\.\\d+\\/\\d+ +\\d+ +\\d+","g");
	reg2 = new RegExp("("+ lansubip + "\\d+\\.\\d+)\\/\\d+ +\\d+ +(\\d+)","");
	reg3 = new RegExp(lansubip      + "\\d+\\.\\d+\\/\\d+ +\\d+\\.\\d+\\.\\d+\\.\\d+\\/\\d+ +\\d+ +\\d+","g");
	reg4 = new RegExp("("+ lansubip + "\\d+\\.\\d+)\\/\\d+ +\\d+\\.\\d+\\.\\d+\\.\\d+\\/\\d+ +\\d+ +(\\d+)","");
	ms2 = (new Date()).getTime()/1000;
	getUrl();
}

function getLansubIP() {
	var reg0 = /https?:\/\/[^\/]+\//i;
	http_request.open("GET", location.href.match(reg0)[0] + "interfaces_lan.php", false);
	http_request.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
	http_request.send(null);
	reg0 = /id\=\"ipaddr\".+?value=\"(\d+\.\d+\.)\d+\.\d+\"/;
	http_request.responseText.match(reg0);
	return RegExp.$1;
}

function getUrl() {
	try {
		http_request.open("GET", url, true);
		http_request.setRequestHeader("Content-Type","application/xml");
		http_request.onreadystatechange = function () {
			if (http_request.readyState == 4) {
				window.clearTimeout(tooout);
				window.clearTimeout(renewout);
				if(http_request.status==200) {
					window.status = "处理...";
					creTbl();
				}
				else {
					window.status = "连接超时";
					alert("连接超时！地址错误？");
				}
			}
		}
		window.status = "连接 "+ url;
		http_request.send(null);
	} catch(e) {window.alert(e.description);return;}
	tooout = window.setTimeout(function() {http_request.abort();}, too);
}

function creTbl() {
	var reqTxt,strArr,i,j,sumdown = 0,sumup = 0;
	reqTxt = http_request.responseText;
	var timeArr = reqTxt.substring(5,reqTxt.indexOf("\n")).split(" ");
	ms1 = ms2;
	ms2 = parseFloat(timeArr[1]) + parseFloat(timeArr[0]);
	for(i = 0;i<256;++i) {
		ipArr[i][0] = ipArr[i][1];
		ipArr[i][1] = 0;
		ipArr[i][2] = ipArr[i][3];
		ipArr[i][3] = 0;
	}
	try	{
		strArr = reqTxt.match(reg1);
		for (i = 0;i<strArr.length;++i) {
			strArr[i].match(reg2);
			j = RegExp.$1;
			j = j.substr(j.lastIndexOf(".")+1);
			ipArr[j][1] += parseInt(RegExp.$2);
			ipArr[j][4] = RegExp.$1;
		}
		strArr = reqTxt.match(reg3);
		for (i = 0;i<strArr.length;++i) {
			strArr[i].match(reg4);
			j = RegExp.$1;
			j = j.substr(j.lastIndexOf(".")+1);
			ipArr[j][3] += parseInt(RegExp.$2);
			ipArr[j][4] = RegExp.$1;
		}
	} catch (e){window.alert(e.description);return;}
	strArr = null;
	var arrSort = new Array(256);
	for (i = 1;i<255;++i) {
		arrSort[i] = new Array(5);
		arrSort[i][0] = ipArr[i][4];
		arrSort[i][1] = ipArr[i][1]>ipArr[i][0]?(ipArr[i][1]-ipArr[i][0])/120/((ms2-ms1)):0;
		arrSort[i][2] = ipArr[i][3]>ipArr[i][2]?(ipArr[i][3]-ipArr[i][2])/120/((ms2-ms1)):0;
		arrSort[i][3] = ipArr[i][1];
		arrSort[i][4] = ipArr[i][3];
		sumdown += arrSort[i][1];
		sumup   += arrSort[i][2];
	}
	++countloop;
	if(countloop != 1) {
		if (sortby >= 1)
			arrSort.sort(sortNum);
		else
			arrSort.sort(sortIP);
		strTbl = "<table width=360 border=1><col align=left><col width=60><col width=60><col width=60><col width=60><tr class=tblbg>".concat(tbl1htm,"</tr>");
		var countip=0;
		//254,可修改此数以缩小范围；排序后，[0]排上了数据
		for (i = 0;i<254;++i) {
			if(arrSort[i][1]==0 && arrSort[i][2]==0 && arrSort[i][3]==0 && arrSort[i][4]==0)
				continue;
			++countip;
			var downspeed = Math.ceil(arrSort[i][1]*10)/10;
			var upspeed   = Math.ceil(arrSort[i][2]*10)/10;
			var downMbyte = Math.ceil(arrSort[i][3]/1024/1024*10)/10;
			var upMbyte   = Math.ceil(arrSort[i][4]/1024/1024*10)/10;
			strTbl = strTbl.concat("<tr><td>",arrSort[i][0],"</td><td>",downspeed,"</td><td>",upspeed,"</td><td>",downMbyte,"</td><td>",upMbyte,"</td></tr>\n");
		}
		strTbl = strTbl.concat("</table>\n");
		window.status = "完毕";
		document.getElementById("table2").innerHTML = strTbl;
		var strTbl = "<table width=120 border=1 style='text-align:center;'><tr class=tblbg><td>统计数据</td></tr><tr><td>总下载速度</td></tr><tr><td>"
		strTbl = strTbl.concat(Math.ceil(sumdown*10)/10," Kbps</td></tr><tr><td>总上传速度</td></tr><tr><td>",Math.ceil(sumup*10)/10," Kbps</td></tr><tr><td>刷新时间</td></tr><tr><td>",Math.ceil((ms2-ms1)*100)/100,"s</td><tr><td>目前有 ",countip," 个IP</td></tr></table>");
		document.getElementById("table1").innerHTML = strTbl;
		renewout = window.setTimeout("getUrl()", renew);
	}
	else
		getUrl();
}

function changesort(change){
	sortby=parseInt(change);
	var eve = window.event.srcElement;
	var tds = eve.parentNode.childNodes;
	for(var i=0;i<tds.length;++i)
		tds(i).innerHTML = tds(i).innerHTML.replace("↓","");
	eve.innerHTML += "↓";
	tbl1htm = eve.parentNode.innerHTML;
}

function sortNum(a1,a2){
	return a2[sortby]-a1[sortby];
}

function sortIP(a1,a2){
	var arr1,arr2;
	if (a2[sortby]==0)
		arr2 = new Array(0,0,0,0);
	else
		arr2=a2[sortby].split(".");
	if (a1[sortby]==0)
		arr1 = new Array(0,0,0,0);
	else
		arr1=a1[sortby].split(".");
	return (arr1[2]*256+arr1[3]) - (arr2[2]*256+arr2[3]);
}

window.onscroll = function () {
	if (document.documentElement.scrollTop > 0)
		document.getElementById("table1").style.top = document.documentElement.scrollTop + 136;
	else
		document.getElementById("table1").style.top = document.body.scrollTop + 136
}

document.onkeypress = function () {
	if( window.event.keyCode==13){
		window.clearTimeout(tooout);
		window.clearTimeout(renewout);
		http_request.onreadystatechange = function() {};
		if (http_request.readyState != 0) {
			http_request.abort();
			window.status = "暂停";
		}
		else {
			countloop = 0;
			getUrl();
		}
	}
}

initApp();
</script>
<font style='color:red;'>&nbsp;回车：[暂停/继续] &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 注意:&nbsp;&nbsp;&nbsp;使用此功能需开启<a href="firewall_shaper.php">流量管理</a>功能. </font>
<table>
	<tr>
		<td align="left" valign="top">
			<div id="table2"></div>
		</td>
		<td align="right" valign="top">
			<div id="table1"></div>
		</td>
	</tr>
</table>
<br>
<?php
	include("fend.inc");
?>
