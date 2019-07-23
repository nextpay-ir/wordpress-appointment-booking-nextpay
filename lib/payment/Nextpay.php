<?php
namespace Bookly\Lib\Payment;

use Bookly\Lib;

/**
 * Class Nextpay
 * @package Bookly\Lib\Payment
 */
class Nextpay
{
	const TYPE_EXPRESS_CHECKOUT = 'ec';
    const TYPE_PAYMENTS_STANDARD = 'ps';
	
    // Array for cleaning Nextpay request
    static public $remove_parameters = array( 'bookly_action', 'bookly_fid', 'error_msg', 'token', 'PayerID',  'type' );

    /**
     * The array of products for checkout
     *
     * @var array
     */
    protected $products = array();

    /**
     * Send the Express Checkout NVP request
     *
     * @param $form_id
     * @throws \Exception
     */
    public function sendECRequest( $form_id )
    {

		$current_url = Lib\Utils\Common::getCurrentPageURL();
		
        if ( !session_id() ) {
            @session_start();
        }
        $total = 0;
        foreach ( $this->products as $index => $product ) {
            $total += ( $product->qty * $product->price );
        }
        $_SESSION["price".$form_id] =  $total; 
        $link = new \SoapClient("https://api.nextpay.org/gateway/token.wsdl",array('encoding' => 'UTF-8'));
        //
        $data = array(
        'api_key' => get_option( 'bookly_pmt_nextpay_apikey' ),
        'amount' => $total,
        'order_id' => time(),
        'callback_uri' => add_query_arg( array( 'bookly_action' => 'nextpay-ec-return', 'bookly_fid' => $form_id ), $current_url )
        );
        $res = $link->TokenGenerator($data);
        $res = $res->TokenGeneratorResult;
        /*$curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://api.nextpay.org/gateway/token.http");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS,"api_key=".$data['api_key']."&order_id=".$data['order_id']."&amount=".$data['amount']."&callback_uri=".$data['callback_uri']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $res = json_decode(curl_exec ($curl));
        curl_close ($curl);
        Header('Location: https://api.nextpay.org/gateway/payment/'.$res->code);
        exit;*/
       if(intval($res->code) == -1){
        ob_clean();
        ob_end_clean();
        ob_end_flush();
        Header('Location: https://api.nextpay.org/gateway/payment/'.$res->trans_id);
        exit;
       }
       else
       {
            header( 'Location: ' . wp_sanitize_redirect( add_query_arg( array( 'bookly_action' => 'nextpay-ec-error', 'bookly_fid' => $form_id, 'error_msg' => urlencode($this->description_Verification($res->code) ) ), $current_url ) ) );
            exit;
       }
        
        
    }

    /**
     * Send the NVP Request to the Nextpay
     *
     * @param       $method
     * @param array $data
     * @return array
     */
    public function sendNvpRequest( $method, array $data )
    {
        $NextpayResponse = array("code"=>-1,"msg"=>$this->description_Verification(-1),"refid"=>"");
        $trans_id = isset($_POST['trans_id']) ? $_POST['trans_id'] : false ;
        $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : false ;

        if (!$trans_id) {
            $NextpayResponse['msg'] = $this->description_Verification(-37);
            return $NextpayResponse;
        }

        if (!is_string($trans_id) || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $trans_id) !== 1)) {
            $NextpayResponse['msg'] = $this->description_Verification(-34);
            return $NextpayResponse;
        }

        $data = array("api_key"=> get_option('bookly_pmt_nextpay_apikey'),"trans_id"=>$trans_id,"amount"=>$_SESSION["price".$method], "order_id"=>$order_id);
        $link = new \SoapClient('https://api.nextpay.org/gateway/verify.wsdl',array('encoding' => 'UTF-8'));

        $res = $link->PaymentVerification($data);
        $res = $res->PaymentVerificationResult;

        $NextpayResponse= array("code"=>$res->code,"msg"=>$this->description_Verification($res->code),"refid"=>$refID);

        return $NextpayResponse;
    }

    public static function renderECForm( $form_id )
    {
        $replacement = array(
            '%form_id%' => $form_id,
            '%gateway%' => Lib\Entities\Payment::TYPE_NEXTPAY,
            //'%response_url%' => $response_url,
            '%back%'    => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_button_back' ),
            '%next%'    => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_step_payment_button_next' ),
        );
        $form = '<form method="post" class="bookly-%gateway%-form">
                <input type="hidden" name="bookly_fid" value="%form_id%"/>
                <input type="hidden" name="bookly_action" value="nextpay-ec-init"/>
                <button class="bookly-back-step bookly-js-back-step bookly-btn ladda-button" data-style="zoom-in" style="margin-right: 10px;" data-spinner-size="40"><span class="ladda-label">%back%</span></button>
                <button class="bookly-next-step bookly-js-next-step bookly-btn ladda-button"" data-style="zoom-in" data-spinner-size="40"><span class="ladda-label">%next%</span></button>
             </form>';
        echo strtr( $form, $replacement );
    }

    /**
     * Add the Product for payment
     *
     * @param \stdClass $product
     */
    public function addProduct( \stdClass $product )
    {
        $this->products[] = $product;
    }

    function description_Verification($code){
        $error_code = intval($code);
        $error_array = array(
          0 => "Complete Transaction",
	     -1 => "Default State",
	     -2 => "Bank Failed or Canceled",
	     -3 => "Bank Payment Pending",
	     -4 => "Bank Canceled",
	    -20 => "api key is not send",
	    -21 => "empty trans_id param send",
	    -22 => "amount not send",
	    -23 => "callback not send",
	    -24 => "amount incorrect",
	    -25 => "trans_id resend and not allow to payment",
	    -26 => "Token not send",
	    -27 => "order_id incorrect",
	    -28 => "custom field incorrect [must be json]",
	    -30 => "amount less of limit payment",
	    -31 => "fund not found",
	    -32 => "callback error",
	    -33 => "api_key incorrect",
	    -34 => "trans_id incorrect",
	    -35 => "type of api_key incorrect",
	    -36 => "order_id not send",
	    -37 => "transaction not found",
	    -38 => "token not found",
	    -39 => "api_key not found",
	    -40 => "api_key is blocked",
	    -41 => "params from bank invalid",
	    -42 => "payment system problem",
	    -43 => "gateway not found",
	    -44 => "response bank invalid",
	    -45 => "payment system deactivated",
	    -46 => "request incorrect",
	    -47 => "gateway is deleted or not found",
	    -48 => "commission rate not detect",
	    -49 => "trans repeated",
	    -50 => "account not found",
	    -51 => "user not found",
	    -52 => "user not verify",
	    -60 => "email incorrect",
	    -61 => "national code incorrect",
	    -62 => "postal code incorrect",
	    -63 => "postal add incorrect",
	    -64 => "desc incorrect",
	    -65 => "name family incorrect",
	    -66 => "tel incorrect",
	    -67 => "account name incorrect",
	    -68 => "product name incorrect",
	    -69 => "callback success incorrect",
	    -70 => "callback failed incorrect",
	    -71 => "phone incorrect",
	    -72 => "bank not response",
	    -73 => "callback_uri incorrect [with api's address website]",
	    -82 => "ppm incorrect token code"
        );
        
        if (array_key_exists($error_code, $error_array)) {
		return $error_array[$error_code];
        } else {
            return "error code : $error_code";
        }
    }
    
}
