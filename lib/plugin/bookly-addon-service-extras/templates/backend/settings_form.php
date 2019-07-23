<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="tab-pane" id="ab_settings_service_extras">
    <form method="post" action="<?php echo esc_url( add_query_arg( 'tab', 'service_extras' ) ) ?>" class="ab-settings-form">
        <div class="form-group">
            <label for="bookly_service_extras_enabled"><?php _e( 'Extras', 'bookly-service-extras' ) ?></label>
            <p class="help-block"><?php _e( 'This setting enables or disables the Extras step of booking. You can choose what information should be displayed to your clients by using the checkboxes below.', 'bookly-service-extras' ) ?></p>
            <?php \Bookly\Lib\Utils\Common::optionToggle( 'bookly_service_extras_enabled' ) ?>
        </div>
        <div class="form-group">
            <label><?php _e( 'Show', 'bookly-service-extras' ) ?></label>
            <?php \Bookly\Lib\Utils\Common::optionFlags( 'bookly_service_extras_show', array( array( 'title', __( 'Title', 'bookly-service-extras' ) ), array( 'price', __( 'Price', 'bookly-service-extras' ) ), 'image' => array( 'image', __( 'Image', 'bookly-service-extras' ) ), 'duration' => array( 'duration', __( 'Duration', 'bookly-service-extras' ) ), 'summary' => array( 'summary', __( 'Summary', 'bookly-service-extras' ) ) ) ) ?>
        </div>

        <div class="panel-footer">
            <?php \Bookly\Lib\Utils\Common::submitButton() ?>
            <?php \Bookly\Lib\Utils\Common::resetButton() ?>
        </div>
    </form>
</div>