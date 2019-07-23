<?php
namespace Bookly\Frontend\Modules\Zarinpal;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\Zarinpal
 */
class Controller extends Lib\Base\Controller
{

    protected function getPermissions()
    {
        return array( '_this' => 'anonymous' );
    }
	
	/**
     * Init Express Checkout transaction.
     */
    public function ecInit()
    {
        $form_id = $this->getParameter( 'bookly_fid' );
        if ( $form_id ) {
            // Create a zarinpal object.
            $zarinpal   = new Lib\Payment\Zarinpal();
            $userData = new Lib\UserBookingData( $form_id );

            if ( $userData->load() ) {
                list ( $total, $deposit ) = $userData->cart->getInfo();
                $product = new \stdClass();
                $product->name  = $userData->cart->getItemsTitle( 126 );
                $product->price = $deposit;
                $product->qty   = 1;
                $zarinpal->addProduct( $product );

                // and send the payment request.
                $zarinpal->sendECRequest( $form_id );
            }
        }
    }

    /**
     * Process Express Checkout return request.
     */
    public function ecReturn()
    {
        $form_id = $this->getParameter( 'bookly_fid' );
        $ZarinPal  = new Lib\Payment\Zarinpal();
        $error_message = '';
            $response = $ZarinPal->sendNvpRequest( $form_id, array() );

            if ( strtoupper( $response['code'] ) == 100) {
                
                        $userData = new Lib\UserBookingData( $form_id );
                        $userData->load();
                        list ( $total, $deposit ) = $userData->cart->getInfo();
						$coupon = $userData->getCoupon();
                        if ( $coupon ) {
                            $coupon->claim();
                            $coupon->save();
                        }
                        $payment = new Lib\Entities\Payment();
                        $payment
                                ->set( 'type',    Lib\Entities\Payment::TYPE_ZARIN )
                                ->set( 'status',  Lib\Entities\Payment::STATUS_COMPLETED )
                                ->set( 'total',   $total )
                                ->set( 'paid',    $deposit )
								->set( 'paid_type', $total == $deposit ? Lib\Entities\Payment::PAY_IN_FULL : Lib\Entities\Payment::PAY_DEPOSIT )
                                //->set( 'token',   intval($response['refid']) )
                                ->set( 'created',   current_time( 'mysql' ) )
                                ->save();
                            $ca_list = $userData->save( $payment->get( 'id' ) );
                            Lib\NotificationSender::sendFromCart( $ca_list );
                            $payment->setDetails( $ca_list, $coupon )->save();
							$userData->setPaymentStatus( Lib\Entities\Payment::TYPE_ZARIN, 'success' );
                        
                        @wp_redirect( remove_query_arg( Lib\Payment\Zarinpal::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
                        exit;
            } else {
                $error_message = $response["msg"];
			}
        
        if ( ! empty( $error_message ) ) {
            header( 'Location: ' . wp_sanitize_redirect( add_query_arg( array(
                    'bookly_action'    => 'zarin-ec-error',
                    'bookly_fid'    => $form_id,
                    'error_msg' => urlencode( $error_message ),
                ), Lib\Utils\Common::getCurrentPageURL()
                ) ) );
            exit;
        }
    }
    
    /**
     * Process Express Checkout cancel request.
     */
    public function ecCancel()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'bookly_fid' ) );
        $userData->load();
        $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_ZARIN, 'cancelled' );
        @wp_redirect( remove_query_arg( Lib\Payment\Zarinpal::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
        exit;
    }

    /**
     * Process Express Checkout error request.
     */
    public function ecError()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'bookly_fid' ) );
        $userData->load();
        $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_ZARIN, 'error', $this->getParameter( 'error_msg' ) );
        @wp_redirect( remove_query_arg( Lib\Payment\Zarinpal::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
        exit;
    }

}