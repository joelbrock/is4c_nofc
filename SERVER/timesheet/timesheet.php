<?php

$max = 5; // Max number of entries.

require_once ('../define.conf');
// mysql_select_db(DB_NAME, 'is4c_log');

if (isset($_POST['submitted'])) { // If the form has been submitted.
        // Validate the data.
        $errors = array();
        
        //if (checkdate($_POST['month'], $_POST['date'], date('Y'))) {
       //2011-01-03 sdh - added a field to select a YEAR 
        if (checkdate($_POST['month'], $_POST['date'],$_POST['year'])) {
	        $date = $_POST['year'] . '-' . str_pad($_POST['month'], 2, 0, STR_PAD_LEFT) . '-' . $_POST['date'];
        } else {
                $errors[] = 'The date you have entered is not a valid date.';
        }
        
        if (strtotime($date) > strtotime(date('Y-m-d'))) {
                $errors[] = 'You can\'t enter hours for a future date.';
        }
        
        // Make sure we're in a valid pay period.
        $query = "SELECT periodID FROM is4c_log.payperiods WHERE '$date' BETWEEN DATE(periodStart) AND DATE(periodEnd)";
        $result = mysql_query($query);
        list($periodID) = mysql_fetch_row($result);
        
        $query = "SELECT DATEDIFF(CURDATE(), DATE(periodEnd)) FROM is4c_log.payperiods WHERE periodID = $periodID";
        $result = mysql_query($query);
        list($datediff) = mysql_fetch_row($result);
        
        if (!is_numeric($_POST['emp_no'])) {
                $errors[] = 'You didn\'t select your name.';
        } else {
                $emp_no = $_POST['emp_no'];
        }
        
        if ($datediff > 1) { // Bad.
                $errors[] = 'You can\'t add hours more than a day after the pay period has ended.';
                $date = NULL;
        }
        
        $entrycount = 0;
        for ($i = 1; $i <= $max; $i++) {
                if ((isset($_POST['in' . $i])) && (isset($_POST['out' . $i])) && (is_numeric($_POST['area' . $i]))) {
                        $entrycount++;
                }
        }
        
        $lunch = $_POST['lunch'];
        $hour = array();
        $area = array();
        
        if ($entrycount == 0) {
                $errors[] = "You didn't enter any hours.";
        } else {
                for ($i = 1; $i <= $max; $i++) {
                        if ((isset($_POST['in' . $i])) && (isset($_POST['out' . $i])) && (is_numeric($_POST['area' . $i]))) {
                                if (strlen($_POST['in' . $i]) == 2 && is_numeric($_POST['in' . $i])) {
                                        $_POST['in' . $i] = $_POST['in' . $i] . ':00';
                                } elseif (strlen($_POST['in' . $i]) == 4 && is_numeric($_POST['in' . $i])) {
                                        $_POST['in' . $i] = substr($_POST['in' . $i], 0, 2) . ':' . substr($_POST['in' . $i], 2, 2);
                                } elseif (strlen($_POST['in' . $i]) == 3 && is_numeric($_POST['in' . $i])) {
                                        $_POST['in' . $i] = substr($_POST['in' . $i], 0, 1) . ':' . substr($_POST['in' . $i], 1, 2);
                                } elseif (strlen($_POST['in' . $i]) == 1 && is_numeric($_POST['in' . $i])) {
                                        $_POST['in' . $i] = $_POST['in' . $i] . ':00';
                                }
                                
                                if (strlen($_POST['out' . $i]) == 2 && is_numeric($_POST['out' . $i])) {
                                        $_POST['out' . $i] = $_POST['out' . $i] . ':00';
                                } elseif (strlen($_POST['out' . $i]) == 4 && is_numeric($_POST['out' . $i])) {
                                        $_POST['out' . $i] = substr($_POST['out' . $i], 0, 2) . ':' . substr($_POST['out' . $i], 2, 2);
                                } elseif (strlen($_POST['out' . $i]) == 3 && is_numeric($_POST['out' . $i])) {
                                        $_POST['out' . $i] = substr($_POST['out' . $i], 0, 1) . ':' . substr($_POST['out' . $i], 1, 2);
                                } elseif (strlen($_POST['out' . $i]) == 1 && is_numeric($_POST['out' . $i])) {
                                        $_POST['out' . $i] = $_POST['out' . $i] . ':00';
                                }
                                
                                $in = explode(':', $_POST['in' . $i]);
                                $out = explode(':', $_POST['out' . $i]);
                                
                                if (($_POST['inmeridian' . $i] == 'PM') && ($in[0] < 12)) {
                                        $in[0] = $in[0] + 12;
                                } elseif (($_POST['inmeridian' . $i] == 'AM') && ($in[0] == 12)) {
                                        $in[0] = 0;
                                }
                                if (($_POST['outmeridian' . $i] == 'PM') && ($out[0] < 12)) {
                                        $out[0] = $out[0] + 12;
                                } elseif (($_POST['outmeridian' . $i] == 'AM') && ($out[0] == 12)) {
                                        $out[0] = 0;
                                }
                                
                                $timein[$i] = $date . ' ' . $in[0] . ':' . $in[1] . ':00';
                                $timeout[$i] = $date . ' ' . $out[0] . ':' . $out[1] . ':00';
                                $area[$i] = $_POST['area' . $i];
                                $sub[$i] = $_POST['sub' . $i] == 'on' ? 1 : 0;
                                
                                if (strtotime($timein[$i]) >= strtotime($timeout[$i])) {
                                        $errors[] = "You can't have gotten here after you finished work.</p><p>Or, you couldn't have finished work before you started work.";
                                }
                        }
                }
        }
        
        if (empty($errors)) { // All good.
                // First check to make sure they haven't already entered hours for this day.
                $query = "SELECT * FROM is4c_log.timesheet WHERE emp_no=$emp_no AND date='$date'";
                $result = mysql_query($query);
                if (mysql_num_rows($result) == 0) { // Success.
                        $successcount = 0;
                        for ($i = 1; $i <= $entrycount; $i++) {
                                $query = "INSERT INTO is4c_log.timesheet (emp_no, time_in, time_out, area, date, periodID, sub)
                                        VALUES ($emp_no, '{$timein[$i]}', '{$timeout[$i]}', {$area[$i]}, '$date', $periodID, $sub[$i])";
                                $result = mysql_query($query);
                                if (mysql_affected_rows($dbc) == 1) {$successcount++;}
                        }
                        if ($successcount == $entrycount) {
                                
                        } else {
                                $header = 'Timesheet Management';
                                $page_title = 'Fannie - Administration Module';
                                include ('../src/header.php');
                                include ('./includes/header.html');
                                echo '<p>The entered hours could not be added, please try again later.</p>';
                                echo '<p>Error: ' . mysql_error() . '</p>';
                                echo '<p>Query: ' . $query . '</p>';
                                include ('../src/footer.php');
                                exit();
                        }
                        $query = "INSERT INTO is4c_log.timesheet (emp_no, time_out, time_in, area, date, periodID)
                                VALUES  ($emp_no, '2008-01-01 00:00:00', '2008-01-01 " . $lunch . "', 0, '$date', $periodID)";
                        $result = mysql_query($query);
                        if (!$result) {
                                $header = 'Timesheet Management';
                                $page_title = 'Fannie - Administration Module';
                               include ('../src/header.php');
                                include ('./includes/header.html');
                                echo '<p>The entered hours could not be added, please try again later.</p>';
                                echo '<p>Error: ' . mysql_error() . '</p>';
                                echo '<p>Query: ' . $query . '</p>';
                                include ('../src/footer.php');
                                exit();
                        } else {
                                // Start the redirect.
                                $url = "/timesheet/viewsheet.php?emp_no=$emp_no&period=$periodID";
                                header("Location: $url");
                                exit();
                        }
                } else {
                        $header = 'Timesheet Management';
                        $page_title = 'Fannie - Administration Module';
                        include ('../src/header.php');
                        include ('./includes/header.html');
                        echo '<p>You have already entered hours for that day, please edit that day instead.</p>';
                }
                
        } else { // Report errors.
                $header = 'Timesheet Management';
                $page_title = 'Fannie - Administration Module';
                include ('../src/header.php');
                include ('./includes/header.html');
                echo '<p><font color="red">The following error(s) occurred:</font></p>';
                foreach ($errors AS $message) {
                        echo "<p> - $message</p>";
                }
                echo '<p>Please try again.</p>';
        }
        
        
} else { // Otherwise display the form.
       echo '<script type="text/javascript" language="javascript">
                window.onload = initAll;
                function initAll() {
                        for (var i = 1; i <= 5 ; i++) {
                                        document.getElementById(i + "14").disabled = true;
                        }
                }
		//this function was used by Matthaus (#7012) to hide certain Categories
                function updateshifts(sIndex) {
                        if (sIndex == 7012) {
                                for (var i = 1; i <= 5 ; i++) {
                                        document.getElementById(i + "14").disabled = false;
                                }
                        } else {
                                for (var i = 1; i <= 5 ; i++) {
                                        document.getElementById(i + "14").disabled = true;
                                }
                        }
                }
                </script>';
        $header = 'Timesheet Management';
        $page_title = 'Fannie - Administration Module';
        // include ('../includes/header.html');
		include('../src/header.php');

        include ('./includes/header.html');
        $months = array(01=>'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
        
        echo '<form action="timesheet.php" method="POST" name="timesheet">
            <p>Name: <select name="emp_no" onchange="updateshifts(this.value);">
                <option value="error">Who are You?</option>' . "\n";
        
        $query = "SELECT FirstName, emp_no FROM is4c_op.employees where EmpActive=1 ORDER BY FirstName ASC";
        $result = mysql_query($query);
        while ($row = mysql_fetch_array($result)) {
                echo "<option value=\"$row[1]\">$row[0]</option>\n";
        }
        echo '</select></p>
            <p>Month: <select name="month">';
            foreach ($months AS $value => $key) {
                echo "<option value=\"$value\"";
                if (date('m')==$value) echo ' SELECTED';
                echo ">$key</option>\n";
            }
        echo '</select>
            Date: <select name="date">';
            for ($i = 1; $i <= 31; $i++) {
                $i = str_pad($i, 2, 0, STR_PAD_LEFT);
                echo "<option value=\"$i\"";
                if (date('d') == $i) echo ' SELECTED';
                echo ">$i</option>\n";
            }
        echo '</select>';
	echo ' Year: <select name="year">
		<option value="2011">2011</option>
		<option value="2010">2010</option>
		<option value="2009">2009</option>
		<option value="2008">2008</option>
		<option value="2007">2007</option>
		</select><br /> (Today is ';
        echo date('l\, F jS, Y');
        echo ')</p>';
        echo '<p>Lunch? <select name="lunch">
                        <option value="00:00:00">None</option>
                        <option value="00:15:00">15 Minutes</option>
                        <option value="00:30:00">30 Minutes</option>
                        <option value="00:45:00">45 Minutes</option>
                        <option value="01:00:00">1 Hour</option>
                        <option value="01:15:00">1 Hour, 15 Minutes</option>
                        <option value="01:30:00">1 Hour, 30 Minutes</option>
                        <option value="01:45:00">1 Hour, 45 Minutes</option>
                        <option value="02:00:00">2 Hours</option>
                </select></p>';

        // echo "<p>Please use enter times in (HH:MM) format. For example 8:45, 12:30, etc.</p>";
        echo "<table><tr><th>Time In</th><th>Time Out</th><th>Area Worked</th><th>Sub?</th></tr>\n";
        for ($i = 1; $i <= $max; $i++) {
                $query = "SELECT * FROM is4c_log.shifts ORDER BY ShiftName ASC";
                $result = mysql_query($query);
                
            echo '<tr>
            <th><input type="text" name="in' . $i . '" size="5" maxlength="5">
                <select name="inmeridian' . $i . '">
                        <option value="AM">AM</option>
                        <option value="PM">PM</option>
                </select>
            </th>
            <th><input type="text" name="out' . $i . '" size="5" maxlength="5">
                <select name="outmeridian' . $i . '">
                        <option value="AM">AM</option>
                        <option value="PM" SELECTED>PM</option>
                </select>
            </th>
            <th><select name="area' . $i . '" id="area' . $i . '">
            <option>Please select an area of work.</option>';
            while ($row = mysql_fetch_row($result)) {
                echo "<option id =\"$i$row[1]\" value=\"$row[1]\">$row[0]</option>";
            }
            echo '</select></th>' . "\n";
            echo '<th><input type="checkbox" name="sub' . $i . '" /></th></tr>';
        }
            echo '</table>
        <button name="submit" type="submit">Submit</button>
        <input type="hidden" name="submitted" value="TRUE" />
        </form>';

}
        
include('../src/footer.php');
?>
