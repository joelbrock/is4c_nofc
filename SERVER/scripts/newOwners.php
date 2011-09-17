<?php
	require_once('../src/mysql_connect.php');
	
	for ($i = 7265; $i <= 9998; $i++) {
		$query = sprintf("INSERT INTO custdata 
			(CardNo, LastName, FirstName,
			Discount, ChargeOk, memType)
			VALUES
			(%u, '', 'WELCOME', 0, 0, 2)", $i);
		$result = mysql_query($query);
		if (!$result || mysql_affected_rows() != 1)
			printf('Owner #%u not inserted: Query: %s, Error: %s' . "\n", $i, $query, mysql_error());
		else
			printf("Owner #%u successfully inserted.", $i);
	}
	
	for ($i = 10004; $i <= 10020; $i++) {
		$query = sprintf("INSERT INTO custdata 
			(CardNo, LastName, FirstName,
			Discount, ChargeOk, memType)
			VALUES
			(%u, '', 'WELCOME', 0, 0, 2)", $i);
		$result = mysql_query($query);
		if (!$result || mysql_affected_rows() != 1)
			printf('Owner #%u not inserted: Query: %s, Error: %s' . "\n", $i, $query, mysql_error());
		else
			printf("Owner #%u successfully inserted.", $i);
	}
	

?>