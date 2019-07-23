<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly  ?>
<div class="bookly-form">

    <!-- Progress Tracker-->
    <?php echo $progress_tracker ?>

    <div class="ab-row">
        <span data-inputclass="input-xxlarge" data-notes="<?php echo esc_html( \BooklyServiceExtras\Lib\Render::render( 'backend/_codes', array(), false ) ) ?>" data-placement="bottom" data-default="<?php echo esc_attr( get_option( 'ab_appearance_text_info_extras_step' ) ) ?>" class="ab_editable" id="ab-text-info-extras" data-rows="7" data-type="textarea"><?php echo esc_html( get_option( 'ab_appearance_text_info_extras_step' ) ) ?></span>
    </div>
    <div class="ab-extra-step">
        <div class="ab-row">
            <div class="bookly-extras-item">
                <div class="bookly-extras-thumb bookly-extras-selected">
                    <div><img style="margin-bottom: 8px" src="<?php echo plugins_url( 'bookly-addon-service-extras/images/medical.png' ) ?>" /></div>
                    <span>Dental Care Pack</span>
                    <strong><?php echo \Bookly\Lib\Utils\Common::formatPrice( 90 ) ?></strong>
                </div>
            </div>
            <div class="bookly-extras-item">
                <div class="bookly-extras-thumb">
                    <div><img style="margin-bottom: 8px" src="<?php echo plugins_url( 'bookly-addon-service-extras/images/teeth.png' ) ?>" /></div>
                    <span>Special Toothbrush</span>
                    <strong><?php echo \Bookly\Lib\Utils\Common::formatPrice( 15 ) ?></strong>
                </div>
            </div>
            <div class="bookly-extras-item">
                <div class="bookly-extras-thumb">
                    <div><img style="margin-bottom: 8px" src="<?php echo plugins_url( 'bookly-addon-service-extras/images/tool.png' ) ?>" /></div>
                    <span>Natural Toothpaste</span>
                    <strong><?php echo \Bookly\Lib\Utils\Common::formatPrice( 10 ) ?></strong>
                </div>
            </div>
        </div>

        <div class="ab-summary ab-row"><?php _e( 'Summary', 'bookly-service-extras' ) ?>: <?php echo \Bookly\Lib\Utils\Common::formatPrice( 350 ) ?> + <?php echo \Bookly\Lib\Utils\Common::formatPrice( 90 ) ?><span></span></div>
    </div>
    <div class="ab-row ab-nav-steps">
        <div class="ab-left ab-back-step ab-btn">
            <span class="text_back ab_editable" id="ab-text-button-back" data-mirror="text_back" data-type="text" data-default="<?php echo esc_attr( get_option( 'ab_appearance_text_button_back' ) ) ?>"><?php echo esc_html( get_option( 'ab_appearance_text_button_back' ) ) ?></span>
        </div>
        <button class="ab-left ab-goto-cart bookly-round-button ladda-button"><img src="<?php echo plugins_url( 'appointment-booking/frontend/resources/images/cart.png' ) ?>" /></button>
        <div class="ab-right ab-next-step ab-btn">
            <span class="ab_editable text_next" id="ab-text-button-next" data-mirror="text_next" data-type="text" data-default="<?php echo esc_attr( get_option( 'ab_appearance_text_button_next' ) ) ?>"><?php echo esc_html( get_option( 'ab_appearance_text_button_next' ) ) ?></span>
        </div>
    </div>
</div>