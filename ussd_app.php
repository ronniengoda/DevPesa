<?php 
include 'functions.php';

// Reads the variables sent via POST from our gateway
$sessionId   = $_POST["sessionId"];
$serviceCode = $_POST["serviceCode"];
$phoneNumber = $_POST["phoneNumber"];
$text        = $_POST["text"];

//Explode text responses to only get required text without *
$text_exploded = explode ("*",$text);
$textFinal=trim(end($text_exploded));

//set and get ussd level
$getLevel=sqlValue("SELECT level FROM ussd_levels WHERE session_id='$sessionId' ORDER BY id DESC LIMIT 1");
if ($getLevel=="") {
  # code...
	$level=0;
}
else{
	$level=$getLevel;
}

//Validate if the phone number initiating the request is registered.
$validatePhone=checkPhoneNumber($phoneNumber);

if ($validatePhone=='FALSE' && $text=="") {
	# code...register user
	//This is the first request. Note how we start the response with CON.New User
	$response  = "CON Welcome to DevPesa.Select Option \n";
	$response .= "6. Register Account";
}
elseif($validatePhone=='TRUE' && $text==""){
	# Dispaly startup menu
	 //Business logic for first level response.Registered user.
	$response = "CON Welcome to DevPesa.Select Option \n";
	$response .= "1. My Wallet Balance \n";
	$response .= "2. Top Up Wallet \n";
	$response .= "3. Withdraw From Wallet \n";
	$response .= "4. Buy Airtime \n";
	$response .= "5. Send Cash \n";
}

else if ($text == "6") {
    // Business logic for first level response.Enter name
	sql("INSERT INTO ussd_levels SET session_id='$sessionId',level='11'");
	$response  = "CON Enter Your Full Name \n";

}

elseif ($level=="11") {
  # code...Enter second name
	if (strlen($textFinal)==0) {
    # code...
		$response  = "END Please Provide Your Full Name";
	}
	elseif (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $textFinal))
	{
		$response  = "END Sorry name cannot contain speacial characters";
	}
	else{
		sql("UPDATE ussd_levels SET full_name='$textFinal',level='12' WHERE session_id='$sessionId'");
		$response  = "CON Enter Your Email \n";
	}
}

elseif ($level=="12") {
  # code...Save Data and register account.
	// Remove all illegal characters from email
	$textFinal = filter_var($textFinal, FILTER_SANITIZE_EMAIL);
	if (strlen($textFinal)==0) {
    # code...
		$response  = "END Please Provide Your Email";
	}
	elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$response  = "END Please Provide A Valid Email";
	}
	else{
		sql("UPDATE ussd_levels SET email='$textFinal' WHERE session_id='$sessionId'");
		$Email=sqlValue("SELECT  email FROM ussd_levels WHERE session_id='$sessionId'");
		$FullName=sqlValue("SELECT  full_name FROM ussd_levels WHERE session_id='$sessionId'");
		$PhoneNumber=$phoneNumber;
		registerAccount($Email,$FullName,$PhoneNumber);
		$response  = "END DevPesa Account Successfully Created";
	}
}

//Display wallet balance
elseif ($text=='1') {
	# code...
	$balance=getWalletBalance($phoneNumber);
	$response  = "END Your DevPesa Wallet Balance Is Ksh ".$balance;
}

//Prompt user to provide amount to top up
elseif ($text=='2') {
	# code...
	sql("INSERT INTO ussd_levels SET session_id='$sessionId',level='21'");
	$response="CON Enter Amount To Topup \n";
}

//Process topup request
elseif ($level=="21") {
  # code...
	if (strlen($textFinal)==0) {
    # code...
		$response  = "END Please Provide Amount";
	}
	elseif(!ctype_digit($textFinal)){
		$response  = "END Amount Should Not Contain Non Numeric Values";
	}
	elseif ($textFinal<10) {
  	# code...
		$response  = "END Amount Cannot be less than 10";
	}
	else{
		$response  = processSTK($textFinal,$phoneNumber);
	}
}

//prompt user to enter amount to withdraw
elseif ($text=='3') {
	# code...
	sql("INSERT INTO ussd_levels SET session_id='$sessionId',level='31'");
	$response="CON Enter Amount To Withdraw \n";
}

//process withdrawal request.
elseif ($level=="31") {
  # code...
	if (strlen($textFinal)==0) {
    # code...
		$response  = "END Please Provide Amount";
	}
	elseif(!ctype_digit($textFinal)){
		$response  = "END Amount Should Not Contain Non Numeric Values";
	}
	elseif ($textFinal<10) {
  	# code...
		$response  = "END Amount Cannot be less than 10";
	}
	else{
		$response  = processB2C($textFinal,$phoneNumber);
	}
}

//Buy airtime menu
elseif ($text=='4') {
	# code...
	$response = "CON DevPesa Buy Airtime.Select Option \n";
	$response .= "41. My Phone \n";
	$response .= "42. Other Phone \n";
}

//Buy airtime for self
elseif ($textFinal=='41') {
	# code...process airtime for self
	sql("INSERT INTO ussd_levels SET session_id='$sessionId',level='411'");
	$response="CON Enter Amount \n";
}

//Process the buy airtime self request.
elseif ($level=='411') {
	# code...
	if (strlen($textFinal)==0) {
    # code...
		$response  = "END Please Provide Amount";
	}
	elseif(!ctype_digit($textFinal)){
		$response  = "END Amount Should Not Contain Non Numeric Values";
	}
	elseif ($textFinal<10) {
  	# code...
		$response  = "END Amount Cannot be less than 10";
	}
	else{
		$response  = processAirtime($textFinal,$phoneNumber,$phoneNumber);
	}
}

//Buy airtime for other phone number
elseif ($textFinal=='42') {
	# code...process airtime for other
	sql("INSERT INTO ussd_levels SET session_id='$sessionId',level='421'");
	$response="CON Enter Other Phone Number \n";
}

//Validate phone number
elseif ($level=='421') {
	# code...
	# code...
	if (strlen($textFinal)==0) {
    # code...
		$response  = "END Please Provide Other Phone Number";
	}
	elseif(!ctype_digit($textFinal)){
		$response  = "END Phone Should Not Contain Non Numeric Values";
	}
	else{
		sql("INSERT INTO ussd_levels SET session_id='$sessionId',level='422',phone='$textFinal'");
		$response="CON Enter Amount \n";
	}
}

//Validate amount and process buy aitime for other number.
elseif ($level=='422') {
	# code...
	if (strlen($textFinal)==0) {
    # code...
		$response  = "END Please Provide Amount";
	}
	elseif(!ctype_digit($textFinal)){
		$response  = "END Amount Should Not Contain Non Numeric Values";
	}
	elseif ($textFinal<10) {
  	# code...
		$response  = "END Amount Cannot be less than 10";
	}
	else{
		$getOtherPhone=sqlValue("SELECT phone FROM ussd_levels WHERE session_id='$sessionId' ORDER BY id DESC LIMIT 1");
		$response  = processAirtime($textFinal,$getOtherPhone,$phoneNumber);
	}
}

//Send cash to another DevPesa user.
elseif ($text=='5') {
	# code....provide recipient phone number
	sql("INSERT INTO ussd_levels SET session_id='$sessionId',level='51'");
	$response="CON Enter Recipient Phone Number \n";
}

//Validate recipient phone number
elseif ($level=='51') {
	# code...
	if (strlen($textFinal)==0) {
    # code...
		$response  = "END Please Provide Recipient Phone Number";
	}
	elseif(!ctype_digit($textFinal)){
		$response  = "END Phone Should Not Contain Non Numeric Values";
	}
	else{
		sql("INSERT INTO ussd_levels SET session_id='$sessionId',level='52',phone='$textFinal'");
		$response="CON Enter Amount \n";
	}
}

//Validate amount to send.Process request.
elseif ($level=='52') {
	# code...
	if (strlen($textFinal)==0) {
    # code...
		$response  = "END Please Provide Amount";
	}
	elseif(!ctype_digit($textFinal)){
		$response  = "END Amount Should Not Contain Non Numeric Values";
	}
	elseif ($textFinal<10) {
  	# code...
		$response  = "END Amount Cannot be less than 10";
	}
	else{
		$getRecipientPhone=sqlValue("SELECT phone FROM ussd_levels WHERE session_id='$sessionId' ORDER BY id DESC LIMIT 1");
		$response  = processTransferCash($textFinal,$getRecipientPhone,$phoneNumber);
	}
}

// Echo the response back to the API
header('Content-type: text/plain');
echo $response;
?>