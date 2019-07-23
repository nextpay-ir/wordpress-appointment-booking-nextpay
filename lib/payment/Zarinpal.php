<?php
namespace Bookly\Lib\Payment;

use Bookly\Lib;

/**
 * Class Zarinpal
 * @package Bookly\Lib\Payment
 */
class Zarinpal
{
	const TYPE_EXPRESS_CHECKOUT = 'ec';
    const TYPE_PAYMENTS_STANDARD = 'ps';

    //const URL_POSTBACK_IPN_LIVE = 'https://www.paypal.com/cgi-bin/webscr';
    //const URL_POSTBACK_IPN_SANDBOX = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	
    // Array for cleaning PayPal request
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
        $sandbox =get_option( 'bookly_pmt_zarin_sandbox' )==1;
        $soapurl = $sandbox?str_replace("https://","https://sandbox.",get_option( 'bookly_pmt_zarin_url' )):get_option( 'bookly_pmt_zarin_url' );
        $total = 0;
        foreach ( $this->products as $index => $product ) {
            $total += ( $product->qty * $product->price );
        }
        $_SESSION["price".$form_id] =  $total; 
        $link = new \SoapClient($soapurl,array('encoding' => 'UTF-8'));
        $data = array(
        'MerchantID' => get_option( 'bookly_pmt_zarin_merchantid' ),
        'Amount' => $total,
        'Description' => 'پرداخت هزینه وقت ملاقات',
        'CallbackURL' => add_query_arg( array( 'bookly_action' => 'zarin-ec-return', 'bookly_fid' => $form_id ), $current_url )
        );
	   $res = $link->PaymentRequest($data);
       if($res->Status == 100){
        ob_clean();
        ob_end_clean();
        ob_end_flush();
        Header('Location: https://'.($sandbox?"sandbox.":"").'zarinpal.com/pg/StartPay/'.$res->Authority);
        //die($this->description_Request(1));
        exit;
        
       }
       else
       {
            header( 'Location: ' . wp_sanitize_redirect( add_query_arg( array( 'bookly_action' => 'zarin-ec-error', 'bookly_fid' => $form_id, 'error_msg' => urlencode($this->description_Verification($res->Status) ) ), $current_url ) ) );
            exit;
        
       }
        
        
    }

    /**
     * Send the NVP Request to the PayPal
     *
     * @param       $method
     * @param array $data
     * @return array
     */
    public function sendNvpRequest( $method, array $data )
    {
        $sandbox =get_option( 'bookly_pmt_zarin_sandbox' )==1;
        $soapurl = $sandbox?str_replace("https://","https://sandbox.",get_option( 'bookly_pmt_zarin_url' )):get_option( 'bookly_pmt_zarin_url' );
        $PayPalResponse = array("code"=>-1,"msg"=>$this->description_Verification(0),"refid"=>"");
        if(isset($_GET["Authority"],$_GET["Status"]) && $_GET["Status"]=="OK" ){
            $orderid=$refID=$_GET["Authority"];
            
            $data = array("MerchantID"=> get_option( 'bookly_pmt_zarin_merchantid' ),"Authority"=>$refID,"Amount"=>$_SESSION["price".$method]);
            $link = new \SoapClient($soapurl,array('encoding' => 'UTF-8'));

    	   $res = $link->PaymentVerification($data);
           
           $PayPalResponse= array("code"=>$res->Status,"msg"=>$this->description_Verification($res->Status),"refid"=>$refID);
        }
        return $PayPalResponse;
    }

    public static function renderECForm( $form_id )
    {
        $replacement = array(
            '%form_id%' => $form_id,
            '%gateway%' => Lib\Entities\Payment::TYPE_ZARIN,
            //'%response_url%' => $response_url,
            '%back%'    => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_button_back' ),
            '%next%'    => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_step_payment_button_next' ),
        );
        $form = '<form method="post" class="bookly-%gateway%-form">
                <input type="hidden" name="bookly_fid" value="%form_id%"/>
                <input type="hidden" name="bookly_action" value="zarin-ec-init"/>
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

    function description_Request($code){
        switch(intval($code)){
            case -1: return "اطلاعات ارسالی ناقص می باشد (کد -1)";
            case -2: return "وب سرویس مورد نظر معتبر نمی باشد (کد -2)";
            case -3: return "حداقل مبلغ پرداختی درگاه پرداخت 100 تومان می باشد (کد -3)";
            case -4: return "فروشنده متقاضی پرداخت معتبر نمی باشد (کد -4)";
            default:
                return "سیستم در حال انتقال به سیستم مورد نظر می باشد، لطفا صبر کنید (کد 1)";
        }
    }
    function description_Verification($code){
        switch(intval($code)){
            case 101:
            case 100: return "پرداخت با موفقیت انجام پذیرفت (کد 1)";
            case -1: return "اطلاعات ارسالی ناقص می باشد (کد -1)";
            case -2: return "و يا مرچنت كد پذيرنده صحيح نيست. IP";
            case -3: return "با توجه به محدوديت هاي شاپرك امكان پرداخت با رقم درخواست شده ميسر نمي باشد.";
            case -4: return "سطح تاييد پذيرنده پايين تر از سطح نقره اي است.";
            case -11: return "درخواست مورد نظر يافت نشد.";
            case -12: return "امكان ويرايش درخواست ميسر نمي باشد.";
            case -21:return "هيچ نوع عمليات مالي براي اين تراكنش يافت نشد.";
            case -22:return "تراكنش نا موفق ميباشد.";
            case -33:return "رقم تراكنش با رقم پرداخت شده مطابقت ندارد.";
            case -34:return "سقف تقسيم تراكنش از لحاظ تعداد يا رقم عبور نموده است";
            case -40:return "اجازه دسترسي به متد مربوطه وجود ندارد.";
            case -42:return "مدت زمان معتبر طول عمر شناسه پرداخت بايد بين 30 دقيه تا 45 روز مي باشد.";
            case -54:return "درخواست مورد نظر آرشيو شده است.";
            default:
                return "عملیات پرداخت بطورکامل انجام نشد. (کد 0)";
        }
    }
    
}