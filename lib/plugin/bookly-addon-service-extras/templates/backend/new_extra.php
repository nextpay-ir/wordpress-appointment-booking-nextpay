<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    $time_interval = get_option( 'ab_settings_time_slot_length' );
?>
<ul class="ab--templates extras">
    <li class="list-group-item extra new" data-extra-id="%id%">
        <div class="row">
            <div class="col-lg-3">
                <div class="bookly-flexbox">
                    <div class="bookly-flex-cell bookly-vertical-top">
                        <div class="bookly-js-handle bookly-margin-right-sm" title="<?php esc_attr_e( 'Reorder', 'bookly-service-extras' ) ?>">
                            <i class="bookly-icon bookly-icon-draghandle"></i>
                        </div>
                    </div>
                    <div class="bookly-flex-cell" style="width: 100%">
                        <div class="form-group">
                            <input type="hidden" name="extras[%id%][position]" value="9999">
                            <input type="hidden" name="extras[%id%][attachment_id]" value="">
                            <div class="extra-attachment-image bookly-thumb bookly-thumb-lg bookly-margin-right-lg">
                                <a class="bookly-js-remove-attachment dashicons dashicons-trash text-danger bookly-thumb-delete"
                                   href="javascript:void(0)"
                                   title="<?php _e( 'Delete', 'bookly-service-extras' ) ?>"
                                   style="display: none">
                                </a>
                                <div class="bookly-thumb-edit extra-attachment">
                                    <div class="bookly-pretty">
                                        <label class="bookly-pretty-indicator bookly-thumb-edit-btn">
                                            <?php _e( 'Image', 'bookly-service-extras' ) ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="form-group">
                    <label for="title_%id%"><?php _e( 'Title', 'bookly-service-extras' ) ?></label>
                    <input name="extras[%id%][title]" class="form-control" type="text" value="" id="title_%id%">
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="duration_%id%"><?php _e( 'Duration', 'bookly-service-extras' ) ?></label>
                            <select name="extras[%id%][duration]" id="duration_%id%" class="form-control">
                                <option value="0"><?php _e( 'OFF', 'bookly-service-extras' ) ?></option>
                                <?php for ( $j = $time_interval; $j <= 720; $j += $time_interval ) : ?>
                                    <option value="<?php echo $j * 60 ?>"><?php echo \Bookly\Lib\Utils\DateTime::secondsToInterval( $j * 60 ) ?></option><?php endfor ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="price_%id%"><?php _e( 'Price', 'bookly-service-extras' ) ?></label>
                            <input class="form-control" type="number" step="0.01" name="extras[%id%][price]" value="0.00" id="price_%id%">
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            <label for="max_quantity_%id%">
                                <?php _e( 'Max quantity', 'bookly-service-extras' ) ?>
                            </label>
                            <input name="extras[%id%][max_quantity]" class="form-control" type="number" step="1" id="max_quantity_%id%" min="1" value="1">
                        </div>
                    </div>
                </div>

                <div class="form-group text-right">
                    <?php \Bookly\Lib\Utils\Common::deleteButton( null, 'extra-delete' ) ?>
                </div>
            </div>
        </div>
    </li>
</ul>