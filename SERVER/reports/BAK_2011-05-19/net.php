<?php
// setlocale(LC_MONETARY, 'en_US');
require('conf.php');
// include('reportFunctions.php');
$dbChangeDate = '2009-10-08';

//$db_date = '2007-03-19';
// 
// $db = mysql_connect('localhost','root','');
// mysql_select_db('is4c_log',$db);
// require_once('../src/mysql_connect.php');
// 
// $db_date = $_SESSION['db_date'];
// $table = $_SESSION['table'];

/** 
 * total sales 
 * Gross = total of all inventory depts. 1-15 (at PFC)
 * Hash = People Shares + General Donations + Customers Svcs. + gift certs. sold + Bottle Deposits & Returns + Comm. Rm. fees
 * Net = Gross + Everything else + R/A (45) - Market EBT (37) - Charge pmts.(35) - All discounts - Coupons(IC & MC) - 
 * 		Gift Cert. Tender - Store Charge
 */

$grossQ = "SELECT ROUND(sum(total),2) as GROSS_sales
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date' 
	AND department < 20
	AND department <> 0
	AND trans_subtype NOT IN ('IC', 'MC', 'CP')
	AND trans_status <> 'X'
	AND emp_no <> 9999";

	$results = mysql_query($grossQ);
	$row = mysql_fetch_row($results);
	$gross = $row[0];

$hashQ = "SELECT ROUND(sum(total),2) AS HASH_sales
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date'
	AND department >= 33 AND department <= 44
	AND trans_subtype NOT IN ('IC', 'MC', 'CP')
	AND trans_status <> 'X'
	AND emp_no <> 9999";

	$results = mysql_query($hashQ);
	$row = mysql_fetch_row($results);
	$hash = $row[0];
	if (is_null($hash)) {
		$hash = 0;
	}

//
//	BEGIN STAFF_TOTAL	
//	Total Staff discount given less the needbased and MAD discount
//

if (strtotime($db_date) <= strtotime($dbChangeDate)) { // If the date is before the change in DB data collection...

	$staffQ = "SELECT SUM(total) AS staff_total
		FROM is4c_log.$table
		WHERE date(datetime) = '$db_date'
		AND UPC = 'DISCOUNT'
		AND staff IN (1,2)
		AND trans_status <> 'X'
		AND emp_no <> 9999";
	$staffR = mysql_query($staffQ);

	list($staff_total) = mysql_fetch_row($staffR);
	$staff_total = (is_null($staff_total) ? 0 : $staff_total);

	$staffFFAQ = "SELECT SUM(unitPrice) AS wmFFA
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND staff IN (1,2)
		AND voided = 10
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$staffFFAR = mysql_query($staffFFAQ);
	list($staffFFA) = mysql_fetch_row($staffFFAR);
	$staffFFA = (is_null($staffFFA) ? 0 : $staffFFA);

	$staffMADQ = "SELECT SUM(unitPrice) AS wmMAD
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND staff IN (1,2)
		AND voided = 9
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$staffMADR = mysql_query($staffMADQ);
	list($staffMAD) = mysql_fetch_row($staffMADR);
	$staffMAD = (is_null($staffMAD) ? 0 : $staffMAD);

	$staff_total -= $staffFFA;
	$staff_total -= $staffMAD;

	//
	//	END STAFF_TOTAL
	//

	//
	//	BEGIN HOO_TOTAL
	//

	$hoo_total = 0;

	$wmFFAQ = "SELECT SUM(unitPrice) AS wmFFA
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND staff = 3
		AND voided = 10
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$wmFFAR = mysql_query($wmFFAQ);
	list($wmFFA) = mysql_fetch_row($wmFFAR);
	$wmFFA = (is_null($wmFFA) ? 0 : $wmFFA);

	$wmMADQ = "SELECT SUM(unitPrice) AS wmMAD
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND staff = 3
		AND voided = 9
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$wmMADR = mysql_query($wmMADQ);
	list($wmMAD) = mysql_fetch_row($wmMADR);
	$wmMAD = (is_null($wmMAD) ? 0 : $wmMAD);

	$wmQ = "SELECT SUM(total) as working_member
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND staff = 3
		AND UPC = 'DISCOUNT'
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$wmR = mysql_query($wmQ);
	list($hoo_total) = mysql_fetch_row($wmR);
	$hoo_total = (is_null($hoo_total) ? 0 : $hoo_total);

	$hoo_total -= $wmFFA;
	$hoo_total -= $wmMAD;
	//
	//	END HOO_TOTAL
	//

	//
	//	BEGIN BENE_TOTAL
	//

	$bene_total = 0;

	$beneQ = "SELECT SUM(total) AS benefit_provider
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND staff = 5
		AND upc = 'DISCOUNT'
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$beneR = mysql_query($beneQ);

	list($bene_total) = mysql_fetch_row($beneR);
	$bene_total = (is_null($bene_total) ? 0 : $bene_total);

	$beneMADQ = "SELECT SUM(unitPrice) AS miscMAD
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND voided = 9
		AND staff = 5
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$beneMADR = mysql_query($beneMADQ);
	list($beneMAD) = mysql_fetch_row($beneMADR);
	$beneMAD = (is_null($beneMAD) ? 0 : $beneMAD);

	$beneFFAQ = "SELECT SUM(unitprice) AS miscFFA
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND voided = 10
		AND staff = 5
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$beneFFAR = mysql_query($beneFFAQ);
	list($beneFFA) = mysql_fetch_row($beneFFAR);
	$beneFFA = (is_null($beneFFA) ? 0 : $beneFFA);

	$bene_total -= $beneFFA;
	$bene_total -= $beneMAD;

	//
	//	END BENE_TOTAL
	//

	//
	//	BOD DISCOUNTS
	//

	$boardQ = "SELECT SUM(total) AS board_total
		FROM is4c_log.$table
		WHERE date(datetime) = '$db_date'
		AND UPC = 'DISCOUNT'
		AND staff = 4
		AND trans_status <> 'X'
		AND emp_no <> 9999";
	$boardR = mysql_query($boardQ);

	list($bod_total) = mysql_fetch_row($boardR);
	$bod_total = (is_null($bod_total) ? 0 : $bod_total);

	$boardMADQ = "SELECT SUM(unitPrice) AS miscMAD
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND voided = 9
		AND staff = 4
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$boardMADR = mysql_query($boardMADQ);
	list($boardMAD) = mysql_fetch_row($boardMADR);
	$boardMAD = (is_null($boardMAD) ? 0 : $boardMAD);

	$boardFFAQ = "SELECT SUM(unitprice) AS miscFFA
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND voided = 10
		AND staff = 4
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$boardFFAR = mysql_query($boardFFAQ);
	list($boardFFA) = mysql_fetch_row($boardFFAR);
	$boardFFA = (is_null($boardFFA) ? 0 : $boardFFA);

	$bod_total -= $boardFFA;
	$bod_total -= $boardMAD;

	//
	//	END BOD DISCOUNT
	//

	//	DISCOUNT CATCHALL

	$miscDiscQ = "SELECT SUM(total) AS miscDisc
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND UPC='DISCOUNT'
		AND staff = 0
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$miscDiscR = mysql_query($miscDiscQ);
	list($miscDisc) = mysql_fetch_row($miscDiscR);
	$miscDisc = (is_null($miscDisc) ? 0 : $miscDisc);

	$miscMADQ = "SELECT SUM(unitPrice) AS miscMAD
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND voided = 9
		AND staff = 0
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$miscMADR = mysql_query($miscMADQ);
	list($miscMAD) = mysql_fetch_row($miscMADR);
	$miscMAD = (is_null($miscMAD) ? 0 : $miscMAD);

	$miscFFAQ = "SELECT SUM(unitprice) AS miscFFA
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND voided = 10
		AND staff = 0
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$miscFFAR = mysql_query($miscFFAQ);
	list($miscFFA) = mysql_fetch_row($miscFFAR);
	$miscFFA = (is_null($miscFFA) ? 0 : $miscFFA);

	$miscDisc -= $miscFFA;
	$miscDisc -= $miscMAD;

	//	END DISCOUNT CATCHALL

	$MADQ = "SELECT SUM(unitPrice), COUNT(unitPrice) FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND emp_no <> 9999
		AND trans_status <> 'X'
		AND voided = 9";
	$MADR = mysql_query($MADQ);
	$MAD_num = mysql_num_rows($MADR);
	list($MADcoupon, $MAD_num) = mysql_fetch_row($MADR);
	$MADcoupon = (is_null($MADcoupon) ? 0 : $MADcoupon);
	$MAD_num = (is_null($MAD_num) ? 0 : $MAD_num);

	$ffaQ = "SELECT SUM(unitprice), COUNT(unitPrice) AS FFA
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND emp_no <> 9999
		AND trans_status <> 'X'
		AND voided = 10";
	$ffaR = mysql_query($ffaQ);
	$ffa_num = mysql_num_rows($ffaR);
	list($foodforall, $ffa_num) = mysql_fetch_row($ffaR);
	$foodforall = (is_null($foodforall) ? 0 : $foodforall);
	$ffa_num = (is_null($ffa_num) ? 0 : $ffa_num);

} else { // New discount scheme.
	$staffQ = "SELECT SUM(total) AS staff_total
		FROM is4c_log.$table
		WHERE date(datetime) = '$db_date'
		AND UPC = 'DISCOUNT'
		AND staff IN (1,2)
		AND trans_status <> 'X'
		AND emp_no <> 9999";
	$staffR = mysql_query($staffQ);

	list($staff_total) = mysql_fetch_row($staffR);
	$staff_total = (is_null($staff_total) ? 0 : $staff_total);
	
	$wmQ = "SELECT SUM(total) as working_member
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND staff = 3
		AND UPC = 'DISCOUNT'
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$wmR = mysql_query($wmQ);
	list($hoo_total) = mysql_fetch_row($wmR);
	$hoo_total = (is_null($hoo_total) ? 0 : $hoo_total);
	
	$beneQ = "SELECT SUM(total) AS benefit_provider
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND staff = 5
		AND upc = 'DISCOUNT'
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$beneR = mysql_query($beneQ);

	list($bene_total) = mysql_fetch_row($beneR);
	$bene_total = (is_null($bene_total) ? 0 : $bene_total);
	
	$boardQ = "SELECT SUM(total) AS board_total
		FROM is4c_log.$table
		WHERE date(datetime) = '$db_date'
		AND UPC = 'DISCOUNT'
		AND staff = 4
		AND trans_status <> 'X'
		AND emp_no <> 9999";
	$boardR = mysql_query($boardQ);

	list($bod_total) = mysql_fetch_row($boardR);
	$bod_total = (is_null($bod_total) ? 0 : $bod_total);
	
	$miscDiscQ = "SELECT SUM(total) AS miscDisc
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND UPC='DISCOUNT'
		AND staff = 0
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$miscDiscR = mysql_query($miscDiscQ);
	list($miscDisc) = mysql_fetch_row($miscDiscR);
	$miscDisc = (is_null($miscDisc) ? 0 : $miscDisc);
	
	$ffaQ = "SELECT SUM(total), COUNT(total) AS ffaDisc
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND UPC='FFADISCOUNT'
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$ffaR = mysql_query($ffaQ);
	list($foodforall, $ffa_num) = mysql_fetch_row($ffaR);
	$foodforall = (is_null($foodforall) ? 0 : $foodforall);
	$ffa_num = (is_null($ffa_num) ? 0 : $ffa_num);
	
	$madQ = "SELECT SUM(total), COUNT(total) AS madDisc
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND UPC='MADDISCOUNT'
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$madR = mysql_query($madQ);
	list($MADcoupon, $MAD_num) = mysql_fetch_row($madR);
	$MADcoupon = (is_null($MADcoupon) ? 0 : $MADcoupon);
	$MAD_num = (is_null($MAD_num) ? 0 : $MAD_num);

	$ssdQ = "SELECT SUM(total), COUNT(total) AS SSDisc
		FROM is4c_log.$table
		WHERE DATE(datetime) = '$db_date'
		AND ((UPC LIKE '%SSDD') OR (UPC = 'SPECIALDISC'))
		AND emp_no <> 9999
		AND trans_status <> 'X'";
	$ssdR = mysql_query($ssdQ);
	list($SSDdiscount, $SSDD_num) = mysql_fetch_row($ssdR);
	$SSDdiscount = (is_null($SSDdiscount) ? 0 : $SSDdiscount);
	$SSDD_num = (is_null($SSDD_num) ? 0 : $SSDD_num);
}

// 10% off on the tenth discount reporting...
$tenQ = "SELECT SUM(total) AS tenDisc
	FROM is4c_log.$table
	WHERE DATE(datetime) = '$db_date'
	AND UPC='TENDISCOUNT'
	AND emp_no <> 9999
	AND trans_status <> 'X'";
$tenR = mysql_query($tenQ);
list($tenDisc) = mysql_fetch_row($tenR);
$tenDisc = (is_null($tenDisc) ? 0 : $tenDisc);

//
//	NON-MEWBER DISCOUNTS -- 10% ONLY
//	added 2011-05-04 ~jb
//
$NMDQ = "SELECT (-SUM(total) * ($NMD_discount / 100)) AS NMD_total
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date'
	AND department BETWEEN 1 AND 20
	AND card_no = 99910
	AND trans_status <> 'X' 
	AND emp_no <> 9999";

$NMDnumQ = "SELECT * FROM is4c_log.$table
	WHERE date(datetime) = '$db_date'
	AND department BETWEEN 1 AND 20
	AND card_no = 99910
	AND trans_status <> 'X' 
	AND emp_no <> 9999";
// echo $NMDnumQ;
	
$NMDR = mysql_query($NMDQ);
$NMDnumR = mysql_query($NMDnumQ);
$NMD_num = mysql_num_rows($NMDnumR);
$row = mysql_fetch_row($NMDR);
$NMD_total = $row[0];
if (is_null($NMD_total)) { $NMD_total = 0;}

//	TOTAL DISCOUT:  Add it all up ****
//
// $totalDisc = $staff_total + $bene_total + $hoo_total + $bod_total + $MADcoupon + $foodforall + $miscDisc + $tenDisc + $SSDdiscount + $NMD_total;
//
//	**********************************
	
$ICQ = "SELECT ROUND(SUM(total),2) AS coupons
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date'
	AND trans_subtype IN ('IC')
	AND trans_status <> 'X'
	AND emp_no <> 9999";
	
	$ICR = mysql_query($ICQ);
	$row = mysql_fetch_row($ICR);
	$IC = $row[0];
	if (is_null($IC)) {
		$IC = 0;
	}

$MCQ = "SELECT ROUND(SUM(total),2) AS coupons
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date'
	AND trans_subtype IN ('MC', 'CP')
	AND trans_status <> 'X'
	AND emp_no <> 9999";

	$MCR = mysql_query($MCQ);
	$row = mysql_fetch_row($MCR);
	$MC = $row[0];
	if (is_null($MC)) {
		$MC = 0;
	}
	
$TCQ = "SELECT ROUND(SUM(total),2) AS coupons
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date'
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

$strchgQ = "SELECT ROUND(SUM(total),2) AS strchg
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date'
	AND trans_subtype IN('MI')
	AND trans_status <> 'X'
	AND emp_no <> 9999";

	$strchgR = mysql_query($strchgQ);
	$row = mysql_fetch_row($strchgR);
	$strchg = $row[0];
	if (is_null($strchg)) {
		$strchg = 0;
	}

$RAQ = "SELECT ROUND(SUM(total),2) as RAs
	FROM is4c_log.$table
	WHERE date(datetime) = '$db_date'
	AND department IN(45)
	AND trans_status <> 'X'
	AND emp_no <> 9999";

	$RAR = mysql_query($RAQ);
	$row = mysql_fetch_row($RAR);
	$RA = $row[0];
	if (is_null($RA)) {
		$RA = 0;
	}

//
//	NET TOTALS
//

// $net = $gross + $hash + $totalDisc + $coupons + $strchg + $RA + $pt_total;

$cashier_netQ = "SELECT -SUM(total) AS net
	FROM is4c_log.$table
	WHERE DATE(datetime) = '$db_date'
	AND trans_subtype IN ('CA','CK','DC','CC','FS','EC')
	AND emp_no <> 9999 AND trans_status <> 'X'";

	$cnR = mysql_query($cashier_netQ);
	$row = mysql_fetch_row($cnR);
	$cnet = $row[0];
	
	
$d2 = $net - $cnet;
?>
