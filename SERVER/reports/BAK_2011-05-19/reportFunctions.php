<?php
require_once('../define.conf');
require('conf.php');

function gross($table,$date1,$date2) {
	
	if (!isset($date2)) {$date2 = $date1;}
	
	$grossQ = "SELECT ROUND(sum(total),2) as GROSS_sales
		FROM " . DB_LOGNAME . ".$table
		WHERE date(datetime) >= '$date1'
		AND date(datetime) <= '$date2' 
		AND department < 20
		AND department <> 0
		AND trans_status <> 'X'
		AND emp_no <> 9999";
	$results = mysql_query($grossQ);
	$row = mysql_fetch_row($results);
	$gross = ($row[0]) ? $row[0] : 0;

	return $gross;
}

function hash_total($table,$date1,$date2) {
	
	if (!isset($date2)) {$date2 = $date1;}
		
	$hashQ = "SELECT ROUND(sum(total),2) AS HASH_sales
		FROM " . DB_LOGNAME . ".$table
		WHERE date(datetime) >= '$date1'
		AND date(datetime) <= '$date2'
		AND department IN(33,34,35,36,37,38,40,42,43,44)
		AND trans_status <> 'X'
		AND emp_no <> 9999";

	$results = mysql_query($hashQ);
	$row = mysql_fetch_row($results);
	$hash = $row[0];

	return $hash;
}

function coupon_total($table, $date1, $date2) {
	if (!isset($date2)) {$date2 = $date1;}
		
	$ICQ = "SELECT ROUND(SUM(total),2) AS instore
		FROM is4c_log.$table
		WHERE date(datetime) BETWEEN '$date1' AND '$date2'
		AND trans_subtype IN ('IC')
		AND trans_status <> 'X'
		AND emp_no <> 9999";

	$ICR = mysql_query($ICQ);
	$row = mysql_fetch_row($ICR);
	$IC = $row[0];
	if (is_null($IC)) {
		$IC = 0;
	}

	$MCQ = "SELECT ROUND(SUM(total),2) AS manufacturer
		FROM is4c_log.$table
		WHERE date(datetime) BETWEEN '$date1' AND '$date2'
		AND trans_subtype IN ('MC', 'CP')
		AND trans_status <> 'X'
		AND emp_no <> 9999";

	$MCR = mysql_query($MCQ);
	$row = mysql_fetch_row($MCR);
	$MC = $row[0];
	if (is_null($MC)) {
		$MC = 0;
	}

	$TCQ = "SELECT ROUND(SUM(total),2) AS giftcerts
		FROM is4c_log.$table
		WHERE date(datetime) BETWEEN '$date1' AND '$date2'
		AND trans_subtype IN('TC')
		AND trans_status <> 'X'
		AND emp_no <> 9999";

	$TCR = mysql_query($TCQ);
	$row = mysql_fetch_row($TCR);
	$TC = $row[0];
	if (is_null($TC)) {
		$TC = 0;
	}

	$coupons = $IC + $MC + $TC;
	return $coupons;
}

function charge_total($table, $date1, $date2) {
	if (!isset($date2)) {$date2 = $date1;}
	$strchgQ = "SELECT ROUND(SUM(total),2) AS strchg
		FROM is4c_log.$table
		WHERE date(datetime) BETWEEN '$date1' AND '$date2'
		AND trans_subtype IN('MI')
		AND trans_status <> 'X'
		AND emp_no <> 9999";

	$strchgR = mysql_query($strchgQ);
	$row = mysql_fetch_row($strchgR);
	$strchg = $row[0];
	if (is_null($strchg)) {
		$strchg = 0;
	}
	return $strchg;
}

function RA_total($table, $date1, $date2) { 
	if (!isset($date2)) {$date2 = $date1;}
	$RAQ = "SELECT ROUND(SUM(total),2) as RAs
		FROM is4c_log.$table
		WHERE date(datetime) BETWEEN '$date1' AND '$date2'
		AND department IN(45)
		AND trans_status <> 'X'
		AND emp_no <> 9999";

	$RAR = mysql_query($RAQ);
	$row = mysql_fetch_row($RAR);
	$RA = $row[0];
	if (is_null($RA)) {
		$RA = 0;
	}
	
	return $RA;
}
	


function deptTotals ($title,$gross,$table,$date1,$date2,$in,$bgcolor) {
	if (!$bgcolor || $bgcolor == '') $bgcolor = 'FFFFFF';
//	Build table of dept sales
	$query = "SELECT t.dept_name AS $title,ROUND(sum(d.total),2) AS total,ROUND((SUM(d.total)/$gross)*100,2) as pct
		FROM " . DB_LOGNAME . ".$table AS d RIGHT JOIN departments AS t ON d.department = t.dept_no
		AND date(d.datetime) >= '$date1' AND date(d.datetime) <= '$date2' 
		AND t.dept_no IN($in)
		AND d.trans_status <> 'X'
		AND d.emp_no <> 9999
		GROUP BY t.dept_no HAVING t.dept_no IN($in) ORDER BY t.dept_no";
	// echo $query;
	//	Generate totals
	$query1 = "SELECT ROUND(sum(d.total),2) AS total,ROUND((SUM(d.total)/$gross)*100,2) as pct
		FROM " . DB_LOGNAME . ".$table AS d
		WHERE date(d.datetime) >= '$date1' AND date(d.datetime) <= '$date2' 
		AND d.department IN($in)
		AND d.trans_status <> 'X'
		AND d.emp_no <> 9999";
	$results1 = mysql_query($query1);
	$row1 = mysql_fetch_row($results1);
	$tot = $row1[0];
	$pct = $row1[1];

	echo "<p><b>" . $title . " Subtotal: " . money_format('%n',$tot) . " (". number_format($pct,2) ."%)</b></p>\n";
	// select_to_table($query,0,$bgcolor);
}
//
// 		IN PROGRESS --jb 2009-05-03
//
// function inv_depts($table,$date1,$date2) {
// 
// 	if (!isset($date2)) {$date2 = $date1;}
// 
// 	$invdept = "SELECT t.dept_no AS dept_no,t.dept_name AS dept_name,ROUND(sum(d.total),2) AS total
// 	   	FROM " . DB_LOGNAME . ".$table AS d, DB_NAME.departments AS t
// 		WHERE d.department = t.dept_no
// 		AND date(d.datetime) >= '$date1'
// 		AND date(d.datetime) <= '$date2'
// 		AND d.department <> 0
// 		AND d.trans_subtype NOT IN('IC','MC')
// 		AND d.trans_status <> 'X'
// 		AND d.emp_no <> 9999
// 		GROUP BY t.dept_no
// 		ORDER BY t.dept_no";
// 		
// 	$results = mysql_query($invdept);
// 	
// 	while ($row = mysql_fetch_assoc($results)) {
// 		$label = str_replace( ' ', '', strtoupper($row['dept_name']));
// 		
// 	}
// 	
// }
//
//		END IN PROGRESS
//

function staff_total($table,$date1,$date2) {
	require('conf.php');
	
	if (!isset($date2) || $date2 == '') {$date2 = $date1;}
	
	$staffQ = "SELECT (-SUM(total) * ($staff_discount / 100)) AS staff_total
		FROM " . DB_LOGNAME . ".$table
		WHERE date(datetime) >= '$date1' AND date(datetime) <= '$date2'
		AND department BETWEEN 1 AND 20
		AND staff IN(1,2)
		AND trans_status <> 'X' 
		AND emp_no <> 9999";

	// echo $staffQ;

	$staffR = mysql_query($staffQ);
	$row = mysql_fetch_row($staffR);
	$staff_total = $row[0];
	if (is_null($staff_total)) { $staff_total = 0;}
	
	return $staff_total;
}
//	END STAFF_TOTAL

//	BEGIN HOO_TOTAL
function hoo_total($table,$date1,$date2) {
	require('conf.php');
	
	if (!isset($date2) || $date2 == '') {$date2 = $date1;}

	$hoo_total = 0;
	foreach($volunteer_discount AS $row) {
		$wmQ = "SELECT (-SUM(total) * ($row / 100)) AS working_member
			FROM " . DB_LOGNAME . ".$table
			WHERE date(datetime) >= '$date1' AND date(datetime) <= '$date2'
			AND staff = 3
			AND department BETWEEN 1 AND 20
			AND percentDiscount = $row";
		// echo $wmQ;
		$wmR = mysql_query($wmQ);
		$row = mysql_fetch_row($wmR);
		$hoo_tot = $row[0];
		$hoo_total = $hoo_total + $hoo_tot;
	}
		
	return $hoo_total;
}
//	END HOO_TOTAL
	
//	BEGIN BENE_TOTAL
function bene_total($table,$date1,$date2) {
	require('conf.php');
	
	if (!isset($date2) || $date2 == '') {$date2 = $date1;}

	$bene_total = 0;
	foreach($volunteer_discount AS $row) {
		$beneQ = "SELECT (-SUM(total) * ($row / 100)) AS benefit_provider
			FROM " . DB_LOGNAME . ".$table
			WHERE date(datetime) >= '$date1' AND date(datetime) <= '$date2'
			AND staff = 5
			AND department BETWEEN 1 AND 20
			AND percentDiscount = $row";
		// echo $wmQ;
		$beneR = mysql_query($beneQ);
		$row = mysql_fetch_row($beneR);
		$bene_tot = $row[0];
		$bene_total = $bene_total + $bene_tot;
	}
	
	return $bene_total;
}
//	END BENE_TOTAL

//	BOD DISCOUNTS
function bod_total($table,$date1,$date2) {
	require('conf.php');
	
	if (!isset($date2) || $date2 == '') {$date2 = $date1;}

	$boardQ = "SELECT (-SUM(total) * ($board_discount / 100)) AS board_total
		FROM " . DB_LOGNAME . ".$table
		WHERE date(datetime) >= '$date1' AND date(datetime) <= '$date2'
		AND department BETWEEN 1 AND 20
		AND staff IN(4)
		AND trans_status <> 'X' 
		AND emp_no <> 9999";
		
	$boardR = mysql_query($boardQ);
	$row = mysql_fetch_row($boardR);
	$bod_total = $row[0];
	if (is_null($bod_total)) { $bod_total = 0;}

	return $bod_total;
}
	//	END BOD DISCOUNT
	
	
//
//	NON-MEMBER DISCOUNT
//	
function NMDdiscount($table,$date1,$date2) {
	require('conf.php');

	if (!isset($date2) || $date2 == '') {$date2 = $date1;}
	
	$NMDQ = "SELECT (-SUM(total) * 0.1) AS NMD_total
		FROM " . DB_LOGNAME . ".$table
		WHERE date(datetime) >= '$date1' AND date(datetime) <= '$date2'
		AND department BETWEEN 1 AND 20
		AND card_no = 99910
		AND trans_status <> 'X' 
		AND emp_no <> 9999";
		
	$NMDnumQ = "SELECT * FROM " . DB_LOGNAME . ".$table
		WHERE date(datetime) >= '$date1' AND date(datetime) <= '$date2'
		AND department BETWEEN 1 AND 20
		AND card_no = 99910
		AND trans_status <> 'X' 
		AND emp_no <> 9999";
	
	// echo $NMDQ;
	
	$NMDR = mysql_query($NMDQ);
	$NMDnumR = mysql_query($NMDnumQ);
	$NMD_num = mysql_num_rows($NMDnumR);
	$row = mysql_fetch_row($NMDR);
	$NMD_total = $row[0];
	if (is_null($NMD_total)) { $NMD_total = 0;}
	
	
	return compact('NMD_total', 'NMD_num');
}

function MADcoupon($table,$date1,$date2) {
	require('conf.php');

	if (!isset($date2) || $date2 == '') {$date2 = $date1;}

	// 	NEW MAD coupon reporting format?.....  -- 2009-03-09
	$trans_IDQ = "SELECT CONCAT(emp_no,'_',register_no,'_',trans_no) AS trans_ID
		FROM " . DB_LOGNAME . ".$table
		WHERE date(datetime) >= '$date1' AND date(datetime) <= '$date2'
		AND voided = 9
		AND trans_status NOT IN ('X','V')
		AND emp_no <> 9999";
	// echo $trans_IDQ;
	$result = mysql_query($trans_IDQ);
	$MAD_num = mysql_num_rows($result);
	$MADcoupon = 0;
	while ($row = mysql_fetch_array($result)) {
		$n = explode('_',$row['trans_ID']);
		$emp_no = $n[0];
		$register_no = $n[1];
		$trans_no = $n[2];
		$query = "SELECT (-SUM(total) * ($MAD_discount / 100)) as MADdiscount
			FROM " . DB_LOGNAME . ".$table
			WHERE date(datetime) >= '$date1' AND date(datetime) <= '$date2'
			AND emp_no = $emp_no AND register_no = $register_no AND trans_no = $trans_no
			AND department BETWEEN 1 AND 20";
		$result2 = mysql_query($query);
		$row2 = mysql_fetch_row($result2);
		$MAD_tot = $row2[0];
		// echo "MAD_tot = " . $MAD_tot;
		$MADcoupon = $MADcoupon + $MAD_tot;
	}

	return compact('MADcoupon','MAD_num');
}


function SSDdiscount($table,$date1,$date2) {
	require('conf.php');

	if (!isset($date2) || $date2 == '') {$date2 = $date1;}
	$SSDdiscount = 0;
	$SSDD_num = 0;
	// if (strtotime($date1) <= strtotime($dbChangeDate) && strtotime($date2) <= strtotime($dbChangeDate)) { // Old method...
	// 
	// 	$SSDDQ = "SELECT SUM(total), COUNT(unitprice)
	// 		FROM is4c_log.$table
	// 		WHERE voided = 22
	// 		AND DATE(datetime) BETWEEN '$date1' AND '$date2'
	// 		AND emp_no <> 9999
	// 		AND trans_status <> 'X'";
	// 	$SSDDR = mysql_query($SSDDQ);
	// 	list($SSDdiscount, $SSDD_num) = mysql_fetch_row($SSDDR);
	// 
	// 	$SSDdiscount = (is_null($SSDdiscount) ? 0 : $SSDdiscount);
	// 	$SSDD_num = (is_null($SSDD_num) ? 0 : $SSDD_num);
	// 	
	// } elseif (strtotime($date1) > strtotime($dbChangeDate) && strtotime($date2) > strtotime($dbChangeDate)) { // New method...
	// 	
		$SSDDQ = "SELECT SUM(unitprice) as tot, COUNT(unitprice) as ct
			FROM " . DB_LOGNAME . ".$table
			WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
			AND UPC = 'SPECIALDISC'
			AND emp_no <> 9999
			AND trans_status <> 'X'";
		$SSDDR = mysql_query($SSDDQ);
		list($SSDdiscount2, $SSDD_num) = mysql_fetch_row($SSDDR);
		
		$SSDdiscount2 = (is_null($SSDdiscount2) ? 0 : $SSDdiscount2);
		$SSDD_num = (is_null($SSDD_num) ? 0 : $SSDD_num);
	// } else { // Mixed bag...sum of two queries...
	// 	
	// 	$SSDDQ = "SELECT SUM(total), COUNT(total)
	// 		FROM is4c_log.$table
	// 		WHERE voided = 22
	// 		AND DATE(datetime) BETWEEN '$date1' AND '$dbChangeDate'
	// 		AND emp_no <> 9999
	// 		AND trans_status <> 'X'";
	// 	$SSDDR = mysql_query($SSDDQ);
	// 	list($SSDdiscount, $SSDD_num) = mysql_fetch_row($SSDDR);
	// 
	// 	$SSDdiscount = (is_null($SSDdiscount) ? 0 : $SSDdiscount);
	// 	$SSDD_num = (is_null($SSDD_num) ? 0 : $SSDD_num);
	// 	
	// 	$SSDDQ = "SELECT SUM(total), COUNT(total)
	// 		FROM is4c_log.$table
	// 		WHERE voided = 22
	// 		AND DATE(datetime) BETWEEN '$dbNewDate' AND '$date2'
	// 		AND emp_no <> 9999
	// 		AND trans_status <> 'X'";
	// 	$SSDDR = mysql_query($SSDDQ);
	// 	list($SSDdiscount2, $SSDD_num2) = mysql_fetch_row($SSDDR);
	// 	
	// 	$SSDdiscount += (is_null($SSDdiscount2) ? 0 : $SSDdiscount2);
	// 	$SSDD_num += (is_null($SSDD_num2) ? 0 : $SSDD_num2);
	// }
	
	return compact('SSDdiscount2','SSDD_num','SSDDQ');
}

function foodforall($table,$date1,$date2) {
	require('conf.php');
	
	if (!isset($date2) || $date2 == '') {$date2 = $date1;}

	//	NEW need-based-discount reporting calcs
	
	$trans_IDQ = "SELECT CONCAT(DATE(datetime),'_',emp_no,'_',register_no,'_',trans_no) AS trans_ID
		FROM " . DB_LOGNAME . ".$table
		WHERE date(datetime) >= '$date1' AND date(datetime) <= '$date2'
		AND upc = '" . $need_based_discount . "FF'
		AND trans_status NOT IN ('X','V')
		AND emp_no <> 9999";
	// echo $trans_IDQ;
	$result = mysql_query($trans_IDQ) OR die(mysql_error() . "<br />" . $trans_IDQ);
	$ffa_num = mysql_num_rows($result);
	$foodforall = 0;
	while ($row = mysql_fetch_array($result)) {
		$n = explode('_',$row['trans_ID']);
		$date = $n[0];
		$emp_no = $n[1];
		$register_no = $n[2];
		$trans_no = $n[3];
		$query = "SELECT (-SUM(total) * ($need_based_discount / 100)) as NBDiscount
			FROM " . DB_LOGNAME . ".$table
			WHERE date(datetime) = '$date'
			AND emp_no = $emp_no AND register_no = $register_no AND trans_no = $trans_no
			AND department BETWEEN 1 AND 20";
		$result2 = mysql_query($query) OR die(mysql_error() . "<br />" . $query);
		$row2 = mysql_fetch_row($result2);
		$ffa_tot = $row2[0];
		// echo "ffa_tot = " . $ffa_tot;
		$foodforall = $foodforall + $ffa_tot;
	}
	
	return compact('foodforall', 'ffa_num');
}

function tenDisc($table,$date1,$date2) {
	require('conf.php');
	
	if (!isset($date2) || $date2 == '') {$date2 = $date1;}
	
	$tenDiscQ = "SELECT SUM(total)
		FROM " . DB_LOGNAME . ".$table
		WHERE upc='TENDISCOUNT'
		AND DATE(datetime) BETWEEN '$date1' AND '$date2'
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$tenDiscR = mysql_query($tenDiscQ);
	list($tenDisc) = mysql_fetch_row($tenDiscR);
	
	$tenDisc = (is_null($tenDisc) ? 0 : $tenDisc);
	
	return $tenDisc;
}
function miscDisc($table, $date1, $date2) {
	require('conf.php');
	
	if (!isset($date2) || $date2 == '') {$date2 = $date1;}
	
	if (strtotime($date1) <= strtotime($dbChangeDate) && strtotime($date2) <= strtotime($dbChangeDate)) { // Old method...
	
		$miscQ = "SELECT SUM(total)
			FROM " . DB_LOGNAME . ".$table
			WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
			AND upc='DISCOUNT'
			AND staff = 0
			AND trans_status <> 'X'
			AND emp_no <> 9999";
		$miscR = mysql_query($miscQ);
	
		list($misc_total) = mysql_fetch_row($miscR);
		$misc_total = (is_null($misc_total) ? 0 : $misc_total);
	
		$miscFFAQ = "SELECT SUM(unitprice) AS wmFFA
			FROM " . DB_LOGNAME . ".$table
			WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
			AND staff = 0
			AND voided = 10
			AND trans_status <> 'X'
			AND emp_no <> 9999";
		$miscFFAR = mysql_query($miscFFAQ);
		list($miscFFA) = mysql_fetch_row($miscFFAR);
		$miscFFA = (is_null($miscFFA) ? 0 : $miscFFA);
	
		$miscMADQ = "SELECT SUM(unitprice) AS wmFFA
			FROM " . DB_LOGNAME . ".$table
			WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
			AND staff = 0
			AND voided = 9
			AND trans_status <> 'X'
			AND emp_no <> 9999";
		$miscMADR = mysql_query($miscMADQ);
		list($miscMAD) = mysql_fetch_row($miscMADR);
		$miscMAD = (is_null($miscMAD) ? 0 : $miscMAD);
	
		$misc_total -= $miscFFA;
		$misc_total -= $miscMAD;
	
	} elseif (strtotime($date1) > strtotime($dbChangeDate) && strtotime($date2) > strtotime($dbChangeDate)) { // New method...
		
		$miscQ = "SELECT SUM(total)
			FROM " . DB_LOGNAME . ".$table
			WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
			AND upc='DISCOUNT'
			AND staff = 0
			AND trans_status <> 'X'
			AND emp_no <> 9999";
		$miscR = mysql_query($miscQ);
	
		list($misc_total) = mysql_fetch_row($miscR);
		$misc_total = (is_null($misc_total) ? 0 : $misc_total);
		
	} else { // Mixed bag...slightly complicated query...
		// First half...old style
		$miscQ = "SELECT SUM(total)
			FROM " . DB_LOGNAME . ".$table
			WHERE DATE(datetime) BETWEEN '$date1' AND '$dbChangeDate'
			AND upc='DISCOUNT'
			AND staff = 0
			AND trans_status <> 'X'
			AND emp_no <> 9999";
		$miscR = mysql_query($miscQ);
	
		list($misc_total) = mysql_fetch_row($miscR);
		$misc_total = (is_null($misc_total) ? 0 : $misc_total);
	
		$miscFFAQ = "SELECT SUM(unitprice) AS wmFFA
			FROM " . DB_LOGNAME . ".$table
			WHERE DATE(datetime) BETWEEN '$date1' AND '$dbChangeDate'
			AND staff = 0
			AND voided = 10
			AND trans_status <> 'X'
			AND emp_no <> 9999";
		$miscFFAR = mysql_query($miscFFAQ);
		list($miscFFA) = mysql_fetch_row($miscFFAR);
		$miscFFA = (is_null($miscFFA) ? 0 : $miscFFA);
	
		$miscMADQ = "SELECT SUM(unitprice) AS wmFFA
			FROM " . DB_LOGNAME . ".$table
			WHERE DATE(datetime) BETWEEN '$date1' AND '$dbChangeDate'
			AND staff = 0
			AND voided = 9
			AND trans_status <> 'X'
			AND emp_no <> 9999";
		$miscMADR = mysql_query($miscMADQ);
		list($miscMAD) = mysql_fetch_row($miscMADR);
		$miscMAD = (is_null($miscMAD) ? 0 : $miscMAD);
	
		$misc_total -= $miscFFA;
		$misc_total -= $miscMAD;
		
		// Second half...new style
		$miscQ = "SELECT SUM(total)
			FROM " . DB_LOGNAME . ".$table
			WHERE DATE(datetime) BETWEEN '$dbNewDate' AND '$date2'
			AND upc='DISCOUNT'
			AND staff = 0
			AND trans_status <> 'X'
			AND emp_no <> 9999";
		$miscR = mysql_query($miscQ);
	
		list($misc_total2) = mysql_fetch_row($miscR);
		$misc_total += (is_null($misc_total2) ? 0 : $misc_total2);
	}
	
	return $misc_total;
}

function patronage_total($table, $date1, $date2) {
	$pt_totalQ = "SELECT SUM(total) 
		FROM " . DB_LOGNAME . ".$table
		WHERE date(datetime) BETWEEN '$date1' AND '$date2'
		AND trans_subtype = 'PT'
		AND emp_no <> 9999 
		AND voided <> 55
		AND trans_status <> 'X'";

	$pt_totalR = mysql_query($pt_totalQ);
	list($pt_total) = mysql_fetch_row($pt_totalR);
	$pt_total = (is_null($pt_total) ? 0 : $pt_total);
	
	return $pt_total;
}

function discount_total($table, $date1, $date2) {
	$staff_total = staff_total($table, $date1, $date2);
	$hoo_total = hoo_total($table, $date1, $date2);
	$bene_total = bene_total($table, $date1, $date2);
	$bod_total = bod_total($table, $date1, $date2);
	$misc_total = miscDisc($table, $date1, $date2);
	$tenDisc = tenDisc($table, $date1, $date2);
	extract(MADcoupon($table, $date1, $date2));  	
	extract(foodforall($table, $date1, $date2));	
	extract(SSDdiscount($table, $date1, $date2));
	// extract(staff_total($table, $date, $date));
	extract(NMDdiscount($table, $date1, $date2));
	
	$totalDisc = $staff_total + $bene_total + $hoo_total + $bod_total + $MADcoupon + $foodforall + $misc_total + $tenDisc + $SSDdiscount2 + $NMD_total;
	
	return $totalDisc;
	
}


function net_total($table, $date1, $date2) {
	$gross = gross($table, $date1, $date2);
	$hash = hash_total($table, $date1, $date2);
	$coupons = coupon_total($table, $date1, $date2);
	$strchg = charge_total($table, $date1, $date2);
	$RA = RA_total($table, $date1, $date2);
	$pt_total = patronage_total($table, $date1, $date2);
	$totalDisc = discount_total($table, $date1, $date2);
	
	$net = $gross + $hash + $totalDisc + $coupons + $strchg + $RA + $pt_total;
	
	return $net;
	
}


?>