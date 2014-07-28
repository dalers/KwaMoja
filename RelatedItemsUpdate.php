<?php

include('includes/session.inc');

$Title = _('Update Related Items');

include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

//initialise no input errors assumed initially before we test
$InputError = 0;

if (isset($_GET['Item'])) {
	$Item = trim(mb_strtoupper($_GET['Item']));
} elseif (isset($_POST['Item'])) {
	$Item = trim(mb_strtoupper($_POST['Item']));
}
if (isset($_GET['Related'])) {
	$Related = trim(mb_strtoupper($_GET['Related']));
} elseif (isset($_POST['Related'])) {
	$Related = trim(mb_strtoupper($_POST['Related']));
}

echo '<div class="toplink">
		<a href="' . $RootPath . '/SelectProduct.php">' . _('Back to Items') . '</a>
	</div>';

echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . _('Search') . '" alt="" />' . $Title . '</p>';

$Result = DB_query("SELECT stockmaster.description
					FROM stockmaster
					WHERE stockmaster.stockid='" . $Item . "'");
$MyRow = DB_fetch_row($Result);

if (DB_num_rows($Result) == 0) {
	prnMsg(_('The part code entered does not exist in the database') . ': ' . $Item . _('Only valid parts can have related items entered against them'), 'error');
	$InputError = 1;
}

if (!isset($Item)) {
	echo '<p>';
	prnMsg(_('An item must first be selected before this page is called') . '. ' . _('The product selection page should call this page with a valid product code'), 'error');
	include('includes/footer.inc');
	exit;
}

$PartDescription = $MyRow[0];

if (isset($_POST['submit'])) {

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible
	// This gives some date in 1999?? $ZeroDate = Date($_SESSION['DefaultDateFormat'],Mktime(0,0,0,0,0,0));

	$Result_related = DB_query("SELECT stockmaster.description,
										stockmaster.mbflag
									FROM stockmaster
								WHERE stockmaster.stockid='" . $_POST['Related'] . "'");
	$MyRow_related = DB_fetch_row($Result_related);

	if (DB_num_rows($Result_related) == 0) {
		prnMsg(_('The part code entered as related item does not exist in the database') . ': ' . $_POST['Related'] . _('Only valid parts can be related items'), 'error');
		$InputError = 1;
	}

	$SQL = "SELECT related
				FROM relateditems
			WHERE stockid='" . $Item . "'
				AND related = '" . $_POST['Related'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);

	if (DB_num_rows($Result) != 0) {
		prnMsg(_('This related item has already been entered.'), 'warn');
		$InputError = 1;
	}

	if ($_POST['Related'] == $Item) {
		prnMsg(_('An item can not be related to itself'), 'warn');
		$InputError = 1;
	}

	if ($InputError != 1) {
		$SQL = "INSERT INTO relateditems (stockid,
									related)
							VALUES ('" . $Item . "',
								'" . $_POST['Related'] . "')";
		$ErrMsg = _('The new related item could not be added');
		$Result = DB_query($SQL, $ErrMsg);

		prnMsg($_POST['Related'] . ' ' . _('is now related to') . ' ' . $Item, 'success');

		/* It is safe to assume that, if A is related to B, B is related to A */
		$SQL_reverse = "SELECT related
					FROM relateditems
				WHERE stockid='" . $_POST['Related'] . "'
					AND related = '" . $Item . "'";
		$Result_reverse = DB_query($SQL_reverse);
		$MyRow_reverse = DB_fetch_row($Result_reverse);

		if (DB_num_rows($Result_reverse) == 0) {
			$SQL = "INSERT INTO relateditems (stockid,
										related)
								VALUES ('" . $_POST['Related'] . "',
									'" . $Item . "')";
			$ErrMsg = _('The new related item could not be added');
			$Result = DB_query($SQL, $ErrMsg);
			prnMsg($Item . ' ' . _('is now related to') . ' ' . $_POST['Related'], 'success');
		}
	}

	unset($_POST['Related']);

} elseif (isset($_GET['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button

	/* Again it is safe to assume that we have to delete both relations A to B and B to A */

	$SQL = "DELETE FROM relateditems
			WHERE (stockid = '" . $Item . "' AND related ='" . $_GET['Related'] . "')
			OR (stockid = '" . $_GET['Related'] . "' AND related ='" . $Item . "')";
	$ErrMsg = _('Could not delete this relationshop');
	$Result = DB_query($SQL, $ErrMsg);
	prnMsg(_('This relationship has been deleted'), 'success');

}

//Always do this stuff

$SQL = "SELECT stockmaster.stockid,
			stockmaster.description
		FROM stockmaster, relateditems
		WHERE stockmaster.stockid = relateditems.related
			AND relateditems.stockid='" . $Item . "'";

$Result = DB_query($SQL);

if (DB_num_rows($Result) > 0) {
	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
	echo '<table class="selection">
			<tr>
				<th colspan="3">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />' . _('Related Items To') . ':
				<input type="text" required="required" autofocus="autofocus" name="Item" size="22" value="' . $Item . '" maxlength="20" />
				<input type="submit" name="NewPart" value="' . _('List Related Items') . '" /></th>
			</tr>';

	echo '<tbody>
			<tr>
				<th class="SortableColumn">' . _('Code') . '</th>
				<th class="SortableColumn">' . _('Description') . '</th>
				<th>' . _('Delete') . '</th>
			</tr>';

	$k = 0; //row colour counter
	while ($MyRow = DB_fetch_array($Result)) {
		if ($k == 1) {
			echo '<tr class="EvenTableRows">';
			$k = 0;
		} else {
			echo '<tr class="OddTableRows">';
			$k = 1;
		}

		echo '<td>' . $MyRow['stockid'] . '</td>
				<td>' . $MyRow['description'] . '</td>
				<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Item=' . $Item . '&amp;Related=' . $MyRow['stockid'] . '&amp;delete=yes" onclick="return confirm(\'' . _('Are you sure you wish to delete this relationship?') . '\');">' . _('Delete') . '</a></td>
			</tr>';

	}
	//END WHILE LIST LOOP
	echo '</tbody>
		</table>';
	echo '</form>';
} else {
	prnMsg(_('There are no items related set up for this part'), 'warn');
}

echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Item=' . $Item . '">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
if (isset($_GET['Edit'])) {
	/*the price sent with the get is sql format price so no need to filter */
	$_POST['Related'] = $_GET['Related'];
}

echo '<table class="selection">';

echo '<tr>
		<th colspan="5"><h3>' . $Item . ' - ' . $PartDescription . '</h3></th>
	</tr>';

if (!isset($_POST['Related'])) {
	$_POST['Related'] = '';
}

echo '<tr>
		<td>' . _('Related Item Code') . ':</td>
        <td>
			<input type="text" class="text" required="required" name="Related" size="21" maxlength="20" value="' . $_POST['Related'] . '" />
		</td>
	</tr>
</table>';
echo '<div class="centre">
		<input type="submit" name="submit" value="' . _('Enter') . '/' . _('Amend Relation') . '" />
	</div>';

echo '</form>';
include('includes/footer.inc');

?>