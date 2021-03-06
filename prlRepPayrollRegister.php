<?php
$PageSecurity = 2;

if (isset($_POST['PrintPDF']) and isset($_POST['PayrollID'])) {

	include ('config.php');
	include ('includes/PDFStarter.php');
	include ('includes/ConnectDB.php');
	include ('includes/DateFunctions.php');
	include ('includes/prlFunctions.php');

	/* A4_Landscape */

	$Page_Width = 842;
	$Page_Height = 595;
	$Top_Margin = 20;
	$Bottom_Margin = 20;
	$Left_Margin = 25;
	$Right_Margin = 22;

	$PageSize = array(0, 0, $Page_Width, $Page_Height);
	$PDF = new Cpdf($PageSize);

	$PageNumber = 0;

	$PDF->selectFont('./fonts/Helvetica.afm');

	/* Standard PDF file creation header stuff */
	$PDF->addinfo('Title', _('Payroll Register'));
	$PDF->addinfo('Subject', _('Payroll Register'));

	$PageNumber = 1;
	$line_height = 12;

	$PayDesc = GetPayrollRow($_POST['PayrollID'], 1);
	$FromPeriod = GetPayrollRow($_POST['PayrollID'], 3);
	$ToPeriod = GetPayrollRow($_POST['PayrollID'], 4);
	$PageNumber = 0;
	$FontSize = 10;
	$PDF->addinfo('Title', _('Payroll Register'));
	$PDF->addinfo('Subject', _('Payroll Register'));
	$line_height = 12;
	$EmpID = '';
	$Basic = 0;
	$OthInc = 0;
	$Lates = 0;
	$Absent = 0;
	$OT = 0;
	$Gross = 0;
	$SSS = 0;
	$HDMF = '';
	$PhilHealt = 0;
	$Loan = 0;
	$Tax = 0;
	$Net = 0;
	include ('includes/PDFPayRegisterPageHeader.php');
	$k = 0; //row colour counter
	$SQL = "SELECT employeeid,basicpay,othincome,absent,late,otpay,grosspay,loandeduction,sss,hdmf,philhealth,tax,netpay
			FROM prlpayrolltrans
			WHERE prlpayrolltrans.payrollid='" . $_POST['PayrollID'] . "'";
	$PayResult = DB_query($SQL);
	if (DB_num_rows($PayResult) > 0) {
		while ($MyRow = DB_fetch_array($PayResult)) {
			$EmpID = $MyRow['employeeid'];
			$FullName = GetName($EmpID);
			$Basic = $MyRow['basicpay'];
			$OthInc = $MyRow['othincome'];
			$OT = $MyRow['otpay'];
			$Gross = $MyRow['grosspay'];
			$SSS = $MyRow['sss'];
			$HDMF = $MyRow['hdmf'];
			$PhilHealth = $MyRow['philhealth'];
			$Loan = $MyRow['loandeduction'];
			$Tax = $MyRow['tax'];
			$Net = $MyRow['netpay'];

			$GTBasic+= $MyRow['basicpay'];
			$GTOthInc+= $MyRow['othincome'];
			$GTOT+= $MyRow['otpay'];
			$GTGross+= $MyRow['grosspay'];
			$GTSSS+= $MyRow['sss'];
			$GTHDMF+= $MyRow['hdmf'];
			$GTPhilHealth+= $MyRow['philhealth'];
			$GTLoan+= $MyRow['loandeduction'];
			$GTTax+= $MyRow['tax'];
			$GTNet+= $MyRow['netpay'];

			//$YPos -= (2 * $line_height);  //double spacing
			$FontSize = 8;
			$PDF->selectFont('./fonts/Helvetica.afm');
			$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 50, $FontSize, $EmpID);
			$LeftOvers = $PDF->addTextWrap(100, $YPos, 120, $FontSize, $FullName, 'left');
			$LeftOvers = $PDF->addTextWrap(221, $YPos, 50, $FontSize, number_format($Basic, 2), 'right');
			$LeftOvers = $PDF->addTextWrap(272, $YPos, 50, $FontSize, number_format($OthInc, 2), 'right');
			$LeftOvers = $PDF->addTextWrap(313, $YPos, 50, $FontSize, number_format($Lates, 2), 'right');
			$LeftOvers = $PDF->addTextWrap(354, $YPos, 50, $FontSize, number_format($Absent, 2), 'right');
			$LeftOvers = $PDF->addTextWrap(395, $YPos, 50, $FontSize, number_format($OT, 2), 'right');
			$LeftOvers = $PDF->addTextWrap(446, $YPos, 50, $FontSize, number_format($Gross, 2), 'right');
			$LeftOvers = $PDF->addTextWrap(487, $YPos, 50, $FontSize, number_format($SSS, 2), 'right');
			$LeftOvers = $PDF->addTextWrap(528, $YPos, 50, $FontSize, number_format($HDMF, 2), 'right');
			$LeftOvers = $PDF->addTextWrap(569, $YPos, 50, $FontSize, number_format($PhilHealth, 2), 'right');
			$LeftOvers = $PDF->addTextWrap(610, $YPos, 50, $FontSize, number_format($Loan, 2), 'right');
			$LeftOvers = $PDF->addTextWrap(671, $YPos, 50, $FontSize, number_format($Tax, 2), 'right');
			$LeftOvers = $PDF->addTextWrap(722, $YPos, 50, $FontSize, number_format($Net, 2), 'right');
			$YPos-= $line_height;
			if ($YPos < ($Bottom_Margin)) {
				include ('includes/PDFPayRegisterPageHeader.php');
			}
		}

	} //end of loop
	$LeftOvers = $PDF->line($Page_Width - $Right_Margin, $YPos, $Left_Margin, $YPos);
	$YPos-= (2 * $line_height);
	$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 150, $FontSize, 'Grand Total');
	$LeftOvers = $PDF->addTextWrap(221, $YPos, 50, $FontSize, number_format($GTBasic, 2), 'right');
	$LeftOvers = $PDF->addTextWrap(221, $YPos, 50, $FontSize, number_format($GTBasic, 2), 'right');
	$LeftOvers = $PDF->addTextWrap(272, $YPos, 50, $FontSize, number_format($GTOthInc, 2), 'right');
	$LeftOvers = $PDF->addTextWrap(313, $YPos, 50, $FontSize, number_format($GTLates, 2), 'right');
	$LeftOvers = $PDF->addTextWrap(354, $YPos, 50, $FontSize, number_format($GTAbsent, 2), 'right');
	$LeftOvers = $PDF->addTextWrap(395, $YPos, 50, $FontSize, number_format($GTOT, 2), 'right');
	$LeftOvers = $PDF->addTextWrap(446, $YPos, 50, $FontSize, number_format($GTGross, 2), 'right');
	$LeftOvers = $PDF->addTextWrap(487, $YPos, 50, $FontSize, number_format($GTSSS, 2), 'right');
	$LeftOvers = $PDF->addTextWrap(528, $YPos, 50, $FontSize, number_format($GTHDMF, 2), 'right');
	$LeftOvers = $PDF->addTextWrap(569, $YPos, 50, $FontSize, number_format($GTPhilHealth, 2), 'right');
	$LeftOvers = $PDF->addTextWrap(610, $YPos, 50, $FontSize, number_format($GTLoan, 2), 'right');
	$LeftOvers = $PDF->addTextWrap(671, $YPos, 50, $FontSize, number_format($GTTax, 2), 'right');
	$LeftOvers = $PDF->addTextWrap(722, $YPos, 50, $FontSize, number_format($GTNet, 2), 'right');

	$LeftOvers = $PDF->line($Page_Width - $Right_Margin, $YPos, $Left_Margin, $YPos);

	$PDFcode = $PDF->output();
	$len = strlen($PDFcode);
	if ($len <= 20) {
		$Title = _('Payroll Register Error');
		include ('includes/header.php');
		echo '<p>';
		prnMsg(_('There were no entries to print out for the selections specified'));
		echo '<BR><A HREF="' . $RootPath . '/index.php?' . SID . '">' . _('Back to the menu') . '</A>';
		include ('includes/footer.php');
		exit;
	} else {
		header('Content-type: application/pdf');
		header('Content-Length: ' . $len);
		header('Content-Disposition: inline; filename=PayrollRegister.pdf');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');

		$PDF->Stream();

	}
	exit;

} elseif (isset($_POST['ShowPR'])) {
	include ('includes/session.php');
	$Title = _('PhilHealth Monthly Premium Listing');
	include ('includes/header.php');
	echo 'Use PrintPDF instead';
	echo "<BR><A HREF='" . $RootPath . "/index.php?" . SID . "'>" . _('Back to the menu') . '</A>';
	include ('includes/footer.php');
	exit;
} else { /*The option to print PDF was not hit */

	include ('includes/session.php');
	$Title = _('Payroll Register');
	include ('includes/header.php');

	echo '<form method="POST" ACTION="' . basename(__FILE__) . '?' . SID . '">';
	echo '<table><tr><td>' . _('Select Payroll:') . '</td><td><select Name="PayrollID">';
	DB_data_seek($Result, 0);
	$SQL = 'SELECT payrollid, payrolldesc FROM prlpayrollperiod';
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['payrollid'] == $_POST['PayrollID']) {
			echo '<option selected="selected" value=';
		} else {
			echo '<option value=';
		}
		echo $MyRow['payrollid'] . '>' . $MyRow['payrolldesc'];
	} //end while loop
	echo '</select></td></tr>';
	echo '</table><P><input type="submit" name="ShowPR" value="' . _('Show Payroll Register') . '">';
	echo '<P><input type="submit" name="PrintPDF" value="' . _('PrintPDF') . '">';

	include ('includes/footer.php');;
} /*end of else not PrintPDF */

?>