<?php
/*******************************************************************************

    Copyright 2005 Whole Foods Community Co-op

    This file is part of WFC's PI Killer.

    PI Killer is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    PI Killer is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
$page_title = 'Fannie - Item Maintanence';
$header = 'Item Maintanence';
include('../src/header.php');
include('prodFunction.php');

if(isset($_POST['submit'])){
    $upc = $_POST['upc'];
 
    itemParse($upc);

}elseif(isset($_GET['upc'])){
    $upc = $_GET['upc'];
    itemParse($upc);

}else{

echo "<head><title>Edit Item</title></head>";
echo "<BODY onLoad='putFocus(0,0);'>";
echo "<form action=" . DOCROOT . "item/itemMaint.php method=post>";
echo "<input name=upc type=text id=upc> Enter UPC/PLU or product name here<br><br>";

echo "<input name=submit type=submit value=submit>";
echo "</form>";
}

include ('../src/footer.php');

?>
