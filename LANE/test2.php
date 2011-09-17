<?php



$upc = "000999000012";


echo "FLAG = " . substr($upc,0,6);
echo "<br />";
echo "CARDNO = " . substr($upc,6,5);


?>