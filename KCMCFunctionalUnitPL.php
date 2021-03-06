<?php
/* $Id$*/
include ('includes/session.php');
$Title = _('Income and Expenditure by Functional Unit');
include ('includes/SQL_CommonFunctions.inc');
include ('includes/AccountSectionsDef.inc'); // This loads the $Sections variable


if (isset($_POST['FromPeriod']) and ($_POST['FromPeriod'] > $_POST['ToPeriod'])) {
	prnMsg(_('The selected period from is actually after the period to') . '! ' . _('Please reselect the reporting period'), 'error');
	$_POST['SelectADifferentPeriod'] = 'Select A Different Period';
}

if ((!isset($_POST['FromPeriod']) and !isset($_POST['ToPeriod'])) or isset($_POST['SelectADifferentPeriod'])) {

	include ('includes/header.php');
	echo '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/printer.png" title="' . _('Print') . '" alt="" />' . ' ' . $Title . '</p>';

	if (Date('m') > $_SESSION['YearEnd']) {
		/*Dates in SQL format */
		$DefaultFromDate = Date('Y-m-d', Mktime(0, 0, 0, $_SESSION['YearEnd'] + 2, 0, Date('Y')));
		$FromDate = Date($_SESSION['DefaultDateFormat'], Mktime(0, 0, 0, $_SESSION['YearEnd'] + 2, 0, Date('Y')));
	} else {
		$DefaultFromDate = Date('Y-m-d', Mktime(0, 0, 0, $_SESSION['YearEnd'] + 2, 0, Date('Y') - 1));
		$FromDate = Date($_SESSION['DefaultDateFormat'], Mktime(0, 0, 0, $_SESSION['YearEnd'] + 2, 0, Date('Y') - 1));
	}
	$period = GetPeriod($FromDate);

	/*Show a form to allow input of criteria for profit and loss to show */
	echo '<table class=selection><tr><td>' . _('Select Period From') . ':</td><td><select Name="FromPeriod">';

	$SQL = "SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno DESC";
	$Periods = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Periods)) {
		if (isset($_POST['FromPeriod']) and $_POST['FromPeriod'] != '') {
			if ($_POST['FromPeriod'] == $MyRow['periodno']) {
				echo '<option selected value=' . $MyRow['periodno'] . '>' . MonthAndYearFromSQLDate($MyRow['lastdate_in_period']) . '</option>';
			} else {
				echo '<option value=' . $MyRow['periodno'] . '>' . MonthAndYearFromSQLDate($MyRow['lastdate_in_period']) . '</option>';
			}
		} else {
			if ($MyRow['lastdate_in_period'] == $DefaultFromDate) {
				echo '<option selected value=' . $MyRow['periodno'] . '>' . MonthAndYearFromSQLDate($MyRow['lastdate_in_period']) . '</option>';
			} else {
				echo '<option value=' . $MyRow['periodno'] . '>' . MonthAndYearFromSQLDate($MyRow['lastdate_in_period']) . '</option>';
			}
		}
	}

	echo '</select></td></tr>';
	if (!isset($_POST['ToPeriod']) or $_POST['ToPeriod'] == '') {
		$LastDate = date('Y-m-d', mktime(0, 0, 0, Date('m') + 1, 0, Date('Y')));
		$SQL = "SELECT periodno FROM periods where lastdate_in_period = '" . $LastDate . "'";
		$MaxPrd = DB_query($SQL);
		$MaxPrdrow = DB_fetch_row($MaxPrd);
		$DefaultToPeriod = (int)($MaxPrdrow[0]);

	} else {
		$DefaultToPeriod = $_POST['ToPeriod'];
	}

	echo '<tr><td>' . _('Select Period To') . ':</td><td><select Name="ToPeriod">';

	$RetResult = DB_data_seek($Periods, 0);

	while ($MyRow = DB_fetch_array($Periods)) {

		if ($MyRow['periodno'] == $DefaultToPeriod) {
			echo '<option selected value=' . $MyRow['periodno'] . '>' . MonthAndYearFromSQLDate($MyRow['lastdate_in_period']) . '</option>';
		} else {
			echo '<option VALUE =' . $MyRow['periodno'] . '>' . MonthAndYearFromSQLDate($MyRow['lastdate_in_period']) . '</option>';
		}
	}
	echo '</select></td></tr>';
	//Select the tag
	$SQL = "SELECT name FROM divisions";
	$Result = DB_query($SQL);
	echo '<tr><td>' . _('Functional Unit') . ':</td>
			<td><select name="DefaultTag">';

	while ($MyRow = DB_fetch_array($Result)) {

		if (isset($_POST['DefaultTag']) and $MyRow['name'] == $_POST['DefaultTag']) {

			echo '<option selected value="' . $MyRow['name'] . '">' . $MyRow['name'] . '</option>';

		} else {
			echo '<option value="' . $MyRow['name'] . '">' . $MyRow['name'] . '</option>';

		}

	}
	echo '</select></td></tr>';
	// End select tag
	echo '<tr><td>' . _('Detail Or Summary') . ':</td><td><select Name="Detail">';
	echo '<option selected value="Summary">' . _('Summary') . '</option>';
	echo '<option selected value="Detailed">' . _('All Accounts') . '</option>';
	echo '</select></td></tr>';

	echo '</table><br />';

	echo '<div class="centre"><input type=submit Name="ShowPL" Value="' . _('Show Statement of Income and Expenditure') . '"><br />';
	echo '<br><input type=submit Name="PrintPDF" Value="' . _('PrintPDF') . '"></div>';

	/*Now do the posting while the user is thinking about the period to select */

	include ('includes/GLPostings.inc');

} else if (isset($_POST['PrintPDF'])) {

	$tagsql = "SELECT tagdescription FROM tags WHERE tagref='" . $_POST['tag'] . "'";
	$tagresult = DB_query($tagsql);
	$tagrow = DB_fetch_array($tagresult);
	$Tag = $tagrow['tagdescription'];
	include ('includes/PDFStarter.php');
	$PDF->addInfo('Title', _('Income and Expenditure'));
	$PDF->addInfo('Subject', _('Income and Expenditure'));
	$PageNumber = 0;
	$FontSize = 10;
	$line_height = 12;

	$NumberOfMonths = $_POST['ToPeriod'] - $_POST['FromPeriod'] + 1;

	if ($NumberOfMonths > 12) {
		include ('includes/header.php');
		echo '<p>';
		prnMsg(_('A period up to 12 months in duration can be specified') . ' - ' . _('the system automatically shows a comparative for the same period from the previous year') . ' - ' . _('it cannot do this if a period of more than 12 months is specified') . '. ' . _('Please select an alternative period range'), 'error');
		include ('includes/footer.php');
		exit;
	}

	$SQL = "SELECT lastdate_in_period FROM periods WHERE periodno='" . $_POST['ToPeriod'] . "'";
	$PrdResult = DB_query($SQL);
	$MyRow = DB_fetch_row($PrdResult);
	$PeriodToDate = MonthAndYearFromSQLDate($MyRow[0]);

	$SQL = "SELECT accountgroups.sectioninaccounts,
			accountgroups.groupname,
			accountgroups.parentgroupname,
			chartmaster.accountcode as account,
			chartmaster.accountname,
			(CASE WHEN (gltrans.periodno is NULL) THEN 0 ELSE
			(Sum(CASE WHEN (gltrans.periodno>='" . $_POST['FromPeriod'] . "' and gltrans.periodno<='" . $_POST['ToPeriod'] . "') THEN gltrans.amount ELSE 0 END)) END) AS TotalAllPeriods,
			(CASE WHEN (gltrans.periodno is NULL) THEN 0 ELSE
			(Sum(CASE WHEN (gltrans.periodno='" . $_POST['ToPeriod'] . "') THEN gltrans.amount ELSE 0 END)) END) AS TotalThisPeriod
		FROM chartmaster
		LEFT JOIN accountgroups
			ON chartmaster.group_ = accountgroups.groupname
		LEFT JOIN gltrans
			ON chartmaster.accountcode= gltrans.account
		WHERE accountgroups.pandl=1
			AND (gltrans.tag='" . $_POST['tag'] . "'
			OR gltrans.tag is NULL)
		GROUP BY accountgroups.sectioninaccounts,
			accountgroups.groupname,
			accountgroups.parentgroupname,
			chartmaster.accountcode,
			chartmaster.accountname,
			accountgroups.sequenceintb
		ORDER BY accountgroups.sectioninaccounts,
			accountgroups.sequenceintb,
			accountgroups.groupname,
			chartmaster.accountcode";

	$AccountsResult = DB_query($SQL);

	if (DB_error_no() != 0) {
		$Title = _('Income and Expenditure') . ' - ' . _('Problem Report') . '....';
		include ('includes/header.php');
		prnMsg(_('No general ledger accounts were returned by the SQL because') . ' - ' . DB_error_msg());
		echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		if ($debug == 1) {
			echo '<br />' . $SQL;
		}
		include ('includes/footer.php');
		exit;
	}
	if (DB_num_rows($AccountsResult) == 0) {
		$Title = _('Print Income and Expenditure Error');
		include ('includes/header.php');
		echo '<br />';
		prnMsg(_('There were no entries to print out for the selections specified'), 'info');
		echo '<br /><a href="' . $RootPath . '/index.php?' . SID . '">' . _('Back to the menu') . '</a>';
		include ('includes/footer.php');
		exit;
	}

	include ('includes/PDFTagProfitAndLossPageHeader.inc');

	$Section = '';
	$SectionPrdActual = 0;

	$ActGrp = '';
	$ParentGroups = array();
	$Level = 0;
	$ParentGroups[$Level] = '';
	$GrpPrdActual = array(0);
	$PeriodProfitLoss = 0;
	while ($MyRow = DB_fetch_array($AccountsResult)) {

		// Print heading if at end of page
		if ($YPos < ($Bottom_Margin)) {
			include ('includes/PDFTagProfitAndLossPageHeader.inc');
		}

		if ($MyRow['groupname'] != $ActGrp) {
			if ($ActGrp != '') {
				if ($MyRow['parentgroupname'] != $ActGrp) {
					while ($MyRow['groupname'] != $ParentGroups[$Level] and $Level > 0) {
						if ($_POST['Detail'] == 'Detailed') {
							$ActGrpLabel = $ParentGroups[$Level] . ' ' . _('total');
						} else {
							$ActGrpLabel = $ParentGroups[$Level];
						}
						if ($Section == 1) {
							/*Income */
							$LeftOvers = $PDF->addTextWrap($Left_Margin + ($Level * 10), $YPos, 200 - ($Level * 10), $FontSize, $ActGrpLabel);
							$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format($GrpPrdActual[$Level]), 'right');
							$YPos-= (2 * $line_height);
						} else {
							/*Costs */
							$LeftOvers = $PDF->addTextWrap($Left_Margin + ($Level * 10), $YPos, 200 - ($Level * 10), $FontSize, $ActGrpLabel);
							$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format(-$GrpPrdActual[$Level]), 'right');
							$YPos-= (2 * $line_height);
						}
						$GrpPrdLY[$Level] = 0;
						$GrpPrdActual[$Level] = 0;
						$GrpPrdBudget[$Level] = 0;
						$ParentGroups[$Level] = '';
						$Level--;
						// Print heading if at end of page
						if ($YPos < ($Bottom_Margin + (2 * $line_height))) {
							include ('includes/PDFTagProfitAndLossPageHeader.inc');
						}
					} //end of loop
					//still need to print out the group total for the same level
					if ($_POST['Detail'] == 'Detailed') {
						$ActGrpLabel = $ParentGroups[$Level] . ' ' . _('total');
					} else {
						$ActGrpLabel = $ParentGroups[$Level];
					}
					if ($Section == 1) {
						/*Income */
						$LeftOvers = $PDF->addTextWrap($Left_Margin + ($Level * 10), $YPos, 200 - ($Level * 10), $FontSize, $ActGrpLabel);
						$PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format($GrpPrdActual[$Level]), 'right');
						$YPos-= (2 * $line_height);
					} else {
						/*Costs */
						$LeftOvers = $PDF->addTextWrap($Left_Margin + ($Level * 10), $YPos, 200 - ($Level * 10), $FontSize, $ActGrpLabel);
						$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format(-$GrpPrdActual[$Level]), 'right');
						$YPos-= (2 * $line_height);
					}
					$GrpPrdActual[$Level] = 0;
					$ParentGroups[$Level] = '';
				}
			}
		}

		// Print heading if at end of page
		if ($YPos < ($Bottom_Margin + (2 * $line_height))) {
			include ('includes/PDFTagProfitAndLossPageHeader.inc');
		}

		if ($MyRow['sectioninaccounts'] != $Section) {

			$PDF->setFont('', 'B');
			$FontSize = 10;
			if ($Section != '') {
				$PDF->line($Left_Margin + 310, $YPos + $line_height, $Left_Margin + 500, $YPos + $line_height);
				$PDF->line($Left_Margin + 310, $YPos, $Left_Margin + 500, $YPos);
				if ($Section == 1) {
					/*Income*/

					$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 200, $FontSize, $Sections[$Section]);
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format($SectionPrdActual), 'right');
					$YPos-= (2 * $line_height);

					$TotalIncome = - $SectionPrdActual;
					$TotalBudgetIncome = - $SectionPrdBudget;
					$TotalLYIncome = - $SectionPrdLY;
				} else {
					$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 200, $FontSize, $Sections[$Section]);
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format(-$SectionPrdActual), 'right');
					$YPos-= (2 * $line_height);
				}
				if ($Section == 2) {
					/*Cost of Sales - need sub total for Gross Profit*/
					$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 200, $FontSize, _('Gross Profit'));
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format($TotalIncome - $SectionPrdActual), 'right');
					$PDF->line($Left_Margin + 310, $YPos + $line_height, $Left_Margin + 500, $YPos + $line_height);
					$PDF->line($Left_Margin + 310, $YPos, $Left_Margin + 500, $YPos);
					$YPos-= (2 * $line_height);

					if ($TotalIncome != 0) {
						$PrdGPPercent = 100 * ($TotalIncome - $SectionPrdActual) / $TotalIncome;
					} else {
						$PrdGPPercent = 0;
					}
					$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 200, $FontSize, _('Gross Profit Percent'));
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format($PrdGPPercent, 1) . '%', 'right');
					$YPos-= (2 * $line_height);
				}
			}
			$SectionPrdActual = 0;
			$SectionPrdBudget = 0;
			$SectionPrdLY = 0;

			$Section = $MyRow['sectioninaccounts'];

			if ($_POST['Detail'] == 'Detailed') {
				$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 200, $FontSize, $Sections[$MyRow['sectioninaccounts']]);
				$YPos-= (2 * $line_height);
			}
			$FontSize = 8;
			$PDF->setFont('', '');
		}

		if ($MyRow['groupname'] != $ActGrp) {
			if ($MyRow['parentgroupname'] == $ActGrp and $ActGrp != '') { //adding another level of nesting
				$Level++;
			}
			$ActGrp = $MyRow['groupname'];
			$ParentGroups[$Level] = $ActGrp;
			if ($_POST['Detail'] == 'Detailed') {
				$FontSize = 10;
				$PDF->setFont('', 'B');
				$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 200, $FontSize, $MyRow['groupname']);
				$YPos-= (2 * $line_height);
				$FontSize = 8;
				$PDF->setFont('', '');
			}
		}

		$AccountPeriodActual = $MyRow['TotalAllPeriods'];
		$PeriodProfitLoss+= $AccountPeriodActual;

		for ($i = 0;$i <= $Level;$i++) {
			//			$GrpPrdLY[$i] +=$AccountPeriodLY;
			
		}

		$SectionPrdActual+= $AccountPeriodActual;

		if ($_POST['Detail'] == _('Detailed')) {
			$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 60, $FontSize, $MyRow['account']);
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 60, $YPos, 190, $FontSize, $MyRow['accountname']);
			if ($Section == 1) {
				/*Income*/
				$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format($AccountPeriodActual), 'right');
			} else {
				$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format(-$AccountPeriodActual), 'right');
			}
			$YPos-= $line_height;
		}
	}
	//end of loop
	if ($ActGrp != '') {

		if ($MyRow['parentgroupname'] != $ActGrp) {

			while ($MyRow['groupname'] != $ParentGroups[$Level] and $Level > 0) {
				if ($_POST['Detail'] == 'Detailed') {
					$ActGrpLabel = $ParentGroups[$Level] . ' ' . _('total');
				} else {
					$ActGrpLabel = $ParentGroups[$Level];
				}
				if ($Section == 1) {
					/*Income */
					$LeftOvers = $PDF->addTextWrap($Left_Margin + ($Level * 10), $YPos, 200 - ($Level * 10), $FontSize, $ActGrpLabel);
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format($GrpPrdActual[$Level]), 'right');
					$YPos-= (2 * $line_height);
				} else {
					/*Costs */
					$LeftOvers = $PDF->addTextWrap($Left_Margin + ($Level * 10), $YPos, 200 - ($Level * 10), $FontSize, $ActGrpLabel);
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format(-$GrpPrdActual[$Level]), 'right');
					$YPos-= (2 * $line_height);
				}
				$GrpPrdActual[$Level] = 0;
				$ParentGroups[$Level] = '';
				$Level--;
				// Print heading if at end of page
				if ($YPos < ($Bottom_Margin + (2 * $line_height))) {
					include ('includes/PDFTagProfitAndLossPageHeader.inc');
				}
			}
			//still need to print out the group total for the same level
			if ($_POST['Detail'] == 'Detailed') {
				$ActGrpLabel = $ParentGroups[$Level] . ' ' . _('total');
			} else {
				$ActGrpLabel = $ParentGroups[$Level];
			}
			if ($Section == 1) {
				/*Income */
				$LeftOvers = $PDF->addTextWrap($Left_Margin + ($Level * 10), $YPos, 200 - ($Level * 10), $FontSize, $ActGrpLabel);
				$PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format($GrpPrdActual[$Level]), 'right');
				$YPos-= (2 * $line_height);
			} else {
				/*Costs */
				$LeftOvers = $PDF->addTextWrap($Left_Margin + ($Level * 10), $YPos, 200 - ($Level * 10), $FontSize, $ActGrpLabel);
				$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format(-$GrpPrdActual[$Level]), 'right');
				$YPos-= (2 * $line_height);
			}
			$GrpPrdActual[$Level] = 0;
			$ParentGroups[$Level] = '';
		}
	}
	// Print heading if at end of page
	if ($YPos < ($Bottom_Margin + (2 * $line_height))) {
		include ('includes/PDFTagProfitAndLossPageHeader.inc');
	}
	if ($Section != '') {

		$PDF->setFont('', 'B');
		$PDF->line($Left_Margin + 310, $YPos + 10, $Left_Margin + 500, $YPos + 10);
		$PDF->line($Left_Margin + 310, $YPos, $Left_Margin + 500, $YPos);

		if ($Section == 1) {
			/*Income*/
			$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 200, $FontSize, $Sections[$Section]);
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format(-$SectionPrdActual), 'right');
			$YPos-= (2 * $line_height);

			$TotalIncome = - $SectionPrdActual;
			$TotalBudgetIncome = - $SectionPrdBudget;
			$TotalLYIncome = - $SectionPrdLY;
		} else {
			$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 60, $FontSize, $Sections[$Section]);
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format($SectionPrdActual), 'right');
			$YPos-= (2 * $line_height);
		}
		if ($Section == 2) {
			/*Cost of Sales - need sub total for Gross Profit*/
			$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 60, $FontSize, _('Gross Profit'));
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format($TotalIncome - $SectionPrdActual), 'right');
			$YPos-= (2 * $line_height);

			$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format(100 * ($TotalIncome - $SectionPrdActual) / $TotalIncome, 1) . '%', 'right');
			$YPos-= (2 * $line_height);
		}
	}

	$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 60, $FontSize, _('Profit') . ' - ' . _('Loss'));
	$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, number_format(-$PeriodProfitLoss), 'right');

	$PDF->line($Left_Margin + 310, $YPos + $line_height, $Left_Margin + 500, $YPos + $line_height);
	$PDF->line($Left_Margin + 310, $YPos, $Left_Margin + 500, $YPos);

	$PDF->OutputD($_SESSION['DatabaseName'] . '_' . 'Tag_Income_Statement_' . date('Y-m-d') . '.pdf');
	$PDF->__destruct();
	exit;

} else {

	include ('includes/header.php');
	echo '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<input type="hidden" name="FromPeriod" value="' . $_POST['FromPeriod'] . '" />
			<input type=hidden name="ToPeriod" value="' . $_POST['ToPeriod'] . '" />';

	$NumberOfMonths = $_POST['ToPeriod'] - $_POST['FromPeriod'] + 1;

	if ($NumberOfMonths > 12) {
		echo '<p>';
		prnMsg(_('A period up to 12 months in duration can be specified') . ' - ' . _('the system automatically shows a comparative for the same period from the previous year') . ' - ' . _('it cannot do this if a period of more than 12 months is specified') . '. ' . _('Please select an alternative period range'), 'error');
		include ('includes/footer.php');
		exit;
	}

	$SQL = "SELECT lastdate_in_period FROM periods WHERE periodno='" . $_POST['ToPeriod'] . "'";
	$PrdResult = DB_query($SQL);
	$MyRow = DB_fetch_row($PrdResult);
	$PeriodToDate = MonthAndYearFromSQLDate($MyRow[0]);

	$SQL = "SELECT accountgroups.sectioninaccounts,
			accountgroups.groupname,
			accountgroups.parentgroupname,
			chartmaster.accountcode as account,
			chartmaster.accountname,
			(CASE WHEN (gltrans.periodno is NULL) THEN 0 ELSE
			(Sum(CASE WHEN (gltrans.periodno>='" . $_POST['FromPeriod'] . "' and gltrans.periodno<='" . $_POST['ToPeriod'] . "') THEN gltrans.amount ELSE 0 END)) END) AS TotalAllPeriods,
			(CASE WHEN (gltrans.periodno is NULL) THEN 0 ELSE
			(Sum(CASE WHEN (gltrans.periodno='" . $_POST['ToPeriod'] . "') THEN gltrans.amount ELSE 0 END)) END) AS TotalThisPeriod
		FROM chartmaster
		LEFT JOIN accountgroups
			ON chartmaster.group_ = accountgroups.groupname
		LEFT JOIN gltrans
			ON chartmaster.accountcode= gltrans.account
		WHERE accountgroups.pandl=1
			AND (gltrans.tag='" . $_POST['tag'] . "'
			OR gltrans.tag is NULL)
		GROUP BY accountgroups.sectioninaccounts,
			accountgroups.groupname,
			accountgroups.parentgroupname,
			chartmaster.accountcode,
			chartmaster.accountname,
			accountgroups.sequenceintb
		ORDER BY accountgroups.sectioninaccounts,
			accountgroups.sequenceintb,
			accountgroups.groupname,
			chartmaster.accountcode";

	$AccountsResult = DB_query($SQL, _('No general ledger accounts were returned by the SQL because'), _('The SQL that failed was'));

	/*show a table of the accounts info returned by the SQL
	 Account Code ,   Account Name , Month Actual, Month Budget, Period Actual, Period Budget */
	echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/money_add.png" title="' . _('Print') . '" alt="" />' . ' ' . _('Statement of Income and Expenditure for') . ' ' . $_POST['tag'] . '</p>';

	echo '<table cellpadding=2 class=selection>';
	echo '<tr><th colspan=6><div class="centre"><font size=3 color=blue><b>' . _('Statement of Income and Expenditure for') . ' ' . $_POST['tag'] . ' ' . _('during the') . ' ' . $NumberOfMonths . ' ' . _('months to') . ' ' . $PeriodToDate . '</b></font></div></th></tr>';

	if ($_POST['Detail'] == 'Detailed') {
		$TableHeader = '<tr>
				<th>' . _('Account') . '</th>
				<th>' . _('Account Name') . '</th>
				<th colspan=2>' . _('Month Actual') . '</th>
				<th colspan=2>' . _('Period Actual') . '</th>
				</tr>';
	} else {
		/*summary */
		$TableHeader = '<tr>
				<th colspan=2></th>
				<th colspan=2>' . _('Month Actual') . '</th>
				<th colspan=2>' . _('Period Actual') . '</th>
				</tr>';
	}

	$j = 1;
	$k = 0; //row colour counter
	$Section = '';
	$SectionThisPrdActual = 0;
	$SectionPrdActual = 0;
	$SectionPrdLY = 0;
	$SectionPrdBudget = 0;

	$PeriodProfitLoss = 0;
	$ThisPeriodProfitLoss = 0;
	$PeriodLYProfitLoss = 0;
	$PeriodBudgetProfitLoss = 0;

	$ActGrp = '';
	$ParentGroups = array();
	$Level = 0;
	$ParentGroups[$Level] = '';
	$GrpThisPrdActual = array(0);
	$GrpPrdActual = array(0);
	$GrpPrdLY = array(0);
	$GrpPrdBudget = array(0);

	while ($MyRow = DB_fetch_array($AccountsResult)) {

		if ($MyRow['groupname'] != $ActGrp) {
			if ($MyRow['parentgroupname'] != $ActGrp and $ActGrp != '') {
				while ($MyRow['groupname'] != $ParentGroups[$Level] and $Level > 0) {
					if ($_POST['Detail'] == 'Detailed') {
						echo '<tr>
							<td colspan=2></td>
							<td colspan=4><hr></td>
						</tr>';
						$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level] . ' ' . _('total');
					} else {
						$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level];
					}

					if ($Section == 3) {
						/*Income */
						printf('<tr>
							<td colspan=2><font size=2><i>%s </i></font></td>
							<td></td>
							<td class=number colspan=2>%s</td>
							<td class=number colspan=2>%s</td>
							</tr>', $ActGrpLabel, number_format($GrpThisPrdActual[$Level]), number_format($GrpPrdActual[$Level]));
					} else {
						/*Costs */
						printf('<tr>
							<td colspan=2><font size=2><i>%s </i></font></td>
							<td class=number colspan=2>%s</td>
							<td class=number colspan=2>%s</td>
							<td></td>
							</tr>', $ActGrpLabel, number_format(-$GrpThisPrdActual[$Level]), number_format(-$GrpPrdActual[$Level]));
					}
					$GrpPrdLY[$Level] = 0;
					$GrpPrdActual[$Level] = 0;
					$GrpThisPrdActual[$Level] = 0;
					$GrpPrdBudget[$Level] = 0;
					$ParentGroups[$Level] = '';
					$Level--;
				} //end while
				//still need to print out the old group totals
				if ($_POST['Detail'] == 'Detailed') {
					echo '<tr>
							<td colspan=2></td>
							<td colspan=4><hr></td>
						</tr>';
					$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level] . ' ' . _('total');
				} else {
					$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level];
				}

				if ($Section == 4) {
					/*Income */
					printf('<tr>
						<td colspan=2><font size=2><i>%s </i></font></td>
						<td></td>
						<td class=number colspan=2>%s</td>
						<td class=number colspan=2>%s</td>
						</tr>', $ActGrpLabel, number_format(-$GrpThisPrdActual[$Level]), number_format(-$GrpPrdActual[$Level]));
				} else {
					/*Costs */
					printf('<tr>
						<td colspan=2><font size=2><i>%s </i></font></td>
						<td class=number colspan=2>%s</td>
						<td class=number colspan=2>%s</td>
						<td></td>
						</tr>', $ActGrpLabel, number_format(-$GrpThisPrdActual[$Level]), number_format(-$GrpPrdActual[$Level]));
				}
				$GrpPrdActual[$Level] = 0;
				$GrpThisPrdActual[$Level] = 0;
				$ParentGroups[$Level] = '';
			}
			$j++;
		}

		if ($MyRow['sectioninaccounts'] != $Section) {

			if ($SectionPrdLY + $SectionPrdActual + $SectionPrdBudget != 0) {
				if ($Section == 4) {
					/*Income*/

					echo '<tr>
						<td colspan=3></td>
						<td colspan=4><hr></td>
					</tr>';

					printf('<tr>
					<td colspan=2><font size=4>%s</font></td>
					<td></td>
					<td class=number>%s</td>
					<td class=number>%s</td>
					</tr>', $Sections[$Section], number_format($SectionThisPrdActual), number_format($SectionPrdActual));
					$TotalIncome = - $SectionPrdActual;
				} else {
					echo '<tr>
					<td colspan=2></td>
					<td colspan=4><hr></td>
					<td></td>
					</tr>';
					printf('<tr>
					<td colspan=2><font size=4>%s</font></td>
					<td></td>
					<td class=number>%s</td>
					<td class=number>%s</td>
					</tr>', $Sections[$Section], number_format($SectionThisPrdActual), number_format($SectionPrdActual));
				}
				if ($Section == 2) {
					/*Cost of Sales - need sub total for Gross Profit*/
					echo '<tr>
						<td colspan=2></td>
						<td colspan=4><hr></td>
					</tr>';
					printf('<tr>
						<td colspan=2><font size=4>' . _('Gross Profit') . '</font></td>
						<td></td>
						<td class=number>%s</td>
						<td class=number>%s</td>
						</tr>', number_format($TotalIncome - $SectionThisPrdActual), number_format($TotalIncome - $SectionPrdActual));

					if ($TotalIncome != 0) {
						$PrdGPPercent = 100 * ($TotalIncome - $SectionPrdActual) / $TotalIncome;
					} else {
						$PrdGPPercent = 0;
					}
					echo '<tr>
						<td colspan=2></td>
						<td colspan=4><hr></td>
					</tr>';
					printf('<tr>
						<td colspan=2><font size=2><i>' . _('Gross Profit Percent') . '</i></font></td>
						<td></td>
						<td class=number><i>%s</i></td>
						<td class=number><i>%s</i></td>
						</tr><tr><td colspan=6> </td></tr>', number_format($PrdGPPercent, 1) . '%', number_format($PrdGPPercent, 1) . '%');
					$j++;
				}
			}
			$SectionPrdActual = 0;

			$Section = $MyRow['sectioninaccounts'];

			if ($_POST['Detail'] == 'Detailed') {
				printf('<tr>
					<td colspan=6><font size=4 color=blue><b>%s</b></font></td>
					</tr>', $Sections[$MyRow['sectioninaccounts']]);
			}
			$j++;

		}

		if ($MyRow['groupname'] != $ActGrp) {

			if ($MyRow['parentgroupname'] == $ActGrp and $ActGrp != '') { //adding another level of nesting
				$Level++;
			}

			$ParentGroups[$Level] = $MyRow['groupname'];
			$ActGrp = $MyRow['groupname'];
			if ($_POST['Detail'] == 'Detailed') {
				printf('<tr>
					<td colspan=6><font size=2 color=blue><b>%s</b></font></td>
					</tr>', $MyRow['groupname']);
				echo $TableHeader;
			}
		}

		$AccountThisPeriodActual = $MyRow['TotalThisPeriod'];
		$AccountPeriodActual = $MyRow['TotalAllPeriods'];
		if ($Section == 4) {
			$PeriodProfitLoss-= $AccountPeriodActual;
			$ThisPeriodProfitLoss-= $AccountThisPeriodActual;
		} else {
			$PeriodProfitLoss-= $AccountPeriodActual;
			$ThisPeriodProfitLoss-= $AccountThisPeriodActual;
		}

		for ($i = 0;$i <= $Level;$i++) {
			if (!isset($GrpPrdActual[$i])) {
				$GrpPrdActual[$i] = 0;
			}
			$GrpPrdActual[$i]+= $AccountPeriodActual;
			$GrpThisPrdActual[$i]+= $AccountThisPeriodActual;
		}
		$SectionPrdActual-= $AccountPeriodActual;

		if ($_POST['Detail'] == _('Detailed')) {

			$ActEnquiryURL = '<a href="' . $RootPath . '/GLAccountInquiry.php?Period=' . $_POST['ToPeriod'] . '&Account=' . $MyRow['account'] . '&Show=Yes">' . $MyRow['account'] . '</a>';

			if ($Section == 4) {
				echo '<tr class="striped_row">
						<td>', $ActEnquiryURL, '</td>
						<td>', $MyRow['accountname'], '</td>
						<td></td>
						<td class=number>', number_format(-$AccountThisPeriodActual), '</td>
						<td class=number>', number_format(-$AccountPeriodActual), '</td>
					</tr>';
			} else {
				echo '<tr class="striped_row">
						<td>', $ActEnquiryURL, '</td>
						<td>', $MyRow['accountname'], 's</td>
						<td class=number colspan=2>', number_format(-$AccountThisPeriodActual), '</td>
						<td class=number colspan=2>', number_format(-$AccountPeriodActual), '</td>
					</tr>';
			}

			$j++;
		}
	}
	//end of loop
	

	if ($MyRow['groupname'] != $ActGrp) {
		if ($MyRow['parentgroupname'] != $ActGrp and $ActGrp != '') {
			while ($MyRow['groupname'] != $ParentGroups[$Level] and $Level > 0) {
				if ($_POST['Detail'] == 'Detailed') {
					echo '<tr>
						<td colspan=2></td>
						<td colspan=4><hr></td>
					</tr>';
					$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level] . ' ' . _('total');
				} else {
					$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level];
				}
				if ($Section == 4) {
					/*Income */
					printf('<tr>
						<td colspan=2><font size=2><i>%s </i></font></td>
						<td class=number>%s</td>
						<td></td>
						<td class=number>%s</td>
						<td></td>
						<td class=number>%s</td>
						</tr>', $ActGrpLabel, number_format(-$GrpThisPrdActual[$Level]), number_format(-$GrpPrdActual[$Level]));
				} else {
					/*Costs */
					printf('<tr>
						<td colspan=2><font size=2><i>%s </i></font></td>
						<td class=number>%s</td>
						<td class=number>%s</td>
						<td></td>
						</tr>', $ActGrpLabel, number_format($GrpThisPrdActual[$Level]), number_format($GrpPrdActual[$Level]));
				}
				$GrpPrdActual[$Level] = 0;
				$ParentGroups[$Level] = '';
				$Level--;
			} //end while
			//still need to print out the old group totals
			if ($_POST['Detail'] == 'Detailed') {
				echo '<tr>
						<td colspan=2></td>
						<td colspan=4><hr></td>
					</tr>';
				$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level] . ' ' . _('total');
			} else {
				$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level];
			}

			if ($Section == 4) {
				/*Income */
				printf('<tr>
					<td colspan=2><font size=2><i>%s </i></font></td>
					<td></td>
					<td class=number colspan=2>%s</td>
					<td class=number colspan=2>%s</td>
					</tr>', $ActGrpLabel, number_format(-$GrpThisPrdActual[$Level]), number_format(-$GrpPrdActual[$Level]));
			} else {
				/*Costs */
				printf('<tr>
					<td colspan=2><font size=2><i>%s </i></font></td>
					<td class=number colspan=2>%s</td>
					<td class=number colspan=2>%s</td>
					<td></td>
					</tr>', $ActGrpLabel, number_format($GrpPrdActual[$Level]), number_format($GrpPrdActual[$Level]));
			}
			$GrpPrdActual[$Level] = 0;
			$ParentGroups[$Level] = '';
		}
		$j++;
	}

	if ($MyRow['sectioninaccounts'] != $Section) {

		if ($Section == 4) {
			/*Income*/

			echo '<tr>
				<td colspan=3></td>
				<td colspan=4><hr></td>
			</tr>';

			printf('<tr>
			<td colspan=2><font size=4>%s</font></td>
			<td></td>
			<td class=number>%s</td>
			</tr>', $Sections[$Section], number_format($SectionPrdActual));
			$TotalIncome = $SectionPrdActual;
		} else {
			echo '<tr>
			<td colspan=2></td>
			<td colspan=4><hr></td>
			</tr>';
			printf('<tr>
			<td colspan=2><font size=4>%s</font></td>
			<td></td>
			<td class=number>%s</td>
			<td class=number>%s</td>
			</tr>', $Sections[$Section], number_format(-$SectionThisPrdActual), number_format(-$SectionPrdActual));
		}
		if ($Section == 2) {
			/*Cost of Sales - need sub total for Gross Profit*/
			echo '<tr>
				<td colspan=2></td>
				<td colspan=4><hr></td>
			</tr>';
			printf('<tr>
				<td colspan=2><font size=4>' . _('Gross Profit') . '</font></td>
				<td></td>
				<td class=number>%s</td>
				<td></td>
				<td class=number>%s</td>
				<td></td>
				<td class=number>%s</td>
				</tr>', number_format($TotalIncome - $SectionPrdActual));

			if ($TotalIncome != 0) {
				$PrdGPPercent = 100 * ($TotalIncome - $SectionPrdActual) / $TotalIncome;
			} else {
				$PrdGPPercent = 0;
			}
			echo '<tr>
				<td colspan=2></td>
				<td colspan=6><hr></td>
			</tr>';
			printf('<tr>
				<td colspan=2><font size=2><i>' . _('Gross Profit Percent') . '</i></font></td>
				<td></td>
				<td class=number><i>%s</i></td>
				<td></td>
				<td class=number><i>%s</i></td>
				<td></td>
				<td class=number><i>%s</i></td>
				</tr><tr><td colspan=4> </td></tr>', number_format($PrdGPPercent, 1) . '%');
			$j++;
		}

		$SectionPrdActual = 0;

		$Section = $MyRow['sectioninaccounts'];

		if ($_POST['Detail'] == 'Detailed' and isset($Sections[$MyRow['sectioninaccounts']])) {
			printf('<tr>
				<td colspan=6><font size=4 color=blue><b>%s</b></font></td>
				</tr>', $Sections[$MyRow['sectioninaccounts']]);
		}
		$j++;

	}

	echo '<tr>
		<td colspan=2></td>
		<td colspan=4><hr></td>
		</tr>';

	printf('<tr>
		<td colspan=2><font size=4 color=blue><b>' . _('Surplus') . ' - ' . _('Deficit') . '</b></font></td>
		<td></td>
		<td class=number>%s</td>
		<td class=number>%s</td>
		</tr>', number_format($ThisPeriodProfitLoss), number_format($PeriodProfitLoss));

	echo '<tr>
		<td colspan=2></td>
		<td colspan=4><hr></td>
		</tr>';

	echo '</table>';
	echo '<br /><div class="centre"><input type=submit Name="SelectADifferentPeriod" Value="' . _('Select A Different Period') . '"></div>';
}
echo '</form>';
include ('includes/footer.php');

?>