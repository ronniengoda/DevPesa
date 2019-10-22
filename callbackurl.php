<?php 
//Here we include the functions file.
include 'functions.php';

//Here we get the callback notification which is a json string
$input=file_get_contents('php://input');
$data = json_decode($input);

//Here we store the relevant notification items
$source=mysqli_real_escape_string($con,$data->response->source);
$value=mysqli_real_escape_string($con,$data->response->value);
$status=mysqli_real_escape_string($con,$data->response->status);
$timestamp=mysqli_real_escape_string($con,$data->response->requestMetadata->Tstamp);
$amt=ltrim($value,"KES ");

//Here we get the current balance of the user
$currentBalance=getWalletBalance($source);

//Here we calculate the new balance of the user by adding the topped up amount and the available balance.
$newBalance=($amt+$currentBalance);

if ($status=="Success") {
	# code...if the trsnaction was successfull do the following...

	//Update the users wallet to reflect the new balance we calculated above
	sql("UPDATE wallets SET available_amount='$newBalance' WHERE phone_number='$source'");

	//Insert the transaction details for future reference and accountability.
	sql("INSERT INTO transactions SET transaction_type='Deposit',phone_number='$source',amount='$amt',new_wallet_balance='$newBalance',`date`='".date('Y-m-d')."',`time`='".date('h:s:a')."'");
	$reciver=$source;
	$message="You have successfully deposited Ksh ".$amt." on".date('Y/m/d h:s:A')." your new DevPesa wallet balance is Ksh ".$newBalance;
	
	//Send an sms to the user informing them their wallet has been credited
	sendSMS($reciver,$message);
}
?>