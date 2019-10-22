<?php 
require 'connect.php';

//SET YOUR PHP SAP API CREDENTIALS BELOW HERE
$username="";
$apiKey="";

//function to run sql queries specified by user
function sql($query){
	global $con;
	if ($result=mysqli_query($con,$query)) {
		# code...
		return TRUE;
	}
}

//function to run sql queries provided by user that returns a single value.
function sqlValue($query){
	global $con;
	$result=mysqli_query($con,$query);
	$data=mysqli_fetch_array($result);
	return $data[0];
}

//function to check if a phone number is registered.
function checkPhoneNumber($phoneNumber){
	global $con;
	$validate=sqlValue("SELECT COUNT(*) FROM users WHERE phone_number='$phoneNumber'");
	if ($validate>0) {
		# code...phone number is registered
		$status="TRUE";
		return $status;
	}
	else{
		# code..phone number is not registred
		$status="FALSE";
		return $status;
	}

}

//function to send sms messages.
function sendSMS($reciver,$message){
	require_once 'PHPSAPGateway.php';
	$gateway= new PhpSapGateway;
	global $username,$apiKey;

	//Pass authentication credentials and your SMS data into an array
	$SMSData = array(
		'Receiver' => $reciver,
		'Message' => $message,
		'username'=>$username,
		'apiKey'=>$apiKey
	);

	//Convert the array to JSON String.
	$SMSDataEncoded = json_encode($SMSData);

	//Thats it,from here we will take care of the rest.
	try {
		$result=$gateway->ProcessSMS($SMSDataEncoded);
		//print_r($result);
	} catch (Exception $e) {
		echo $e->getMessage();
	}
}

//Register user account details,also create a mobile wallet for them and send an SMS
function registerAccount($Email,$FullName,$PhoneNumber){
	require_once 'PHPSAPGateway.php';
	$gateway= new PhpSapGateway;
	global $username,$apiKey;
	
	sql("INSERT INTO users SET full_name='$FullName',phone_number='$PhoneNumber',email='$Email'");
	sql("INSERT INTO wallets SET phone_number='$PhoneNumber'");

	//send welcome sms to user using PHP SAP API
	$Receiver=$PhoneNumber;
	$Message="Hello ".$FullName." welcome to DevPesa.With DevPesa you can be able to deposit cash,withdraw and buy airtime for any phone number.";
	sendSMS($Receiver,$Message);
	return TRUE;
}

//function to get user wallet balance.
function getWalletBalance($PhoneNumber){
	$balance=sqlValue("SELECT available_amount FROM wallets WHERE phone_number='$PhoneNumber'");
	return $balance;
}

//function to process stk request
function processSTK($textFinal,$phoneNumber){
	require_once 'PHPSAPGateway.php';
	$gateway= new PhpSapGateway;
	global $username,$apiKey;

	//Set PhoneNumber and Amount below(Required)
	$PhoneNumber=$phoneNumber;
	$Amount=$textFinal;

	//Set any metadata you want to attach to the request below(optional);
	$LNMOmetadata = [
		"UserAccount"   => $phoneNumber
	];

	//Pass authentication credentials and your LNMO data into an array
	$LNMOData = array(
		'PhoneNumber' => $PhoneNumber,
		'Amount' => $Amount,
		'username'=>$username,
		'apiKey'=>$apiKey,
		'LNMOmetadata'=>$LNMOmetadata
	);

	//Convert the array into JSON string.
	$LNMODataEncoded = json_encode($LNMOData);

	//Thats it,from here we will take care of the rest.
	try {
		$result=$gateway->ProcessLNMO($LNMODataEncoded);
		$decode=json_decode($result);
		$status=$decode->status;
		if ($status=='true') {
			# code...
			$response="END Request submitted for processing.You will receive a prompt on your mobile phone to complete payment";
			
		}
		else{
			$response="END Sorry we encoutered a problem try again later";
		}
	} catch (Exception $e) {
		echo $e->getMessage();
	}
	return $response;
}

//Function to process the B2C request.
function processB2C($textFinal,$phoneNumber){
	require_once 'PHPSAPGateway.php';
	$gateway= new PhpSapGateway;
	global $username,$apiKey;

	//Set PhoneNumber and Amount below(Required)
	$PhoneNumber=$phoneNumber;
	$Amount=$textFinal;

	//Pass authentication credentials and your B2C data into an array
	$B2CData = array(
		'PhoneNumber' => $PhoneNumber,
		'Amount' => $Amount,
		'username'=>$username,
		'apiKey'=>$apiKey
	);

	//Convert the array into JSON string.
	$B2CDataEncoded = json_encode($B2CData);

	//check current available amount in wallet
	$currentBalance=getWalletBalance($phoneNumber);

	if ($textFinal>$currentBalance) {
		# code...terminate session.
		$response="END Sorry, you have insufficient balance in your wallet to withdraw Ksh ".$textFinal;
	}
	else{
		//Thats it,from here we will take care of the rest.
		try {
			$result=$gateway->ProcessB2C($B2CDataEncoded);
		//print_r($result);
			$decode=json_decode($result);
			$status=$decode->status;
			if ($status=="true") {
			# code...calculates users new balance,update user wallet,insert transaction details and finally send an SMS to the user.
				$newBalance=($currentBalance-$textFinal);
				sql("UPDATE wallets SET available_amount='$newBalance' WHERE phone_number='$phoneNumber'");
				sql("INSERT INTO transactions SET transaction_type='Withdraw',phone_number='$phoneNumber',amount='$textFinal',new_wallet_balance='$newBalance',`date`='".date('Y-m-d')."',`time`='".date('h:s:a')."'");
				$reciver=$phoneNumber;
				$message="You have successfully withdrawn Ksh ".$textFinal." on ".date('Y/m/d h:s:A')." your new DevPesa wallet balance is Ksh ".$newBalance;
				sendSMS($reciver,$message);
				$response="END You have successfully withdrawn Ksh ".$textFinal;
			}
			else{
				$response="END Sorry we encoutered a problem try again later";
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	return $response;
}

//Function to process the airtime purchase request.
function processAirtime($textFinal,$phoneNumber,$source){
	require_once 'PHPSAPGateway.php';
	$gateway= new PhpSapGateway;
	global $username,$apiKey;

	$phoneNumber    = (substr($phoneNumber, 0, 1) == '0') ? preg_replace('/^0/', '+254', $phoneNumber) : $phoneNumber;
	$phoneNumber    = (substr($phoneNumber, 0, 3) == '254') ? str_replace('254', '+254', $phoneNumber) : $phoneNumber;

	$checkBalance=getWalletBalance($source);
	if ($textFinal>$checkBalance) {
		# code...
		$response="END You have insufficient balance to buy Ksh ".$textFinal." airtime";
	}
	else{
		//Set airtime Receiver and Amount below(Required)
		$Receiver=$phoneNumber;
		$Amount=$textFinal;

		//Pass authentication credentials and your airtime data into an array
		$AirtimeData = array(
			'Receiver' => $Receiver,
			'Amount' => $Amount,
			'username'=>$username,
			'apiKey'=>$apiKey
		);

		//Convert the array into JSON string.
		$AirtimeDataEncoded = json_encode($AirtimeData);

		//Thats it,from here we will take care of the rest.
		try {
			$result=$gateway->ProcessAirtime($AirtimeDataEncoded);
			//print_r($result);
			$decode=json_decode($result);
			$status=$decode->status;
			if ($status=='true') {
				# code...Update source wallet,insert transaction details and send an SMS to the source about the transaction.
				$newBalance=($checkBalance-$textFinal);
				sql("UPDATE wallets SET available_amount='$newBalance' WHERE phone_number='$source'");
				sql("INSERT INTO transactions SET transaction_type='Airtime',phone_number='$source',destination='$phoneNumber',amount='$textFinal',new_wallet_balance='$newBalance',`date`='".date('Y-m-d')."',`time`='".date('h:s:a')."'");
				$reciver=$source;
				$message="You have successfully bought Ksh ".$textFinal." airtime for ".$phoneNumber." on ".date('Y/m/d h:s:A')." your new DevPesa wallet balance is Ksh ".$newBalance;
				sendSMS($reciver,$message);
				$response="END You have successfully bought Ksh ".$textFinal." airtime";
			}
			else{
				$response="END Sorry, we encoutered a problem try again later.";
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	return $response;
}

//Funtion to process the trsnafer cash transaction
function processTransferCash($textFinal,$RecipientPhone,$source){
	require_once 'PHPSAPGateway.php';
	$gateway= new PhpSapGateway;
	global $username,$apiKey;

	$RecipientPhone    = (substr($RecipientPhone, 0, 1) == '0') ? preg_replace('/^0/', '+254', $RecipientPhone) : $RecipientPhone;
	$RecipientPhone    = (substr($RecipientPhone, 0, 3) == '254') ? str_replace('254', '+254', $RecipientPhone) : $RecipientPhone;

	$checkBalance=getWalletBalance($source);
	$verifyRecipient=checkPhoneNumber($RecipientPhone);
	if ($verifyRecipient=="FALSE") {
		# code...
		$response="END Sorry recipient phone number is not registred on DevPesa";
	}
	elseif ($textFinal>$checkBalance) {
		# code...
		$response="END You have insufficient balance to send Ksh ".$textFinal."";
	}
	elseif ($RecipientPhone==$source) {
		# code...
		$response="END Sorry you can not send cash to yourself";
	}
	else{
		//calculate recipient and source balances
		$recipientBalance=getWalletBalance($RecipientPhone);
		$newBalanceRecipient=($recipientBalance+$textFinal);
		$newBalanceSource=($checkBalance-$textFinal);

		//update wallets table
		sql("UPDATE wallets SET available_amount='$newBalanceRecipient' WHERE phone_number='$RecipientPhone'");
		sql("UPDATE wallets SET available_amount='$newBalanceSource' WHERE phone_number='$source'");

		//Update transactions table
		sql("INSERT INTO transactions SET transaction_type='Transfer Cash',phone_number='$source',destination='$RecipientPhone',amount='$textFinal',new_wallet_balance='$newBalanceSource',`date`='".date('Y-m-d')."',`time`='".date('h:s:a')."'");
		sql("INSERT INTO transactions SET transaction_type='Received Cash',phone_number='$source',destination='$RecipientPhone',amount='$textFinal',new_wallet_balance='$newBalanceRecipient',`date`='".date('Y-m-d')."',`time`='".date('h:s:a')."'");

		//send SMS to recipient
		$reciverRecipient=$RecipientPhone;
		$messageRecipient="You have successfully received Ksh ".$textFinal." from ".$source." on ".date('Y/m/d h:s:A')." your new DevPesa wallet balance is Ksh ".$newBalanceRecipient;
		sendSMS($reciverRecipient,$messageRecipient);

		//send SMS to source
		$reciverSource=$source;
		$messageSource="You have successfully sent Ksh ".$textFinal." to ".$RecipientPhone." on ".date('Y/m/d h:s:A')." your new DevPesa wallet balance is Ksh ".$newBalanceSource;
		sendSMS($reciverSource,$messageSource);

		$response="END You have successfully sent Ksh ".$textFinal." to ".$RecipientPhone;
	}

	return $response;
}

?>
