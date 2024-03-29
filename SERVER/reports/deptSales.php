<?php
/*******************************************************************************

    Copyright 2007 People's Food Co-op, Portland, Oregon.

    This file is part of Fannie.

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

include('../src/functions.php');
require_once('../define.conf');

// if(isset($_GET['sort'])){
// 	if(isset($_GET['XL'])){
// 		header("Content-Disposition: inline; filename=deptSales.xls");
// 		header("Content-Description: PHP3 Generated Data");
// 		header("Content-type: application/vnd.ms-excel; name='excel'");
// 	}
// }

if (isset($_POST['submit'])) {
	echo "<html><head><title>Department Sales Movement</title>";
	include ('../src/head.php');
	echo "<link rel=\"stylesheet\" href=\"" . SRCROOT . "/tablesort.css\" type=\"text/css\" />\n</head>";

	foreach ($_POST AS $key => $value) {
		$$key = $value;
	}	
	
	echo "<BODY>";

	$today = date("F d, Y");	
	
	// itemproperty searching - incomplete:  can be done using table filter now.
	// if (isset($_POST['property'])) { 
	// 	$propArray = implode(",",$_POST['property']); 
	// } elseif (isset($_GET['property'])) { 
	// 	$propArray = $_GET['property'];
	// }
	// echo $propArray;
	// $itemProp = '';
	//  end itemprop search
	
	if (isset($allDepts)) {
		$deptArray = "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,40";
		$arrayName = "ALL DEPARTMENTS";
	} else {
		if (isset($_POST['dept'])) {$deptArray = implode(",",$_POST['dept']);}
		elseif (isset($_GET['dept'])) {$deptArray = $_GET['dept'];}
		$arrayName = $deptArray;
	}

	echo "<center><h1>Department Sales Movement</h1>\n
		<h2>Product Movement for $date1 thru $date2</h2></center>";

	// Check year in query, match to a dlog table
	$year1 = idate('Y',strtotime($date1));
	$year2 = idate('Y',strtotime($date2));

	if ($year1 != $year2) {
		echo "<div id='alert'><h4>Reporting Error</h4>
			<p>Fannie cannot run reports across multiple years.<br>
			Please retry your query.</p></div>";
		exit();
	}
//	elseif ($year1 == date('Y')) { $table = 'dtransactions'; }
	else { $table = 'dlog_' . $year1; }

	$date2a = $date2 . " 23:59:59";
	$date1a = $date1 . " 00:00:00";	

	if(isset($inUse)) {
		$inUseA = "AND p.inUse = 1";
	} else {
		$inUseA = "AND p.inUse IN(0,1)";
	}
	echo "<table border=0><tr><td>";

	if (isset($salesTotal)) {
		$query1 = "SELECT d.dept_name,ROUND(SUM(t.total),2) AS total
			FROM " . DB_NAME . ".departments AS d, " . DB_LOGNAME . ".$table AS t
			WHERE d.dept_no = t.department
			AND t.datetime >= '$date1a' AND t.datetime <= '$date2a'
			AND t.department IN($deptArray)
			AND t.trans_subtype NOT IN ('MC','IC')
			AND t.trans_status <> 'X'
			AND t.emp_no <> 9999
			$itemProp
			GROUP BY t.department";
				
		$result1 = mysql_query($query1);
	
		if (!$result1) {
			$message  = 'Invalid query: ' . mysql_error() . "\n";
			$message .= 'Whole query: ' . $query1;
			die($message);
		}
		echo "<table border=1>
			<tr>
				<td><b>Department</b></td>
				<td><b>Total Sales</b></td>
			</tr>";
			
		while($myrow = mysql_fetch_row($result1)) {
			echo "<tr><td>$myrow[0]</td><td align=right>" . money_format('%n',$myrow[1]) . "</td></tr>";
		}
		echo "</table>\n";
		
	}
	echo "</td><td>";
		
	if(isset($openRing)) {
		//$query2 - Total open dept. ring
		$query2 = "SELECT d.dept_name AS Department,ROUND(SUM(t.total),2) AS open_dept
			FROM ". DB_NAME . ".departments AS d," . DB_LOGNAME . ".$table AS t 
			WHERE d.dept_no = t.department
			AND t.datetime >= '$date1a' AND t.datetime <= '$date2a' 
			AND t.department IN($deptArray)
			AND t.trans_type = 'D' 
			AND t.trans_subtype NOT IN ('MC','IC')
			AND t.emp_no <> 9999 AND t.trans_status <> 'X' 
			$itemProp
			GROUP BY t.department";

		$result2 = mysql_query($query2);
		
		if (!$result2) {
			$message  = 'Invalid query: ' . mysql_error() . "\n";
			$message .= 'Whole query: ' . $query2;
					die($message);
		}
		
		echo "<table border=1>\n
			<tr>
				<td><b>Department</b></td>
				<td><b>Open Ring</b></td>
			</tr>\n";

		while($myrow = mysql_fetch_row($result2)) {
			echo "<tr><td>" . $myrow[0] . "</td><td align=right>" . money_format('%n',$myrow[1]) . "</td></tr>";
		}
		echo "</table>\n";
	} 
	
	echo "</td></tr></table>";		
	
	if(isset($pluReport)){
		// $query3 - Sales per PLU
		$query3 = "SELECT DISTINCT
			p.upc AS UPC,
			p.description AS Description,
			d.cost AS Cost,
			t.unitPrice AS Price,
			p.department AS Dept,
			s.subdept_name AS Subdept,
			p.props AS Props,
			SUM(t.quantity) AS Qty,
			ROUND(SUM(t.total),2) AS Total,
			p.scale as Scale
			FROM " . DB_LOGNAME . ".$table t, " . PRODUCTS_TBL . " p, product_details d, subdepts s
			WHERE t.upc = p.upc AND p.upc = d.upc
			AND s.subdept_no = p.subdept
			AND t.department IN($deptArray) 
			AND t.datetime >= '$date1a' AND t.datetime <= '$date2a' 
			AND t.emp_no <> 9999
			AND t.trans_status <> 'X'
			AND t.upc NOT LIKE '%DP%'
			$itemProp
			$inUseA
			GROUP BY t.unitPrice,t.upc";
	
		// echo $query3;
		$result3 = mysql_query($query3);
		$num = mysql_num_rows($result3);
		
		if (!$result3) {
			$message  = 'Invalid query: ' . mysql_error() . "\n";
			$message .= 'Whole query: ' . $query3;
				die($message);
		}

		echo "<table id=\"output\" cellpadding=0 cellspacing=0 border=0 class=\"sortable-onload-8 rowstyle-alt colstyle-alt\">\n
		  <caption>Department range: ".$arrayName.". Search yielded (".$num.") results. Generated on " . date('n/j/y \a\t h:i A') . " &mdash; <a id=\"cleanfilters\" href=\"#\">Clear Filters</a></caption>\n
		  <thead>\n
		    <tr>\n
		      <th class=\"sortable-numeric\">UPC</th>\n
		      <th class=\"sortable-text\">Description</th>\n
			  <th class=\"sortable-currency\">cost</th>\n
		      <th class=\"sortable-currency\">Price</th>\n
			  <th class=\"sortable\">margin%</th>\n
		      <th filter-type='ddl' class=\"sortable-numeric\">Dept.</th>\n
		      <th filter-type='ddl' class=\"sortable-numeric\">Subdept.</th>\n
		      <th class=\"sortable-text\">Item Properties</th>\n
		      <th class=\"sortable-numeric favour-reverse\">Qty.</th>\n
		      <th class=\"sortable-currency favour-reverse\">SALES</th>\n
		      <th filter-type='ddl' class=\"sortable-text\">Scale</th>\n		
		    </tr>\n
		  </thead>\n
		  <tbody>\n";
		
		while ($row = mysql_fetch_array ($result3)) {
			//	GROSS MARGIN = ( ( C - P ) / P ) * 100
			$cost = ($row["Cost"]) ? $row["Cost"] : '';
			$price = $row["Price"];
			$margin = ($cost) ? -round((($cost - $price) / $price * 100),2) : '';
			$pcolor = ($price == 0) ? 'red' : 'black';	
			$mcolor = ($margin < 20) ? 'red' : (($margin > 40) ? 'green' : '');
			$b = bindecValues($row["Props"]);
			$prop_arr = explode("|", $b);
			echo "<td align=center><a class='opener' href='../item/itemMaint.php?upc=" . $row["UPC"] . "'>" . $row["UPC"] . "</a></td>\n
				<td align=left>" . $row["Description"] . "</td>\n
				<td align=right>" . $cost . "</td>\n
				<td align=right><font color=$pcolor>" . money_format('%n',$price) . "</font></td>\n
				<td align=right><font color=$mcolor>" . $margin . "</font></td>\n
				<td align=left>" . $row["Dept"] . "</td>\n
				<td align=left>" . $row["Subdept"] . "</td>\n
				<td>";
			if (!$row["Props"] || $row["Props"] == 0) {
				echo "";
			} else { 
				// print_r($prop_arr);
				foreach ($prop_arr as $i) {
					$tagR = mysql_query("SELECT * FROM item_properties WHERE bit = $i");
					$tag = mysql_fetch_assoc($tagR);
					echo "<span><a class='itemtag' href='#' title='".$tag['name']."'>" . acronymize($tag['name']) . "</a></span>";
				}
			}
			echo "</td>\n
				<td align=right>" . number_format($row["Qty"],2) . "</td>\n
				<td align=right>" . money_format('%n',$row["Total"]) . "</td>\n";
				if($row["Scale"] == 1){
					echo "<td align=center>#</td>";
				} else {
					echo "<td align=center>ea.</td>";
				}
			echo "</tr>\n";
		}
	
		echo "</tbody></table>\n";
		
	}


	// debug_p($_REQUEST, "all the data coming in");
} else {

$page_title = 'Fannie - Reporting';
$header = 'Movement Report';
include('../src/header.php');
?>

<form method="post" action="deptSales.php" target="_blank">		

<div id="box">
	<table border="0" cellspacing="3" cellpadding="3">
		<tr> 
            <th><p><b>Select dept.*</b></p></th>
		</tr>
		<tr valign=top>';
<?php
include('../src/departments.php');
// include('../src/item_props.php');
?>
</tr>
	</table>
</div>
<div id="box">
	<table border="0" cellspacing="3" cellpadding="3">
		<tr>
			<td align="right">
				<p><b>Date Start:</b></p>
		    	<p><b>Date End:</b></p>
			</td>
			<td>			
				<div class="date"><p><input type="text" name="date1" class="datepicker" />&nbsp;&nbsp;*</p></div>
				<div class="date"><p><input type="text" name="date2" class="datepicker" />&nbsp;&nbsp;*</p></div>
			</td>
		</tr>
	</table>
</div>
<div id="box">
	<table border="0" cellspacing="3" cellpadding="3">
	<tr>
			<td align="right"><p><b>Sales totals</b></p></td>
			<td><input type="checkbox" value="1" name="salesTotal" CHECKED></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="right"><p><b>Open ring totals</b></p>
			</td><td><input type="checkbox" value="1" name="openRing" CHECKED></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="right"><p><b>PLU report</b></p></td>
			<td>
				<input type="checkbox" value="1" name="pluReport" CHECKED>
			</td>
		</tr>
		<tr>
			<td colspan="3" align="center">
				<p>* -- indicates required field</p>
			</td>
		</tr>
		<tr> 
			<td>&nbsp;</td>
			<td> <input type=submit name=submit value="Submit"> </td>
			<td> <input type=reset name=reset value="Start Over"> </td>
		</tr>
	</table>
</div>
</form>

<?php include('../src/footer.php'); 

}
?>
<script>
	$(function() {
		$( ".datepicker" ).datepicker({ 
			dateFormat: 'yy-mm-dd' 
		});
	});
</script>
