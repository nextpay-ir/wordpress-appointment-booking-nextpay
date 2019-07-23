<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use Bookly\Lib\Utils\Common;
use Bookly\Lib\Utils\Price;
use Bookly\Lib\Proxy;
?>
<form method="post" action="<?php echo esc_url( add_query_arg( 'tab', 'payments' ) ) ?>">
    <div class="row">
        <div class="col-lg-4">
            <div class="form-group">
                <label for="bookly_pmt_currency"><?php _e( 'Currency', 'bookly' ) ?></label>
                <select id="bookly_pmt_currency" class="form-control" name="bookly_pmt_currency">
                    <?php foreach ( Price::getCurrencies() as $code => $currency ) : ?>
                        <option value="<?php echo $code ?>" data-symbol="<?php esc_attr_e( $currency['symbol'] ) ?>" <?php selected( get_option( 'bookly_pmt_currency' ), $code ) ?> ><?php echo $code ?> (<?php esc_html_e( $currency['symbol'] ) ?>)</option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="form-group">
                <label for="bookly_pmt_price_format"><?php _e( 'Price format', 'bookly' ) ?></label>
                <select id="bookly_pmt_price_format" class="form-control" name="bookly_pmt_price_format">
                    <?php foreach ( Price::getFormats() as $format ) : ?>
                        <option value="<?php echo $format ?>" <?php selected( get_option( 'bookly_pmt_price_format' ), $format ) ?> ></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <div class="col-lg-4">
            <?php Common::optionToggle( 'bookly_pmt_coupons', __( 'Coupons', 'bookly' ) ) ?>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <label for="bookly_pmt_local"><?php _e( 'Service paid locally', 'bookly' ) ?></label>
        </div>
        <div class="panel-body">
            <?php Common::optionToggle( 'bookly_pmt_local', null, null, array( array( 'disabled', __( 'Disabled', 'bookly' ) ), array( '1', __( 'Enabled', 'bookly' ) ) ) ) ?>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <label for="bookly_pmt_2checkout">2Checkout</label>
            <img style="margin-left: 10px; float: right" src="<?php echo plugins_url( 'frontend/resources/images/2Checkout.png', \Bookly\Lib\Plugin::getMainFile() ) ?>"/>
        </div>
        <div class="panel-body">
            <?php Common::optionToggle( 'bookly_pmt_2checkout', null, null, array( array( 'disabled', __( 'Disabled', 'bookly' ) ), array( 'standard_checkout', __( '2Checkout Standard Checkout', 'bookly' ) ) ) ) ?>
            <div class="bookly-2checkout">
                <div class="form-group">
                    <h4><?php _e( 'Instructions', 'bookly' ) ?></h4>
                    <p>
                        <?php _e( 'In <b>Checkout Options</b> of your 2Checkout account do the following steps:', 'bookly' ) ?>
                    </p>
                    <ol>
                        <li><?php _e( 'In <b>Direct Return</b> select <b>Header Redirect (Your URL)</b>.', 'bookly' ) ?></li>
                        <li><?php _e( 'In <b>Approved URL</b> enter the URL of your booking page.', 'bookly' ) ?></li>
                    </ol>
                    <p>
                        <?php _e( 'Finally provide the necessary information in the form below.', 'bookly' ) ?>
                    </p>
                </div>
                <?php Common::optionText( 'bookly_pmt_2checkout_api_seller_id', __( 'Account Number', 'bookly' ) ) ?>
                <?php Common::optionText( 'bookly_pmt_2checkout_api_secret_word', __( 'Secret Word', 'bookly' ) ) ?>
                <?php Common::optionToggle( 'bookly_pmt_2checkout_sandbox', __( 'Sandbox Mode', 'bookly' ), null, array( array( 0, __( 'No', 'bookly' ) ), array( 1, __( 'Yes', 'bookly' ) ) ) ) ?>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <label for="bookly_pmt_paypal">PayPal</label>
            <img style="margin-left: 10px; float: right" src="<?php echo plugins_url( 'frontend/resources/images/paypal.png', \Bookly\Lib\Plugin::getMainFile() ) ?>" />
        </div>
        <div class="panel-body">
            <div class="form-group">
                <?php Common::optionToggle( 'bookly_pmt_paypal', null, null,
                        Proxy\PaypalPaymentsStandard::prepareToggleOptions( array(
                            array( 'disabled', __( 'Disabled', 'bookly' ) ),
                            array( Bookly\Lib\Payment\PayPal::TYPE_EXPRESS_CHECKOUT, 'PayPal Express Checkout' ),
                        ) )
                ) ?>
            </div>
            <div class="bookly-paypal">
                <div class="bookly-paypal-ec">
                    <?php Common::optionText( 'bookly_pmt_paypal_api_username',  __( 'API Username', 'bookly' ) ) ?>
                    <?php Common::optionText( 'bookly_pmt_paypal_api_password',  __( 'API Password', 'bookly' ) ) ?>
                    <?php Common::optionText( 'bookly_pmt_paypal_api_signature', __( 'API Signature', 'bookly' ) ) ?>
                </div>
                <?php Proxy\PaypalPaymentsStandard::renderSetUpOptions() ?>
                <?php Common::optionToggle( 'bookly_pmt_paypal_sandbox', __( 'Sandbox Mode', 'bookly' ), null, array( array( 1, __( 'Yes', 'bookly' ) ), array( 0, __( 'No', 'bookly' ) ) ) ) ?>
            </div>
        </div>
    </div>
    
    <div class="panel panel-default">
        <div class="panel-heading">
            <label for="bookly_pmt_nextpay">درگاه پرداخت نکست پی</label>
            <img style="margin-left: 10px; float: right;height:27px" src="<?php echo plugins_url( 'frontend/resources/images/nextpay.svg', \Bookly\Lib\Plugin::getMainFile() ) ?>" />
        </div>
        <div class="panel-body">
            <div class="form-group">
				<?php Common::optionToggle( 'bookly_pmt_nextpay', null, null, array( array( 'disabled', __( 'Disabled', 'bookly' ) ), array( 'ec', __('Enable Nextpay','bookly') ) ) ) ?>
            </div>
            <div class="bookly-nextpay">
                <div class="bookly-nextpay-ec">
                    <?php Common::optionText( 'bookly_pmt_nextpay_apikey',  __( 'API KEY', 'bookly' ) ) ?>
                </div>
            </div>
        </div>
    </div>
	
	<div class="panel panel-default">
        <div class="panel-heading">
            <label for="bookly_pmt_zarin">درگاه پرداخت زرین پال</label>
            <img style="margin-left: 10px; float: right;height:27px" src="<?php echo plugins_url( 'frontend/resources/images/zarinpal.svg', \Bookly\Lib\Plugin::getMainFile() ) ?>" />
        </div>
        <div class="panel-body">
            <div class="form-group">
				<?php Common::optionToggle( 'bookly_pmt_zarin', null, null, array( array( 'disabled', __( 'Disabled', 'bookly' ) ), array( 'ec', __('Enable Zarin Pal','bookly') ) ) ) ?>
            </div>
            <div class="bookly-zarin">
                <div class="bookly-zarin-ec">
                    <?php Common::optionText( 'bookly_pmt_zarin_url',  __( 'API Url', 'bookly' ) ) ?>
                    <?php Common::optionText( 'bookly_pmt_zarin_merchantid',  __( 'API Marchant ID', 'bookly' ) ) ?>
                </div>
                <?php Common::optionToggle( 'bookly_pmt_zarin_sandbox', __( 'Sandbox Mode', 'bookly' ), null, array( array( 1, __( 'Yes', 'bookly' ) ), array( 0, __( 'No', 'bookly' ) ) ) ) ?>
            </div>
        </div>
    </div>
	
	<div class="panel panel-default">
        <div class="panel-heading">
            <label for="bookly_pmt_mellat">درگاه پرداخت بانک ملت</label>
            <img style="margin-left: 10px; float: right;height:27px" src="<?php echo plugins_url( 'frontend/resources/images/mellat.svg', \Bookly\Lib\Plugin::getMainFile() ) ?>" />
        </div>
        <div class="panel-body">
            <div class="form-group">
				<?php Common::optionToggle( 'bookly_pmt_mellat', null, null, array( array( 'disabled', __( 'Disabled', 'bookly' ) ), array( 'ec', __('Enable Mellat Gateway','bookly' )) ) ) ?>
            </div>
            <div class="bookly-mellat">
                <div class="bookly-mellat-ec">
                    <?php Common::optionText( 'bookly_pmt_mellat_url',  __( 'API Url', 'bookly' ) ) ?>
                    <?php Common::optionText( 'bookly_pmt_mellat_serverurl',  __( 'API Server Url', 'bookly' ) ) ?>
					<?php Common::optionText( 'bookly_pmt_mellat_namespace',  __( 'API namespace', 'bookly' ) ) ?>
					<?php Common::optionText( 'bookly_pmt_mellat_terminalID',  __( 'API ID', 'bookly' ) ) ?>
					<?php Common::optionText( 'bookly_pmt_mellat_username',  __( 'API Username', 'bookly' ) ) ?>
					<?php Common::optionText( 'bookly_pmt_mellat_password',  __( 'API Password', 'bookly' ) ) ?>
                </div>
            </div>
        </div>
    </div>



    <div class="panel panel-default">
        <div class="panel-heading">
            <label for="bookly_pmt_stripe">Stripe</label>
            <img class="pull-right" src="<?php echo plugins_url( 'frontend/resources/images/stripe.png', \Bookly\Lib\Plugin::getMainFile() ) ?>">
        </div>
        <div class="panel-body">
            <?php Common::optionToggle( 'bookly_pmt_stripe', null, null, array( array( 'disabled', __( 'Disabled', 'bookly' ) ), array( '1', __( 'Enabled', 'bookly' ) ) ) ) ?>
            <div class="bookly-stripe">
                <div class="form-group">
                    <h4><?php _e( 'Instructions', 'bookly' ) ?></h4>
                    <p>
                        <?php _e( 'If <b>Publishable Key</b> is provided then Bookly will use <a href="https://stripe.com/docs/stripe.js" target="_blank">Stripe.js</a><br/>for collecting credit card details.', 'bookly' ) ?>
                    </p>
                </div>
                <?php Common::optionText( 'bookly_pmt_stripe_secret_key', __( 'Secret Key', 'bookly' ) ) ?>
                <?php Common::optionText( 'bookly_pmt_stripe_publishable_key', __( 'Publishable Key', 'bookly' ) ) ?>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <label for="bookly_pmt_payu_latam">PayU Latam</label>
            <img class="pull-right" src="<?php echo plugins_url( 'frontend/resources/images/payu_latam.png', \Bookly\Lib\Plugin::getMainFile() ) ?>"/>
        </div>
        <div class="panel-body">
            <?php Common::optionToggle( 'bookly_pmt_payu_latam', null, null, array( array( 'disabled', __( 'Disabled', 'bookly' ) ), array( '1', __( 'Enabled', 'bookly' ) ) ) ) ?>
            <div class="bookly-payu_latam">
                <?php Common::optionText( 'bookly_pmt_payu_latam_api_key', __( 'API Key', 'bookly' ) ) ?>
                <?php Common::optionText( 'bookly_pmt_payu_latam_api_account_id', __( 'Account ID', 'bookly' ) ) ?>
                <?php Common::optionText( 'bookly_pmt_payu_latam_api_merchant_id', __( 'Merchant ID', 'bookly' ) ) ?>
                <?php Common::optionToggle( 'bookly_pmt_payu_latam_sandbox', __( 'Sandbox Mode', 'bookly' ), null, array( array( 0, __( 'No', 'bookly' ) ), array( 1, __( 'Yes', 'bookly' ) ) ) ) ?>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <label for="bookly_pmt_payson">Payson</label>
            <img class="pull-right" src="<?php echo plugins_url( 'frontend/resources/images/payson.png', \Bookly\Lib\Plugin::getMainFile() ) ?>"/>
        </div>
        <div class="panel-body">
            <?php Common::optionToggle( 'bookly_pmt_payson', null, null, array( array( 'disabled', __( 'Disabled', 'bookly' ) ), array( '1', __( 'Enabled', 'bookly' ) ) ) ) ?>
            <div class="bookly-payson">
                <?php Common::optionText( 'bookly_pmt_payson_api_agent_id', __( 'Agent ID', 'bookly' ) ) ?>
                <?php Common::optionText( 'bookly_pmt_payson_api_key', __( 'API Key', 'bookly' ) ) ?>
                <?php Common::optionText( 'bookly_pmt_payson_api_receiver_email', __( 'Receiver Email (login)', 'bookly' ) ) ?>
                <?php Common::optionFlags( 'bookly_pmt_payson_funding', array( array( 'CREDITCARD', __( 'Card', 'bookly' ) ), array( 'INVOICE', __( 'Invoice', 'bookly' ) ) ), __( 'Funding', 'bookly' ) ) ?>
                <?php Common::optionToggle( 'bookly_pmt_payson_fees_payer', __( 'Fees Payer', 'bookly' ), null, array( array( 'PRIMARYRECEIVER', __( 'I am', 'bookly' ) ), array( 'SENDER', __( 'Client', 'bookly' ) ) ) ) ?>
                <?php Common::optionToggle( 'bookly_pmt_payson_sandbox', __( 'Sandbox Mode', 'bookly' ), null, array( array( 0, __( 'No', 'bookly' ) ), array( 1, __( 'Yes', 'bookly' ) ) ) ) ?>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <label for="bookly_pmt_mollie">Mollie</label>
            <img class="pull-right" src="<?php echo plugins_url( 'frontend/resources/images/mollie.png', \Bookly\Lib\Plugin::getMainFile() ) ?>"/>
        </div>
        <div class="panel-body">
            <?php Common::optionToggle( 'bookly_pmt_mollie', null, null, array( array( 'disabled', __( 'Disabled', 'bookly' ) ), array( '1', __( 'Enabled', 'bookly' ) ) ) ) ?>
            <div class="bookly-mollie">
                <?php Common::optionText( 'bookly_pmt_mollie_api_key', __( 'API Key', 'bookly' ) ) ?>
            </div>
        </div>
    </div>

    <?php do_action( 'bookly_render_payment_settings' ) ?>

    <div class="panel-footer">
        <?php Common::csrf() ?>
        <?php Common::submitButton() ?>
        <?php Common::resetButton( 'bookly-payments-reset' ) ?>
    </div>
</form>
