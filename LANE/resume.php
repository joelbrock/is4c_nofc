<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
 // session_start(); 
if (!function_exists("tDataConnect")) include("connect.php");
if (!function_exists("memberID")) include("prehkeys.php");
if (!function_exists("receipt")) include("clientscripts.php");
if (!function_exists("gohome")) include("maindisplay.php");

// lines 40-45 edited by apbw 7/12/05 to resolve "undefined index" error message

if (isset($_POST["selectlist"])) {
	$resume_trans = strtoupper(trim($_POST["selectlist"]));
}
else {
	$resume_trans = "";
}

if (!$resume_trans || strlen($resume_trans) < 1) gohome();
else {
	$resume_spec = explode("::", $resume_trans);
	$suspendedtoday = "suspendedtoday";
	$suspended = "suspended";

	$register_no = $resume_spec[0];
	$emp_no = $resume_spec[1];
	$trans_no = $resume_spec[2];

	$db_a = tDataConnect();
	$m_conn = mDataConnect();

	resumesuspended($register_no, $emp_no, $trans_no);

/*	

	if ( $_SESSION["standalone"] == 0 && $_SESSION["remoteDBMS"] == "mssql" ) {

		$suspendedtoday = $_SESSION["remoteDB"]."suspendedtoday";
		$suspended = $_SESSION["remoteDB"]."suspended";
	} 


	if ($_SESSION["standalone"] == 0 && $_SESSION["remoteDBMS"] != "mssql" ) {
		
		$m_conn = mDataConnect();
		$db_a = tDataConnect();

		$downloadfile = $_SESSION["downloadPath"]."resume.out";
		if (file_exists($downloadfile)) exec("rm ".$downloadfile);
		$out = "select * into outfile '".$downloadfile."' from suspendedtoday "
			."where register_no = ".$resume_spec[0]
			." and emp_no = ".$resume_spec[1]
			." and trans_no = ".$resume_spec[2];
		if (mysql_query($out, $m_conn)) {
			if (file_exists($downloadfile)) {
				$resume = "load data infile '".$downloadfile."' into table resume";
				if (mysql_query($resume, $db_a)) $suspendedtody = "resume";
			}
		}
	}
	$query = "insert localtemptrans "


		."datetime, register_no, emp_no, trans_no, upc, "
		."description, trans_type, trans_subtype, trans_status, department, quantity, scale, "
		."unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, "
		."discounttype, voided, percentDiscount, ItemQtty, volDiscType, volume, VolSpecial, mixMatch, "
		."matched, card_no "
		."from ".$suspendedtoday." where register_no = ".$resume_spec["0"]
		." and emp_no = ".$resume_spec["1"]." and trans_no = ".$resume_spec["2"];


	$query_del = "delete from ".$suspended." where register_no = ".$resume_spec["0"]." and emp_no = "
		.$resume_spec["1"]." and trans_no = ".$resume_spec["2"];

	$db_a = tDataConnect();
	$m_conn = mDataConnect();

	$query_a = "select * from localtemptrans";
	$result_a = sql_query($query_a, $db_a);
	$num_rows_a = sql_num_rows($result_a);

	if ($num_rows_a == 0) {


		if ($_SESSION["remoteDBMS"] == "mssql") {
			mssql_query($query, $db_a);
			mssql_query($query_del, $db_a);

		}
		else {
			$loadresume = "load data infile '".$downloadfile."' into table localtemptrans";
			mysql_query($loadresume, $db_a);
			mysql_query($query_del, $db_a);
			if ($_SESSION["standalone"] == 0) { mysql_query($query_del, $m_conn); }
		}

	}

*/

	$query_update = sprintf("UPDATE localtemptrans SET register_no = %s, emp_no = %s, trans_no = %s, datetime=now()",
				$_SESSION["laneno"], $_SESSION["CashierNo"], $_SESSION["transno"]);

	sql_query($query_update, $db_a);
	sql_close($db_a);
	getsubtotals();
	$_SESSION["unlock"] = 1;

	if ($_SESSION["memberID"] != 0 && strlen($_SESSION["memberID"]) > 0 && $_SESSION["memberID"]) {
		memberID($_SESSION["memberID"]);
	}

	$_SESSION["msg"] =0;
//	receipt("resume");
	goodbeep();
	gohome();
}

function resumesuspended($register_no, $emp_no, $trans_no) {
	$t_conn = tDataConnect();
	mysql_query("truncate table translog.suspended");
	$output = "";
	openlog("is4c_connect", LOG_PID | LOG_PERROR, LOG_LOCAL0);
	
	$loadQ = sprintf('mysqldump -u %s %s -h %s -t %s suspended | mysql -u %s %s %s 2>&1', 
			$_SESSION['mUser'], (isset($_SESSION['mPass']) && !empty($_SESSION['mPass']) ? "-p{$_SESSION['mPass']}" : NULL), 
			$_SESSION['mServer'], $_SESSION['mDatabase'], $_SESSION['localUser'], 
			(isset($_SESSION['localPass']) && !empty($_SESSION['localPass']) ? "-p{$_SESSION['localPass']}" : NULL), 
			$_SESSION['tDatabase']);
	
	exec($loadQ, $result, $return_code);
	foreach ($result as $v) {$output .= "$v\n";}
	if ($return_code == 0) {
		if (insertltt($register_no, $emp_no, $trans_no) == 1) {
			trimsuspended($register_no, $emp_no, $trans_no);
			return 1;
		}
	} else {
		syslog(LOG_WARNING, "resumesuspended() failed; rc: '$return_code', output: '$output'");
		return 0;
	}
}

function insertltt($register_no, $emp_no, $trans_no) {

	$inserted = 0;
	$conn = tDataConnect();
	mysql_query("TRUNCATE TABLE localtemptrans", $conn);

	$query = sprintf("INSERT INTO localtemptrans
				(datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity,
				scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount,
				ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no, memType, staff)
			 SELECT datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, trans_status, department, quantity,
				scale, unitPrice, total, regPrice, tax, foodstamp, discount, memDiscount, discountable, discounttype, voided, percentDiscount,
				ItemQtty, volDiscType, volume, VolSpecial, mixMatch, matched, card_no, memType, staff
			FROM translog.suspended WHERE register_no = %s AND emp_no = %s AND trans_no = %s AND DATE(datetime) = curdate()",
			$register_no, $emp_no, $trans_no);
	/*
		insert into localtemptrans "
		."(datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, "
		."trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, "
		."discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, "
		."volDiscType, volume, VolSpecial, mixMatch, matched, card_no, memType, staff) "
		."select "
		."datetime, register_no, emp_no, trans_no, upc, description, trans_type, trans_subtype, "
		."trans_status, department, quantity, scale, unitPrice, total, regPrice, tax, foodstamp, "
		."discount, memDiscount, discountable, discounttype, voided, percentDiscount, ItemQtty, "
		."volDiscType, volume, VolSpecial, mixMatch, matched, card_no, memType, staff "
		."from translog.suspended where register_no = ".$register_no
		." and emp_no = ".$emp_no." and trans_no = ".$trans_no;
	*/

	if (mysql_query($query, $conn)) {
		if (mysql_query("truncate table translog.suspended", $conn)) $inserted = 1;
	}
	return $inserted;
}


function trimsuspended($register_no, $emp_no, $trans_no) {

	$conn = mDataConnect();
	$query = sprintf("DELETE FROM suspended WHERE DATE(datetime) = curdate() AND register_no = %s AND emp_no = %s AND trans_no = %s", $register_no, $emp_no, $trans_no);
	mysql_query($query, $conn);

}







?>

<FORM name='hidden'>
<INPUT Type='hidden' name='alert' value='noScan'>
</FORM>
