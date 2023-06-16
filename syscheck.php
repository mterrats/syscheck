#!/usr/bin/php
<?php

/**

Idea and pieces of code from http://www.tecmint.com/linux-server-health-monitoring-script/

**/

error_reporting(0);
$netint = _exec("netstat -i | head -n 3 | tail -n 1 | cut -d ' ' -f1"); // get main network interface
$debug  = true; // let it be :)
$output = "";

if (isset($argv[1])) $log_output = true;

_o(     _hostname()     );
_o(     _is_connected() );
_o(     _ips()          );
_o(     _ns()           );
_o(     _cpu()          );
_o(     _procs()        );
_o(     _load()         );
_o(     _uptime()       );
_o(     _netbw()        );
_o(     _netcon()	);
_o(     _mails()        );
_o(     _logged()       );
_o(     _io()           );
_o(     _ram()          );
_o(     _disks()        );


_fix();

if ($log_output) {

}

echo $output;

// functions

//
function _mails() {
  $cmd = "exim -bpc";
  return "Mail Queue: ".intval(_exec($cmd));
}

//
function _procs() {
  $cmd = "ps aux | grep -v '%CPU' | awk '{print \$1}' | sort | uniq -c | sort -nr | head";
  $tmp = explode("\n",preg_replace("/\s+/"," ",strtolower(_exec($cmd))));
  return "Processes: ".implode(",", $tmp);
}

//
function _io() {
  $cmd = "iostat -x 1 1 | egrep 'idle|\.' | grep -vi linux";
  return "I/0:\n"._exec($cmd)."\n";
}

//
function _cpu() {
  $cmd = "top -b -n1 | grep 'Cpu(s)'";
  return _exec($cmd);
}

//
function _netcon() {
  $cmd = "netstat -anteep | grep -v Recv | awk '{print \$6}' | tr -d ')' | sort | uniq -c | sort -nr";
  return "Connections: ".implode("\,",explode("\n",preg_replace("/\s+/"," ",strtolower(_exec($cmd)))));
}

//
function _netbw() {
  global $netint;
  $in[0]  = _exec("cat /sys/class/net/$netint/statistics/rx_bytes");
  $out[0] = _exec("cat /sys/class/net/$netint/statistics/tx_bytes");
  sleep(1);
  $in[1]  = _exec("cat /sys/class/net/$netint/statistics/rx_bytes");
  $out[1] = _exec("cat /sys/class/net/$netint/statistics/tx_bytes");
  $totali = intval(($in[1]-$in[0])/(1024*1024));
  $totalo = intval(($out[1]-$out[0])/(1024*1024));
  return "Network: $totali MB/s IN , $totalo MB/s OUT";
}

//
function _uptime() {
  $cmd= "uptime | awk -F'[ ,:]+' '{print \$6,\$7\",\",\$8,\"hours,\",\$9,\"minutes\"}'";
  return "Uptime: "._exec($cmd);
}

//
function _load() {
  $cmd = "cat /proc/loadavg | awk '{print \$1,\$2,\$3}'";
  return "Load: "._exec($cmd);
}

//
function _disks() {
  return "Disks:\n"._exec("df -h")."\n";
}

//
function _ram() {
  return "Mem:\n"._exec("free -lm")."\n";
}

//
function _logged() {
  return "Logged:\n"._exec("who")."\n";
}

//
function _ns() {
  $cmd = "grep nameserver /etc/resolv.conf | awk '{print \$2}'";
  $res = _exec($cmd);
  $lines = explode("\n",preg_replace("/\r/","",$res));
  if (is_array($lines)) {
    $res = "";
    foreach ($lines as $line) $res .= trim($line)." ";
    $res = substr($res,0,-1);
  }
  return "NS: ".$res;
}

//
function _ips() {
  $cmd = "hostname -I | awk '{print \$2,\$3,\$4}'";
  return "IPs: "._exec($cmd);
}

//
function _hostname() {
  return "Hostname: "._exec("hostname");
}

//
function _o($buff="") {
  global $output;
  $output .= _p($buff,false);
}

//
function _is_connected() {
  $cmd = "ping -c 1 google.com &> /dev/null && echo 'UP' || echo 'DOWN'";
  return "Connection: "._exec($cmd);
}

//
function _p($output="", $print=true) {
  $line = "[".date("Y-m-d H:i:s")." ".time()."] ";
  if ($print) echo $line . trim($output) . "\n";
  else return "$line".trim($output)."\n";
}

//
function _exec($cmd) {
  return trim(`$cmd`);
}

//
function _fix() {
  global $output;
  // fix some output lines
  $output2 = explode("\n",$output);
  $o3 = "";
  $last = "[.......... ........ .............] ";
  foreach ($output2 as $o2) {
    if (strlen($o2)<1) continue;
    if (!preg_match("/^\[/",$o2)) { $o3 .= $last ."  ". $o2."\n";}
    else {
      $p = explode(" ",$o2);
      $last = "{$p[0]} {$p[1]} {$p[2]} ";
      $o3 .= $o2."\n";
    }
  }
  $output = $o3;
  return $o3;
}
