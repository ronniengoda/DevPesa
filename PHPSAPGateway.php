<?php 
/**
 *
 PHP SAP GATEWAY CLASS IS A FREE SOFTWARE IE. CAN BE MODIFIED AND/OR REDISTRIBUTED                        
  UNDER THE TERMS OF GNU GENERAL PUBLIC LICENCES AS PUBLISHED BY THE                                                 
  FREE SOFTWARE FOUNDATION VERSION 1 OR ANY LATER VERSION                                                            
 
  THE CLASS IS DISTRIBUTED ON 'AS IS' BASIS WITHOUT ANY WARRANTY, INCLUDING BUT NOT LIMITED TO                       
  THE IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.                     
  IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,            
  WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE       
  OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

  class PhpSapGatewayException extends Exception  {}

  class PhpSapGateway
  {
  	
  	function ProcessBalance($data){
  		$url = 'https://www.renthero.co.ke/phpsap/developer/payments/payments_wallet_balance.php';
  		$execute=$this->cUrlParams($url,$data);
      return $execute;
  	}

  	function ProcessB2B($data){
  		$url = 'https://www.renthero.co.ke/phpsap/developer/payments/sapb2b.php';
  		$execute=$this->cUrlParams($url,$data);
      return $execute;
  	}

  	function ProcessB2C($data){
  		$url = 'https://www.renthero.co.ke/phpsap/developer/payments/sapb2c.php';
  		$execute=$this->cUrlParams($url,$data);
      return $execute;
  	}

  	function ProcessLNMO($data){
  		$url = 'https://www.renthero.co.ke/phpsap/developer/payments/lnmo.php';
      $decodeData=json_decode($data);
      if (strlen($decodeData->PhoneNumber)==0||strlen($decodeData->Amount)==0) {
      # code...
        throw new PhpSapGatewayException('Please supply both PhoneNumber and Amount parameters');
      }
      elseif (strrpos($decodeData->PhoneNumber, '+254')!==0) {
      # code...
        throw new PhpSapGatewayException('Please ensure the Phone Number is in international format');
        
      }
      elseif ($decodeData->Amount<10) {
        # code...
        throw new PhpSapGatewayException('Amount cannot be less than 10');
      }
      elseif (!ctype_digit($decodeData->Amount)) {
        # code...
        throw new PhpSapGatewayException('Please ensure amount does not contain non numeric values');
      }

  		$execute=$this->cUrlParams($url,$data);
      return $execute;
  	}

    function ProcessSAPWalletBalance($data){
      $url = 'https://www.renthero.co.ke/phpsap/developer/payments/sap_wallet_balance.php';
      $execute=$this->cUrlParams($url,$data);
      return $execute;
    }

  	function ProcessSMS($data){
  		$url = 'https://www.renthero.co.ke/phpsap/developer/payments/sms.php';

  		$decodeData=json_decode($data);
  		if (strlen($decodeData->Receiver)==0||strlen($decodeData->Message)==0) {
			# code...
  			throw new PhpSapGatewayException('Please supply both Receiver and Message parameters');
  		}
  		elseif (strrpos($decodeData->Receiver, '+254')!==0) {
			# code...
  			throw new PhpSapGatewayException('Please ensure the Phone Number is in international format');
  			
  		}
  		
  		$execute=$this->cUrlParams($url,$data);
      return $execute;
  	}

  	function ProcessAirtime($data){
  		$url = 'https://www.renthero.co.ke/phpsap/developer/payments/airtime.php';

  		$decodeData=json_decode($data);
  		if (strlen($decodeData->Receiver)==0||strlen($decodeData->Amount)==0) {
			# code...
  			throw new PhpSapGatewayException('Please supply both Receiver and Amount parameters');
  		}
  		elseif (strrpos($decodeData->Receiver, '+254')!==0) {
			# code...
  			throw new PhpSapGatewayException('Please ensure the Phone Number is in international format');
  			
  		}
      elseif ($decodeData->Amount<=5) {
        # code...
        throw new PhpSapGatewayException('Please provide an amount greater than 5');
      }
      elseif (!ctype_digit($decodeData->Amount)) {
        # code...
        throw new PhpSapGatewayException('Please ensure amount does not contain non numeric values');
      }
      
  		$execute=$this->cUrlParams($url,$data);
      return $execute;
  	}

    function ProcessWalletTransfer($data){
      $url="https://www.renthero.co.ke/phpsap/developer/payments/payments_wallet_transfer.php";
      $execute=$this->cUrlParams($url,$data);
      return $execute;
    }

    function ProcessWalletTopup($data){
      $url="https://www.renthero.co.ke/phpsap/developer/payments/topupsapwallet.php";
      $execute=$this->cUrlParams($url,$data);
      return $execute;
    }

    function ProcessC2BValidation($data){
      $url="https://www.renthero.co.ke/phpsap/developer/payments/sapc2b_validation.php";
      $execute=$this->cUrlParams($url,$data);
      return $execute;
    }

    function ProcessTaskCreate($data){
      $url="https://renthero.co.ke/phpsap/developer/payments/sapscheduler.php";
      $execute=$this->cUrlParams($url,$data);
      return $execute;
    }

    function ProcessTaskDelete($data){
      $url="https://renthero.co.ke/phpsap/developer/payments/sapschedulerdelete.php";
      $execute=$this->cUrlParams($url,$data);
      return $execute;
    }

    function ProcessTaskPause($data){
      $url="https://renthero.co.ke/phpsap/developer/payments/sapschedulerpause.php";
      $execute=$this->cUrlParams($url,$data);
      return $execute;
    }

    function ProcessTaskResume($data){
      $url="https://renthero.co.ke/phpsap/developer/payments/sapschedulerresume.php";
      $execute=$this->cUrlParams($url,$data);
      return $execute;
    }

    function ProcessAccount($data){
      $url="http://localhost/kenyastalking/payments/sap_account.php";
      $execute=$this->cUrlParams($url,$data);
      return $execute;
    }

    function SAPMail($data){
      $url="https://www.renthero.co.ke/send/email.php";
      $execute=$this->cUrlParams($url,$data);
      return $execute;
    }

  	function cUrlParams($url,$data){
		//Initiate cURL.
  		$ch = curl_init($url);

		//Tell cURL that we want to send a POST request.
  		curl_setopt($ch, CURLOPT_POST, 1);

		//Attach our encoded JSON string to the POST fields.
  		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

		//Set the content type to application/json.
  		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    //Dont return result to screen,store in a variable.
      curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);

		//Execute the request.
  		$result = curl_exec($ch);
      return $result;
  	}
  }