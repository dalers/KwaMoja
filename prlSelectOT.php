<?php
/* $Revision: 1.0 $ */

$PageSecurity = 10;
include ('includes/session.php');
$Title = _('View Overtime');

include ('includes/header.php');

if (isset($_GET['Counter'])) {
	$Counter = $_GET['Counter'];
} elseif (isset($_POST['Counter'])) {
	$Counter = $_POST['Counter'];
} else {
	unset($Counter);
}

if (isset($_GET['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button
	$CancelDelete = 0;

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'SuppTrans' , PurchOrders, SupplierContacts
	if ($CancelDelete == 0) {
		$SQL = "DELETE FROM prlottrans WHERE counterindex='$Counter'";
		$Result = DB_query($SQL);
		prnMsg(_('OT record for') . ' ' . $Counter . ' ' . _('has been deleted'), 'success');
		unset($Counter);
		unset($_SESSION['Counter']);
	} //end if Delete paypayperiod
	
}

if (!isset($Counter)) {
	/* It could still be the second time the page has been run and a record has been selected for modification - SelectedAccount will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters
	then none of the above are true and the list of ChartMaster will be displayed with
	links to delete or edit each. These will call the same page again and allow update/input
	or deletion of the records*/
	echo "<form method='post' ACTION='" . basename(__FILE__) . "?" . SID . "'>";
	echo "<input type='hidden' name='New' value='Yes'>";
	echo '<table>';

	$SQL = "SELECT  	counterindex,
						payrollid,
						otref,
						otdesc,
						otdate,
						overtimeid,
						employeeid,
						othours,
						otamount
		FROM prlottrans
		ORDER BY counterindex";
	$ErrMsg = _('The ot could not be retrieved because');
	$Result = DB_query($SQL, $ErrMsg);

	echo '<table border=1>';
	echo "<tr>
		<th>" . _('Index') . "</th>
		<th>" . _('Pay ID') . "</th>
		<th>" . _('OTRef ') . "</th>
		<th>" . _('OTDesc') . "</th>
		<th>" . _('OTDate ') . "</th>
		<th>" . _('OT ID') . "</th>
		<th>" . _('EE ID ') . "</th>
		<th>" . _('OT Hours') . "</th>
		<th>" . _('Amount ') . "</th>
	</tr>";

	$k = 0; //row colour counter
	while ($MyRow = DB_fetch_row($Result)) {

		if ($k == 1) {
			echo "<TR BGCOLOR='#CCCCCC'>";
			$k = 0;
		} else {
			echo "<TR BGCOLOR='#EEEEEE'>";
			$k++;
		}

		echo '<td>' . $MyRow[0] . '</td>';
		echo '<td>' . $MyRow[1] . '</td>';
		echo '<td>' . $MyRow[2] . '</td>';
		echo '<td>' . $MyRow[3] . '</td>';
		echo '<td>' . $MyRow[4] . '</td>';
		echo '<td>' . $MyRow[5] . '</td>';
		echo '<td>' . $MyRow[6] . '</td>';
		echo '<td>' . $MyRow[7] . '</td>';
		echo '<td>' . $MyRow[8] . '</td>';
		echo '<td><A HREF="' . basename(__FILE__) . '?' . SID . '&Counter=' . $MyRow[0] . '&delete=1">' . _('Delete') . '</A></td>';
		echo '</tr>';

	} //END WHILE LIST LOOP
	//END WHILE LIST LOOP
	
} //END IF SELECTED ACCOUNT


echo '</table>';
//end of ifs and buts!
include ('includes/footer.php');
?>