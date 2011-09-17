<?php

$max = 5; // Max number of entries.

if (!isset($_POST['submitted']) && !isset($_GET['emp_no'])) {
    $header = 'Timesheet Management';
    $page_title = 'Fannie - Administration Module';
    include ('../src/header.php');
    include ('./includes/header.html');
    echo '<p><font color="red">You have found this page mistakenly.</font></p>';
    include ('../src/footer.php');
    exit();
}

require_once ('../define.conf');
mysql_select_db('is4c_log');

if (isset($_POST['submitted'])) { // If the form has been submitted.
        if ($_POST['submit'] == 'delete') {
            $header = 'Timesheet Management';
            $page_title = 'Fannie - Administration Module';
            include ('../src/header.php');
            include ('./includes/header.html');
            $emp_no = $_POST['emp_no'];
            $date = $_POST['date'];
            $query = "DELETE FROM is4c_log.timesheet WHERE emp_no=$emp_no AND date='$date'";
            $result = mysql_query($query);
            if ($result) {
                echo '<p>The day has been removed from your timesheet.</p>';
            } else {
                echo '<p><font color="red">The day could not be removed, please try again later.</font></p>';
            }
            include ('../src/footer.php');
            exit();
        } elseif ($_POST['submit'] == 'submit') {
        
            // Validate the data.
            $errors = array();
            
            $date = $_POST['date'];
            $emp_no = $_POST['emp_no'];
            
            $entrycount = 0;
            for ($i = 1; $i <= $max; $i++) {
                    if ((isset($_POST['in' . $i])) && (isset($_POST['out' . $i])) && (is_numeric($_POST['area' . $i]))) {
                            $entrycount++;
                    }
            }
            
            $lunch = $_POST['lunch'];
            $lunchID = $_POST['lunchID'];
            $periodID = $_POST['periodID'];
            $hour = array();
            $area = array();
            
            if ($entrycount == 0) {
                    $errors[] = 'You didn\'t enter any hours.';
            } else {
                    for ($i = 1; $i <= $max; $i++) {
                            if ((isset($_POST['in' . $i])) && (isset($_POST['out' . $i])) && (is_numeric($_POST['area' . $i]))) {
                                    if (strlen($_POST['in' . $i]) == 2 && is_numeric($_POST['in' . $i])) {
                                            $_POST['in' . $i] = $_POST['in' . $i] . ':00';
                                    } elseif (strlen($_POST['in' . $i]) == 4 && is_numeric($_POST['in' . $i])) {
                                            $_POST['in' . $i] = substr($_POST['in' . $i], 0, 2) . ':' . substr($_POST['in' . $i], 2, 2);
                                    } elseif (strlen($_POST['in' . $i]) == 3 && is_numeric($_POST['in' . $i])) {
                                            $_POST['in' . $i] = substr($_POST['in' . $i], 0, 1) . ':' . substr($_POST['in' . $i], 1, 2);
                                    }
                                    if (strlen($_POST['out' . $i]) == 2 && is_numeric($_POST['out' . $i])) {
                                            $_POST['out' . $i] = $_POST['out' . $i] . ':00';
                                    } elseif (strlen($_POST['out' . $i]) == 4 && is_numeric($_POST['out' . $i])) {
                                            $_POST['out' . $i] = substr($_POST['out' . $i], 0, 2) . ':' . substr($_POST['out' . $i], 2, 2);
                                    } elseif (strlen($_POST['out' . $i]) == 3 && is_numeric($_POST['out' . $i])) {
                                            $_POST['out' . $i] = substr($_POST['out' . $i], 0, 1) . ':' . substr($_POST['out' . $i], 1, 2);
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
                                    $ID[$i] = $_POST['ID' . $i];
                                    $sub[$i] = $_POST['sub' . $i] == 'on' ? 1 : 0;
                                    
                            }
                    }
            }
                 
            if (empty($errors)) { // All good.
                    
                $query = "UPDATE is4c_log.timesheet SET time_in='2008-01-01 $lunch' WHERE ID=$lunchID";
                $result = mysql_query($query);
                if (!$result) echo "<p>Lunch Failure: $query</p>";
                
                $successcount = 0;
                for ($i = 1; $i <= $entrycount; $i++) {
                    if (is_numeric($ID[$i])) {
                        $query = "UPDATE is4c_log.timesheet SET time_in='{$timein[$i]}', time_out='{$timeout[$i]}', area={$area[$i]}, sub={$sub[$i]}
                            WHERE emp_no=$emp_no AND date='$date' AND ID={$ID[$i]}";
                        
                        $result = mysql_query($query);
                        if ($result) {$successcount++;} else {echo '<p>Query: ' . $query . '</p><p>MySQL Error: ' . mysql_error() . '</p>';}
                    } elseif ($ID[$i] == 'insert') {
                        $query = "INSERT INTO is4c_log.timesheet (emp_no, time_in, time_out, area, date, periodID, sub)
                            VALUES ($emp_no, '{$timein[$i]}', '{$timeout[$i]}', {$area[$i]}, '$date', $periodID, {$sub[$i]})";
                        $result = mysql_query($query);
                        if ($result) {$successcount++;} else {echo '<p>Query: ' . $query . '</p><p>MySQL Error: ' . mysql_error() . '</p>';}
                    }
                }
            
                if ($successcount == $entrycount) {
                        // Start the redirect.
                        $url = "/timesheet/viewsheet.php?emp_no=$emp_no&period=$periodID";
                        header("Location: $url");
                        exit();
                } else {
                        $header = 'Timesheet Management';
                        $page_title = 'Fannie - Administration Module';
                        include ('../src/header.php');
                        include ('./includes/header.html');
                        echo '<p>The entered hours could not be updated, please try again later.</p>';
                        echo '<p>Error: ' . mysql_error() . '</p>';
                        echo '<p>Query: ' . $query . '</p>';
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
        }
        
} elseif (isset($_GET['emp_no']) && is_numeric($_GET['emp_no'])) { // Display the form.
        $header = 'Timesheet Management';
        $page_title = 'Fannie - Administration Module';
        include ('../src/header.php');
        include ('./includes/header.html');
        $emp_no = $_GET['emp_no'];
        $date = $_GET['date'];
        $periodID = $_GET['periodID'];
        
        // Make sure we're in a valid pay period.
                
        $query = "SELECT DATEDIFF(CURDATE(), DATE(periodEnd)) FROM is4c_log.payperiods WHERE periodID = $periodID";
        $result = mysql_query($query);
        list($datediff) = mysql_fetch_row($result);
        
        if ($datediff > 1) { // Bad.
                echo "<br /><p>You can't edit hours more than a day after the pay period has ended.</p><br />";
                include ('../src/footer.php');
                exit();
        } else { // Good.
        
            $query = "SELECT FirstName FROM is4c_op.employees where EmpActive=1 AND emp_no=$emp_no";
            $result = mysql_query($query);
            
            list($name) = mysql_fetch_row($result);
            echo "<form action='editdate.php' method='POST'>
            <input type='hidden' name='emp_no' value='$emp_no' />
            <input type='hidden' name='date' value='$date' />
            <input type='hidden' name='submitted' value='TRUE' />
            <p align='center'><button name='submit' type='submit' value='delete'>Remove this day from my timesheet.</button></p>
            </form>    
            <form action='editdate.php' method='POST'>
                <p>Name: $name</p>
                <input type='hidden' name='emp_no' value='$emp_no' />
                <input type='hidden' name='periodID' value='$periodID' />
                <p>Date: " . substr($date, 5, 2) . "-" . substr($date, 8, 2) . "-" . substr($date, 0, 4) . "</p>
                <input type='hidden' name='date' value='$date' />";
            $lunchQ = "SELECT time_in, ID FROM is4c_log.timesheet
                WHERE emp_no=$emp_no AND date='$date' AND area = 0";
            $lunchR = mysql_query($lunchQ);
            list($lunch, $lunchID) = mysql_fetch_row($lunchR);
            $lunch = substr($lunch, 11, 5);
            echo '<p>Lunch? <input type="hidden" name="lunchID" value="' . $lunchID . '" /><select name="lunch">
                            <option value="00:00:00"';
            if ($lunch == '00:00') echo ' SELECTED';
            echo '>None</option>
                            <option value="00:15:00"';
            if ($lunch == '00:15') echo ' SELECTED';
            echo '>15 Minutes</option>
                            <option value="00:30:00"';
            if ($lunch == '00:30') echo ' SELECTED';
            echo '>30 Minutes</option>
                            <option value="00:45:00"';
            if ($lunch == '00:45') echo ' SELECTED';
            echo '>45 Minutes</option>
                            <option value="01:00:00"';
            if ($lunch == '01:00') echo ' SELECTED';
            echo '>1 Hour</option>
                            <option value="01:15:00"';
            if ($lunch == '01:15') echo ' SELECTED';
            echo '>1 Hour, 15 Minutes</option>
                            <option value="01:30:00"';
            if ($lunch == '01:30') echo ' SELECTED';
            echo '>1 Hour, 30 Minutes</option>
                            <option value="01:45:00"';
            if ($lunch == '01:45') echo ' SELECTED';
            echo '>1 Hour, 45 Minutes</option>
                            <option value="02:00:00"';
            if ($lunch == '02:00') echo ' SELECTED';
            echo '>2 Hours</option>
                    </select></p>';
                
            // echo "<p>Please use enter times in (HH:MM) format. For example 8:45, 12:30, etc.</p>";
            echo "<table><tr><th>Time In</th><th>Time Out</th><th>Area Worked</th><th>Sub?</th></tr>\n";
            $query = "SELECT time_in, time_out, area, ID, sub FROM is4c_log.timesheet
                WHERE emp_no = $emp_no AND date = '$date' AND area NOT IN (0, 13)
                ORDER BY ID ASC";
            $result = mysql_query($query);
            for ($i = 1; $i <= $max; $i++) {
                if ($row = mysql_fetch_row($result)) {
                    $timein = substr($row[0], 11, 5);
                    $inarray = explode(':', $timein);
                    $timeout = substr($row[1], 11, 5);
                    $outarray = explode(':', $timeout);
                    
                    if ($inarray[0] < 12 && $inarray[0] != 0) {
                        $in = 'AM';
                    } elseif ($inarray[0] == 12) {
                        $in = 'PM';
                    } elseif ($inarray[0] == 00) {
                        $in = 'AM';
                        $inarray[0] = 12;
                    } else {
                        $inarray[0] = $inarray[0] - 12;
                        $in = 'PM';
                    }
                    
                    if ($outarray[0] < 12  && $outarray[0] != 0) {
                        $out = 'AM';
                    } elseif ($outarray[0] == 12) {
                        $out = 'PM';
                    } elseif ($outarray[0] == 00) {
                        $out = 'AM';
                        $outarray[0] = 12;
                    } else {
                        $out = 'PM';
                        $outarray[0] = $outarray[0] - 12;
                    }
                    
                    $timein = $inarray[0] . ':' . $inarray[1];
                    $timeout = $outarray[0] . ':' . $outarray[1];
                    $area = $row[2];
                    $ID = $row[3];
                } else {
                    $timein = NULL;
                    $timeout = NULL;
                    $area = NULL;
                    $ID = "insert";
                }
                $shiftQ = "SELECT * FROM is4c_log.shifts ORDER BY ShiftOrder ASC";
                $shiftR = mysql_query($shiftQ);
                echo '<tr><th><input type="hidden" name="ID' . $i . '" value="' . $ID . '">
                <input type="text" name="in' . $i . '" size="5" maxlength="5" value="' . $timein . '">
                <select name="inmeridian' . $i . '"><option value="AM"';
                if ($in == 'AM') echo ' SELECTED';
                echo '>AM</option><option value="PM"';
                if ($in == 'PM') echo 'SELECTED';
                echo '>PM</option></select></th>';
                echo '<th><input type="text" name="out' . $i . '" size="5" maxlength="5" value="' . $timeout . '">
                <select name="outmeridian' . $i . '"><option value="AM"';
                if ($out == 'AM') echo ' SELECTED';
                echo '>AM</option><option value="PM"';
                if ($out == 'PM') echo 'SELECTED';
                echo '>PM</option></select></th>';
                echo '<th><select name="area' . $i . '">
                <option>Please select an area of work.</option>';
                while ($shiftrow = mysql_fetch_row($shiftR)) {
                    echo "<option value=\"$shiftrow[1]\"";
                    if ($shiftrow[1] == $area) {echo ' SELECTED';}
                    echo ">$shiftrow[0]</option>";
                }
                echo "</select></th>\n";
                echo '<th><input type="checkbox" name="sub' . $i . '"';
                if ($row[4] == 1) echo ' CHECKED';
                echo ' /></tr>';
            }
                echo '</table>
            <button name="submit" type="submit" value="submit">Submit</button>
            <input type="hidden" name="submitted" value="TRUE" />
            </form>';
        }

}
        
include ('../src/footer.php');
?>
