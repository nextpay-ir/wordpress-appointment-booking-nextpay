<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use Bookly\Lib\Utils\Common;
?>
<form method="post" action="<?php echo esc_url( add_query_arg( 'tab', 'smspanel' ) ) ?>" class="bookly-settings-form">
        <div class="panel-body">
            <div class="form-group">
				<?php Common::optionToggle( 'bookly_sms_beferest', null, null, array( array( 'disabled', __( 'Disabled', 'bookly' ) ), array( 'ec', 'فعال کردن سرویس' ) ) ) ?>
            </div>
            <div class="form-group bookly-sms-ec">
				<?php Common::optionText( 'bookly_sms_beferest_url',  __( 'آدرس وب سرویس', 'bookly' ) ) ?>
				<?php Common::optionText( 'bookly_sms_beferest_number',  __( 'شماره پنل', 'bookly' ) ) ?>
				<?php Common::optionText( 'bookly_sms_beferest_username',  __( 'نام کاربری', 'bookly' ) ) ?>
				<?php Common::optionText( 'bookly_sms_beferest_password',  __( 'پسورد', 'bookly' ) ) ?>
            </div>
        </div>
	<div class="panel-footer">
        <?php Common::csrf() ?>
        <?php Common::submitButton() ?>
        <?php Common::resetButton() ?>
    </div>
</form>