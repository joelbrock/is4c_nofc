<?php

require_once ('../define.conf');
// mysqli_select_db($db_master, 'is4c_log');
// mysqli_select_db($db_slave, 'is4c_log');

if ((isset($_POST['submitted']) && is_numeric($_POST['period'])) || (is_numeric($_GET['period']) && (is_numeric($_GET['emp_no']))) || (is_numeric($_POST['emp']) && is_numeric($_POST['period']))) { // If submitted or browsed to.

$table = "dlog_" . date("Y");

    if (is_numeric($_POST['emp_no'])) {$emp_no = $_POST['emp_no'];}
    elseif (is_numeric($_GET['emp_no'])) {$emp_no = $_GET['emp_no'];}
    else {$emp_no = FALSE;}
    if (is_numeric($_POST['period'])) {$periodID = $_POST['period'];}
    elseif (is_numeric($_GET['period'])) {$periodID = $_GET['period'];}

    if ($emp_no) {
        $header = 'Timesheet Management';
        $page_title = 'Fannie - Administration Module';
        include ('../src/header.php');
        include ('./includes/header.html');
        $query = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2),
                date_format(t.date, '%a %b %D'),
                t.emp_no,
                e.FirstName,
                date_format(p.periodStart, '%M %D, %Y'),
                date_format(p.periodEnd, '%M %D, %Y'),
                t.date
            FROM is4c_log.timesheet AS t
                INNER JOIN is4c_op.employees AS e
                ON (t.emp_no = e.emp_no)
                INNER JOIN is4c_log.payperiods AS p
                ON (t.periodID = p.periodID)
            WHERE t.emp_no = $emp_no
            AND t.area <> 13
            AND t.periodID = $periodID
	    AND (t.vacation IS NULL OR t.vacation = 0)
            GROUP BY t.date";

        $periodQ = "SELECT periodStart, periodEnd FROM is4c_log.payperiods WHERE periodID = $periodID";
        $periodR = mysql_query($periodQ);
        list($periodStart, $periodEnd) = mysql_fetch_row($periodR);

        $weekoneQ = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
            FROM is4c_log.timesheet AS t
            INNER JOIN is4c_log.payperiods AS p
            ON (p.periodID = t.periodID)
            WHERE t.emp_no = $emp_no
            AND t.periodID = $periodID
            AND t.area <> 13
            AND t.date >= DATE(p.periodStart)
            AND t.date < DATE(date_add(p.periodStart, INTERVAL 7 day))";

        $weektwoQ = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
            FROM is4c_log.timesheet AS t
            INNER JOIN is4c_log.payperiods AS p
            ON (p.periodID = t.periodID)
            WHERE t.emp_no = $emp_no
            AND t.periodID = $periodID
            AND t.area <> 13
            AND t.date >= DATE(date_add(p.periodStart, INTERVAL 7 day)) AND t.date <= DATE(p.periodEnd)";

        $vacationQ = "SELECT ROUND(vacation, 2), ID
            FROM is4c_log.timesheet AS t
            WHERE t.emp_no = $emp_no
            AND t.periodID = $periodID
            AND t.area = 13";

        $houseChargeQ = "SELECT ROUND(SUM(d.total),2)
            FROM is4c_log.$table d
            INNER JOIN is4c_op.employees e ON (e.card_no = d.card_no)
            AND d.datetime BETWEEN '$periodStart' AND '$periodEnd'
            AND d.staff IN (1,2)
            AND d.trans_subtype = 'MI'
            AND e.emp_no = $emp_no
            AND d.emp_no <> 9999 AND d.trans_status <> 'X'";

        $WageQ = "SELECT pay_rate FROM is4c_op.employees WHERE emp_no = $emp_no";

        $weekoneR = mysql_query($weekoneQ);
        $weektwoR = mysql_query($weektwoQ);
        $vacationR = mysql_query($vacationQ);
        $houseChargeR = mysql_query($houseChargeQ);
        $WageR = mysql_query($WageQ);

        list($weekone) = mysql_fetch_row($weekoneR);
        if (is_null($weekone)) $weekone = 0;
        list($weektwo) = mysql_fetch_row($weektwoR);
        if (is_null($weektwo)) $weektwo = 0;

        if (mysql_num_rows($vacationR) != 0) {
            list($vacation, $vacationID) = mysql_fetch_row($vacationR);
        } elseif (is_null($vacation)) {
            $vacation = 0;
            $vacationID = 'insert';
        } else {
            $vacation = 0;
            $vacationID = 'insert';
        }

        list($houseCharge) = mysql_fetch_row($houseChargeR);
        $houseCharge *= -1;
        if (is_null($houseCharge)) $houseCharge = 0;
        list($Wage) = mysql_fetch_row($WageR);
        if (is_null($Wage)) $Wage = 0;

        $result = mysql_query($query);
        if (mysql_num_rows($result) > 0) {
            $first = TRUE;
            $periodHours = 0;
            while ($row = mysql_fetch_array($result)) {
                if ($first == TRUE) {
                    echo "<p>Timesheet for $row[3] from $row[4] to $row[5]:</p>";
                    echo "<table><tr><th>Date</th><th>Total Hours Worked</th><th></th></tr>\n";
                }
                if ($row[0] > 24) {$fontopen = '<font color="red">'; $fontclose = '</font>';} else {$fontopen = NULL; $fontclose = NULL;}
                echo "<tr><td>$row[1]</td><td>$fontopen$row[0]$fontclose</td><td><a href=\"editdate.php?emp_no=$emp_no&date=$row[6]&periodID=$periodID\">(Edit)</a></td></tr>\n";
                $first = FALSE;
                $periodHours += $row[0];
            }

            $roundhour = explode('.', number_format($periodHours, 2));

            if ($roundhour[1] < 13) {$roundhour[1] = 00;}
            elseif ($roundhour[1] >= 13 && $roundhour[1] < 37) {$roundhour[1] = 25;}
            elseif ($roundhour[1] >= 37 && $roundhour[1] < 63) {$roundhour[1] = 50;}
            elseif ($roundhour[1] >= 63 && $roundhour[1] < 87) {$roundhour[1] = 75;}
            elseif ($roundhour[1] >= 87) {$roundhour[1] = 00; $roundhour[0]++;}

            $periodHours = number_format($roundhour[0] . '.' . $roundhour[1], 2);

            echo "</table>
            <form action='viewsheet.php' method='POST'>
            <p>Total hours in this pay period: " . number_format($periodHours, 2) . "</p>
            <table cellpadding='5'><tr><td>Week One: ";
            if ($weekone > 40) {echo '<font color="red">'; $font = '</font>';} else {$font = NULL;}
            echo number_format($weekone, 2) . $font . "</td>";
            echo "<td>Gross Wages (before taxes): $" . number_format($Wage * ($periodHours + $vacation), 2) . "</td></tr>";
            echo "<tr><td>Week Two: ";
            if ($weektwo > 40) {echo '<font color="red">'; $font = '</font>';} else {$font = NULL;}
            echo number_format($weektwo, 2) . $font . "</td>";
            echo "<td>Amount House Charged: $" . number_format($houseCharge, 2) . "</td></tr>";
            echo "<tr><td>Vacation Hours: ";
            if ($vacation > 0) {echo '<font color="red">'; $font = '</font>';} else {$font = NULL;}
            echo "<input type='text' name='vacation' size='5' maxlength='5' value='" . number_format($vacation, 2) . "' />" . $font . "
                <input type='hidden' name='vacationID' value='$vacationID' />
                <input type='hidden' name='period' value='$periodID' />
                <input type='hidden' name='emp' value='$emp_no' /></td>";
            echo "<td><button name='addvaca' type='submit'>Use Vacation Hours</button></td></tr></table></form><br />";

        } else {
            $periodHours = 0;
	    $nameQ = "SELECT firstName FROM is4c_op.employees WHERE emp_no=$emp_no";
	    $nameR = mysql_query($nameQ);
	    list($name) = mysql_fetch_row($nameR);

	    echo "<p>Timesheet for $name from " . date_format(date_create($periodStart), 'F dS, Y') . " to " . date_format(date_create($periodEnd), 'F dS, Y') . ":</p>
		<table>
		    <tr><th>Date</th><th>Total Hours Worked</th><th></th></tr>\n
		    <tr><td colspan='3'>(No Hours Worked In This Pay Period)</td></tr>\n
		</table>
		<form action='viewsheet.php' method='POST'>
		<p>Total hours in this pay period: 0.00</p>
		<table cellpadding='5'>
		    <tr>
			<td>Week One: 0.00</td>
			<td>Gross Wages (before taxes): $" . number_format($Wage * $vacation, 2) . "</td>
		    </tr>
		    <tr>
			<td>Week Two: 0.00</td>
			<td>Amount House Charged: $" . number_format($houseCharge, 2) . "</td>
		    </tr>
		    <tr>
			<td>Vacation Hours:
			    <input type='text' name='vacation' size='5' maxlength='5' value='" . number_format($vacation, 2) . "' />
			    <input type='hidden' name='vacationID' value='$vacationID' />
			    <input type='hidden' name='period' value='$periodID' />
			    <input type='hidden' name='emp' value='$emp_no' />
			</td>
			<td>
			    <button name='addvaca' type='submit'>Use Vacation Hours</button>
			</td>
		    </tr>
		</table>
		</form><br />";

        }

    } elseif (isset($_POST['addvaca'])) {
        $errors = array();
        $emp = $_POST['emp'];
        if (is_numeric($_POST['vacation'])) {
            $vaca = (float) $_POST['vacation'];

            $roundvaca = explode('.', number_format($vaca, 2));

            if ($roundvaca[1] < 13) {$roundvaca[1] = 00;}
            elseif ($roundvaca[1] >= 13 && $roundvaca[1] < 37) {$roundvaca[1] = 25;}
            elseif ($roundvaca[1] >= 37 && $roundvaca[1] < 63) {$roundvaca[1] = 50;}
            elseif ($roundvaca[1] >= 63 && $roundvaca[1] < 87) {$roundvaca[1] = 75;}
            elseif ($roundvaca[1] >= 87) {$roundvaca[1] = 00; $roundvaca[0]++;}

            $vaca = number_format($roundvaca[0] . '.' . $roundvaca[1], 2);

        } else {
            $errors[] = "Vacation hours to be used must be a number.";
            $vaca = FALSE;
        }

        if (is_numeric($_POST['vacationID']) && is_numeric($_POST['period'])) {
            $vacaID = (int) $_POST['vacationID'];
            $perID = (int) $_POST['period'];
            $vacaQ = "UPDATE is4c_log.timesheet SET date = NULL, vacation = $vaca WHERE ID = $vacaID";

        } elseif ($_POST['vacationID'] == 'insert' && is_numeric($_POST['period'])) {
            $perID = (int) $_POST['period'];
            $vacaQ = "INSERT INTO is4c_log.timesheet (emp_no, area, vacation, date, periodID)
                VALUES ($emp, 13, $vaca, NULL, $perID)";
        }

        if (empty($errors)) {
            $vacaR = mysql_query($vacaQ);
            if ($vacaR) {
                $url = "/timesheet/viewsheet.php?emp_no=$emp&period=$perID";
                header("Location: $url");
                exit();
            } else {
                $header = 'Timesheet Management';
                $page_title = 'Fannie - Administration Module';
                include ('../src/header.?><?php');
                include ('./includes/header.html');
                echo "<br /><br /><h3>The vacation hours could not be added due </h3><h3>to a system error, please try again later.</h3><br /><br /><br />";
                include ('../src/footer.php');
                exit();
            }
        } else {
            $header = 'Timesheet Management';
            $page_title = 'Fannie - Administration Module';
            include ('../src/header.php');
            include ('./includes/header.html');
            echo "<br /><br /><h1>The following errors occurred:</h1><ul>";

            foreach ($errors as $msg) {
                echo "<p>- $msg</p>";
            }
            echo "</ul><br /><br /><br />";


            include ('../src/footer.php');
            exit();
        }

    } else {
	$header = 'Timesheet Management';
	$page_title = 'Fannie - Administration Module';
	include ('../src/header.php');
	include ('./includes/header.html');

        echo "<br /><br /><h3>The following errors occurred:</h3><ul>
            <p>- You forgot to select your name.</p></ul><br /><br /><br />";

            include ('../src/footer.php');
            exit();
    }


} else {
    $header = 'Timesheet Management';
    $page_title = 'Fannie - Administration Module';
    include ('../src/header.php');
    include ('./includes/header.html');

    $query = "SELECT FirstName, emp_no FROM is4c_op.employees where EmpActive=1 ORDER BY FirstName ASC";
    $result = mysql_query($query);
    echo '<form action="viewsheet.php" method="POST">';
    echo '<p>Name: <select name="emp_no">
        <option>Who are You?</option>';
    while ($row = mysql_fetch_array($result)) {
            echo "<option value=\"$row[1]\">$row[0]</option>\n";
    }
    echo '</select></p>';
    $currentQ = "SELECT periodID FROM is4c_log.payperiods WHERE now() BETWEEN periodStart AND periodEnd";
    $currentR = mysql_query($currentQ);
    list($ID) = mysql_fetch_row($currentR);

    $query = "SELECT date_format(periodStart, '%M %D, %Y'), date_format(periodEnd, '%M %D, %Y'), periodID FROM is4c_log.payperiods WHERE periodStart < now() ORDER BY periodID DESC";
    $result = mysql_query($query);

    echo '<p>Pay Period: <select name="period">
        <option>Please select a payperiod to view.</option>';

    while ($row = mysql_fetch_array($result)) {
        echo "<option value=\"$row[2]\"";
        if ($row[2] == $ID) { echo ' SELECTED';}
        echo ">($row[0] - $row[1])</option>";
    }
    echo '</select></p>';

    echo '<button name="submit" type="submit">Submit</button>
    <input type="hidden" name="submitted" value="TRUE" />
    </form>';
}

include ('../src/footer.php');
?>
