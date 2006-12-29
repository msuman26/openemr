<?php
 // Copyright (C) 2006 Rod Roark <rod@sunsetsystems.com>
 //
 // This program is free software; you can redistribute it and/or
 // modify it under the terms of the GNU General Public License
 // as published by the Free Software Foundation; either version 2
 // of the License, or (at your option) any later version.

 require_once("../globals.php");
 require_once("$srcdir/acl.inc");

 $faxstats = array(
  'B' => 'Blocked',
  'D' => 'Sent successfully',
  'F' => 'Failed',
  'P' => 'Pending',
  'R' => 'Send in progress',
  'S' => 'Sleeping',
  'T' => 'Suspended',
  'W' => 'Waiting',
 );

 $mlines = array();
 $dlines = array();
 $slines = array();

 if ($GLOBALS['hylafax_server']) {
  // Get the recvq entries, parse and sort by filename.
  $statlines = array();
  exec("faxstat -r -l -h " . $GLOBALS['hylafax_server'], $statlines);
  foreach ($statlines as $line) {
   // This gets pagecount, sender, time, filename.  We are expecting the
   // string to start with "-rw-rw-" so as to exclude faxes not yet fully
   // received, for which permissions are "-rw----".
   if (preg_match('/^-r\S\Sr\S\S\s+(\d+)\s+\S+\s+(.+)\s+(\S+)\s+(\S+)\s*$/', $line, $matches)) {
    $mlines[$matches[4]] = $matches;
   }
  }
  ksort($mlines);

  // Get the doneq entries, parse and sort by job ID
  /* for example:
  JID  Pri S  Owner Number       Pages Dials     TTS Status
  155  123 D nobody 6158898622    1:1   5:12
  153  124 D nobody 6158896439    1:1   4:12
  154  124 F nobody 6153551807    0:1   4:12         No carrier detected
  */
  $donelines = array();
  exec("faxstat -s -d -l -h " . $GLOBALS['hylafax_server'], $donelines);
  foreach ($donelines as $line) {
   // This gets jobid, priority, statchar, owner, phone, pages, dials and tts/status.
   if (preg_match('/^(\d+)\s+(\d+)\s+(\S)\s+(\S+)\s+(\S+)\s+(\d+:\d+)\s+(\d+:\d+)(.*)$/', $line, $matches)) {
    $dlines[$matches[1]] = $matches;
   }
  }
  ksort($dlines);
 }

 $scandir = $GLOBALS['scanner_output_directory'];
 if ($scandir) {
  // Get the directory entries, parse and sort by date and time.
  $dh = opendir($scandir);
  if (! $dh) die("Cannot read $scandir");
  while (false !== ($sfname = readdir($dh))) {
   if (substr($sfname, 0, 1) == '.') continue;
   $tmp = stat("$scandir/$sfname");
   $tmp[0] = $sfname; // put filename in slot 0 which we don't otherwise need
   $slines[$tmp[9] . $tmp[1]] = $tmp; // key is file mod time and inode number
  }
  closedir($dh);
  ksort($slines);
 }

?>
<html>

<head>

<link rel=stylesheet href='<? echo $css_header ?>' type='text/css'>
<title><? xl('Received Faxes','e'); ?></title>

<style>
td {
 font-family: Arial, Helvetica, sans-serif;
 padding-left: 4px;
 padding-right: 4px;
}
a, a:visited, a:hover {
 color:#0000cc;
}
tr.head {
 font-size:10pt;
 background-color:#cccccc;
 font-weight: bold;
}
tr.detail {
 font-size:10pt;
}
td.tabhead {
  font-size: 11pt;
  font-weight: bold;
  height: 20pt;
  text-align: center;
}
</style>

<script type="text/javascript" src="../../library/dialog.js"></script>

<script language="JavaScript">

// Process click on a tab.
function tabclick(tabname) {
 var tabs = new Array('faxin', 'faxout', 'scanin');
 var visdisp = document.getElementById('bigtable').style.display;
 for (var i in tabs) {
  var thistd    = document.getElementById('td_tab_' + tabs[i]);
  var thistable = document.getElementById('table_' + tabs[i]);
  if (tabs[i] == tabname) {
   // thistd.style.borderBottom = '0px solid #000000';
   thistd.style.borderBottom = '2px solid transparent';
   thistd.style.color = '#cc0000';
   thistd.style.cursor = 'default';
   thistable.style.display = visdisp;
  } else {
   thistd.style.borderBottom = '2px solid #000000';
   thistd.style.color = '#777777';
   thistd.style.cursor = 'pointer';
   thistable.style.display = 'none';
  }
 }
}

// Callback from popups to refresh this display.
function refreshme() {
 location.reload();
}

// Process click on filename to view.
function dodclick(ffname) {
 cascwin('fax_view.php?file=' + ffname, '_blank', 600, 475,
  "resizable=1,scrollbars=1");
 return false;
}

// Process click on Job ID to view.
function dojclick(jobid) {
 cascwin('fax_view.php?jid=' + jobid, '_blank', 600, 475,
  "resizable=1,scrollbars=1");
 return false;
}

// Process scanned document filename to view.
function dosvclick(sfname) {
 cascwin('fax_view.php?scan=' + sfname, '_blank', 600, 475,
  "resizable=1,scrollbars=1");
 return false;
}

// Process click to pop up the fax dispatch window.
function domclick(ffname) {
 dlgopen('fax_dispatch.php?file=' + ffname, '_blank', 850, 550);
}

// Process click to pop up the scanned document dispatch window.
function dosdclick(sfname) {
 dlgopen('fax_dispatch.php?scan=' + sfname, '_blank', 850, 550);
}

</script>

</head>

<body <?echo $top_bg_line;?>>
<table cellspacing='0' cellpadding='0' style='margin: 0 0 0 0; border: 2px solid #000000;'
 id='bigtable' width='100%' height='100%'>
 <tr style='height: 20px;'>
  <td width='33%' id='td_tab_faxin'  class='tabhead'
   <?php if ($GLOBALS['hylafax_server']) { ?>
   style='color: #cc0000; border-right: 2px solid #000000; border-bottom: 2px solid transparent;'
   <?php } else { ?>
   style='color: #777777; border-right: 2px solid #000000; border-bottom: 2px solid #000000; cursor: pointer;'
   <?php } ?>
   onclick='tabclick("faxin")'>Faxes In</td>
  <td width='33%' id='td_tab_faxout' class='tabhead'
   style='color: #777777; border-right: 2px solid #000000; border-bottom: 2px solid #000000; cursor: pointer;'
   onclick='tabclick("faxout")'>Faxes Out</td>
  <td width='34%' id='td_tab_scanin' class='tabhead'
   <?php if ($GLOBALS['hylafax_server']) { ?>
   style='color: #777777; border-bottom: 2px solid #000000; cursor: pointer;'
   <?php } else { ?>
   style='color: #cc0000; border-bottom: 2px solid transparent;'
   <?php } ?>
   onclick='tabclick("scanin")'>Scanner In</td>
 </tr>
 <tr>
  <td colspan='3' style='padding: 5px;' valign='top'>

   <form method='post' action='faxq.php'>

   <table width='100%' cellpadding='1' cellspacing='2' id='table_faxin'
    <?php if (!$GLOBALS['hylafax_server']) echo "style='display:none;'"; ?>>
    <tr class='head'>
     <td colspan='2' title='Click to view'><? xl('Document','e'); ?></td>
     <td><? xl('Received','e'); ?></td>
     <td><? xl('From','e'); ?></td>
     <td align='right'><? xl('Pages','e'); ?></td>
    </tr>
<?
 $encount = 0;
 foreach ($mlines as $matches) {
  ++$encount;
  $ffname = $matches[4];
  $ffbase = basename("/$ffname", '.tif');
  $bgcolor = "#" . (($encount & 1) ? "ddddff" : "ffdddd");
  echo "    <tr class='detail' bgcolor='$bgcolor'>\n";
  echo "     <td onclick='dodclick(\"$ffname\")'>";
  echo "<a href='fax_view.php?file=$ffname' onclick='return false'>$ffbase</a></td>\n";
  echo "     <td onclick='domclick(\"$ffname\")'>";
  echo "<a href='fax_dispatch.php?file=$ffname' onclick='return false'>Dispatch</a></td>\n";
  echo "     <td>" . htmlentities($matches[3]) . "</td>\n";
  echo "     <td>" . htmlentities($matches[2]) . "</td>\n";
  echo "     <td align='right'>" . htmlentities($matches[1]) . "</td>\n";
  echo "    </tr>\n";
 }
?>
   </table>

   <table width='100%' cellpadding='1' cellspacing='2' id='table_faxout'
    style='display:none;'>
    <tr class='head'>
     <td title='Click to view'><? xl('Job ID','e'); ?></td>
     <td><? xl('To','e'); ?></td>
     <td><? xl('Pages','e'); ?></td>
     <td><? xl('Dials','e'); ?></td>
     <td><? xl('TTS','e'); ?></td>
     <td><? xl('Status','e'); ?></td>
    </tr>
<?
 $encount = 0;
 foreach ($dlines as $matches) {
  ++$encount;
  $jobid = $matches[1];
  $ffstatus = $faxstats[$matches[3]];
  $fftts = '';
  $ffstatend = trim($matches[8]);
  if (preg_match('/^(\d+:\d+)\s*(.*)$/', $ffstatend, $tmp)) {
   $fftts = $tmp[1];
   $ffstatend = $tmp[2];
  }
  if ($ffstatend) $ffstatus .= ': ' . $ffstatend;
  $bgcolor = "#" . (($encount & 1) ? "ddddff" : "ffdddd");
  echo "    <tr class='detail' bgcolor='$bgcolor'>\n";
  echo "     <td onclick='dojclick(\"$jobid\")'>" .
       "<a href='fax_view.php?jid=$jobid' onclick='return false'>" .
       "$jobid</a></td>\n";
  echo "     <td>" . htmlentities($matches[5]) . "</td>\n";
  echo "     <td>" . htmlentities($matches[6]) . "</td>\n";
  echo "     <td>" . htmlentities($matches[7]) . "</td>\n";
  echo "     <td>" . htmlentities($fftts)      . "</td>\n";
  echo "     <td>" . htmlentities($ffstatus)   . "</td>\n";
  echo "    </tr>\n";
 }
?>
   </table>

   <table width='100%' cellpadding='1' cellspacing='2' id='table_scanin'
    <?php if ($GLOBALS['hylafax_server']) echo "style='display:none;'"; ?>>
    <tr class='head'>
     <td colspan='2' title='Click to view'><? xl('Filename','e'); ?></td>
     <td><? xl('Scanned','e'); ?></td>
     <td align='right'><? xl('Length','e'); ?></td>
    </tr>
<?
 $encount = 0;
 foreach ($slines as $sline) {
  ++$encount;
  $bgcolor = "#" . (($encount & 1) ? "ddddff" : "ffdddd");
  $sfname = $sline[0]; // filename
  $sfdate = date('Y-m-d H:i', $sline[9]);
  echo "    <tr class='detail' bgcolor='$bgcolor'>\n";
  echo "     <td onclick='dosvclick(\"$sfname\")'>" .
       "<a href='fax_view.php?scan=$sfname' onclick='return false'>" .
       "$sfname</a></td>\n";
  echo "     <td onclick='dosdclick(\"$sfname\")'>";
  echo "<a href='fax_dispatch.php?scan=$sfname' onclick='return false'>Dispatch</a></td>\n";
  echo "     <td>$sfdate</td>\n";
  echo "     <td align='right'>" . $sline[7] . "</td>\n";
  echo "    </tr>\n";
 }
?>
   </table>

   </form>

  </td>
 </tr>
</table>
</body>
</html>