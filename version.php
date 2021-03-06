<?php
// Software version identification.
// This is used for display purposes, and also the major/minor/patch
// numbers are stored in the database and used to determine which sql
// upgrade file is the starting point for the next upgrade.
$v_major = '4';
$v_minor = '0';
$v_patch = '0';
$v_tag   = '-dev'; // minor revision number, should be empty for production releases

// Database version identifier, this is to be incremented whenever there
// is a database change in the course of development.  It is used
// internally to determine when a database upgrade is needed.
//
// Please add a comment that includes the database.sql version that relates to 
// change below

$v_database = 7;
// 4) 3_2_0-to-4_0_0_upgrade.sql new revision: 1.31
//    database.sql,v  new revision: 1.180
// 6) 2010-10-29 by sunsetsystems - 1.182 I guess - what are these entries for?
?>
