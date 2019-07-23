<?php
namespace Bookly\Lib\Payment;

use Bookly\Lib;

include __DIR__.'/Mellat/lib/nos.php';
/**
 * Class Mellat
 * @package Bookly\Lib\Payment
 */
class Mellat
{
	const TYPE_EXPRESS_CHECKOUT = 'ec';
    const TYPE_PAYMENTS_STANDARD = 'ps';

    const URL_POSTBACK_IPN_LIVE = 'https://www.paypal.com/cgi-bin/webscr';
    const URL_POSTBACK_IPN_SANDBOX = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	
    private	$terminalID;
	private	$username;
	private	$password;
	private	$link;
	private $namespace;
	private $ServerUrl ;
    
    public function __construct(){
        if ( !session_id() ) {
            @session_start();
        }
     	$this->link = new \nusoap_client(get_option( 'bookly_pmt_mellat_url' ));
         ini_set("display_errors","on");
        error_reporting(1);
     	$this->terminalID = get_option( 'bookly_pmt_mellat_terminalID' );
     	$this->username = get_option( 'bookly_pmt_mellat_username' );
     	$this->password = get_option( 'bookly_pmt_mellat_password' );
        $this->namespace = get_option( 'bookly_pmt_mellat_namespace' );
        $this->ServerUrl = get_option( 'bookly_pmt_mellat_serverurl' );
    }
    
    
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
		
		$total = 0;
        foreach ( $this->products as $index => $product ) {
            $total += ( $product->qty * $product->price );
        }
        $_SESSION["price".$form_id] =  $total; 
        $_SESSION["id".$form_id] =  rand(100,999).rand(100,999).rand(10,99); 
     	$localDate = date('Ynd',time());
     	$localTime = date('His',time());
         $parameters = array(
               'terminalId' => $this->terminalID,'userName' => $this->username,'userPassword' => $this->password,
               'orderId' => $_SESSION["id".$form_id],'amount' => $total,'localDate' => $localDate,'localTime' => $localTime,
               'additionalData' => '', 'callBackUrl' => add_query_arg( array( 'bookly_action' => 'mellat-ec-return', 'bookly_fid' => $form_id ), $current_url ),'payerId' => '0');
        $res =  $this->link->call("bpPayRequest",$parameters,$this->namespace);
        $data =  explode(',',$res);
        if(is_array($data) && count($data)==2 && $data[0]=="0"){
            ob_clean();
            ob_end_clean();
            ob_end_flush();
        	echo <<<SCRIPT
        	<html><head></head><body>
        	<script type="text/javascript">
                            var form = document.createElement("form");
                            form.setAttribute("method", "POST");
                            form.setAttribute("action", "{$this->ServerUrl}");
                            form.setAttribute("target", "_self");
                            var hiddenField = document.createElement("input");
                            hiddenField.setAttribute("name", "RefId");
                            hiddenField.setAttribute("value", "{$data[1]}");
                            form.appendChild(hiddenField);
    
                            document.body.appendChild(form);
                            form.submit();
                            document.body.removeChild(form);
            </script>
            </body></html>
SCRIPT;
	       die('درحال انتقال به بانک...');
        }
       else
       {
            header( 'Location: ' . wp_sanitize_redirect( add_query_arg( array( 'bookly_action' => 'mellat-ec-error', 'bookly_fid' => $form_id, 'error_msg' => urlencode($this->description_Verification($data[0]))), $current_url ) ) );
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
        $orderid = $_SESSION["id".$method];
        $PayPalResponse = array("code"=>-1,"msg"=>$this->description_Verification(100),"refid"=>"");
        if(isset($_POST["ResCode"])){
            $RC = $ResCode =$SC=$VR=$_POST["ResCode"];
            $SRID = $_POST["SaleReferenceId"];
            $msg = $this->description_Verification($ResCode);
            if($RC==0){
                $VR=$ResCode= $this->bpVerifyRequest($orderid , $SRID);
        		if($VR=="" || $VR!="0"){
    				$VR= $this->bpVerifyRequest($orderid , $SRID);
    				if($VR=="0")
    					$SC = $this->bpSettleRequest($orderid , $SRID);
                    else
                         $this->bpReversalRequest($orderid , $SRID);
        		}
                else
                        $SC =$this->bpSettleRequest($orderid , $SRID);
                if($SC=="" || $SC!="0")
                        $SC =$this->bpSettleRequest($orderid , $SRID);

            }
            $PayPalResponse= array("code"=>($SC=="0"|| $VR=="0"),"msg"=>$msg,"refid"=>$SRID);
        }
        return $PayPalResponse;
    }

    public static function renderECForm( $form_id )
    {
        $replacement = array(
            '%form_id%' => $form_id,
            '%gateway%' => Lib\Entities\Payment::TYPE_MELLAT,
            //'%response_url%' => $response_url,
            '%back%'    => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_button_back' ),
            '%next%'    => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_step_payment_button_next' ),
        );
        $form = '<form method="post" class="bookly-%gateway%-form">
                <input type="hidden" name="bookly_fid" value="%form_id%"/>
                <input type="hidden" name="bookly_action" value="mellat-ec-init"/>
                <button class="bookly-back-step bookly-js-back-step bookly-btn ladda-button" data-style="zoom-in" style="margin-right: 10px;" data-spinner-size="40"><span class="ladda-label">%back%</span></button>
                <button class="bookly-next-step bookly-js-next-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40"><span class="ladda-label">%next%</span></button>
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
    function bpVerifyRequest($orderId,$verifySaleReferenceId){
	       $parameters = array(
			'terminalId' => $this->terminalID,'userName' => $this->username,'userPassword' => $this->password,
			'orderId' => $orderId,'saleOrderId' => $orderId,'saleReferenceId' => $verifySaleReferenceId);
	       return $this->link->call("bpVerifyRequest",$parameters,$this->namespace);
    }
    function bpSettleRequest($orderId,$verifySaleReferenceId){
    	$parameters = array(
    			'terminalId' => $this->terminalID,'userName' => $this->username,'userPassword' => $this->password,
    			'orderId' => $orderId,'saleOrderId' => $orderId,'saleReferenceId' => $verifySaleReferenceId);
    	return $this->link->call("bpSettleRequest",$parameters,$this->namespace);
    }
    function bpReversalRequest($orderId,$verifySaleReferenceId){
	$parameters = array(
			'terminalId' => $this->terminalID,'userName' => $this->username,'userPassword' => $this->password,
			'orderId' => $orderId,'saleOrderId' => $orderId,'saleReferenceId' => $verifySaleReferenceId);
	return $this->link->call("bpReversalRequest",$parameters,$this->namespace);
    }
    function description_Verification($code){
        switch(($code)){
            case "0" : return "تراكنش با موفقيت انجام شد";
            case "11" : return "شماره كارت نامعتبر است";
            case "12" : return "موجودي كافي نيست";
            case "13" : return "رمز نادرست است";
            case "14" : return "تعداد دفعات وارد كردن رمز بيش از حد مجاز است";
            case "15" : return "كارت نامعتبر است";
            case "16" : return "دفعات برداشت وجه بيش از حد مجاز است";
            case "17" : return "كاربر از انجام تراكنش منصرف شده است";
            case "18" : return "تاريخ انقضاي كارت گذشته است";
            case "19" : return "مبلغ برداشت وجه بيش از حد مجاز است";
            case "111" : return "صادر كننده كارت نامعتبر است";
            case "112" : return "خطاي سوييچ صادر كننده كارت";
            case "113" : return "پاسخي از صادر كننده كارت دريافت نشد";
            case "114" : return "دارنده كارت مجاز به انجام اين تراكنش نيست";
            case "21" : return "پذيرنده نامعتبر است";
            case "23" : return "خطاي امنيتي رخ داده است";
            case "24" : return "اطلاعات كاربري پذيرنده نامعتبر است";
            case "25" : return "مبلغ نامعتبر است";
            case "31" : return "پاسخ نامعتبر است";
            case "32" : return "فرمت اطلاعات وارد شده صحيح نمي باشد";
            case "33" : return "حساب نامعتبر است";
            case "34" : return "خطاي سيستمي";
            case "35" : return "تاريخ نامعتبر است";
            case "41" : return "شماره درخواست تكراري است";
            case "42" : return "تراکنش sale یافت نشد";
            case "43" : return "قبلا درخواست verify داده شده است";
            case "44" : return "درخواست verify یافت نشد .";
            case "45" : return "تراکنش settle شده است";
            case "46" : return "تراکنش settle نشده است.";
            case "47" : return "تراکنش settle یافت نشد";
            case "48" : return "تراکنش reverse شده است.";
            case "49" : return "تراکنش refund یافت نشد";
            case "412" : return "شناسه قبض نادرست است";
            case "413" : return "شناسه پرداخت نادرست است";
            case "414" : return "سازمان صادر كننده قبض نامعتبر است";
            case "415" : return "زمان جلسه كاري به پايان رسيده است";
            case "416" : return "خطا در ثبت اطلاعات";
            case "417" : return "شناسه پرداخت كننده نامعتبر است";
            case "418" : return "اشكال در تعريف اطلاعات مشتري";
            case "419" : return "تعداد دفعات ورود اطلاعات از حد مجاز گذشته است";
            case "421" : return "ip نامعتبر است";
            case "51" : return "تراكنش تكراري است";
            case "54" : return "تراكنش مرجع موجود نيست";
            case "55" : return "تراكنش نامعتبر است";
            case "61" : return "خطا در واريز";
            default:
                    "سیستم در برقراری ارتباط با شبکه بانکی دچار مشکل شده است.";
    }
    }
}