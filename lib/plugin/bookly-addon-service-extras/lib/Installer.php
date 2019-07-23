<?php
namespace BooklyServiceExtras\Lib;

/**
 * Class Installer
 * @package BooklyServiceExtras\Lib
 */
class Installer extends \Bookly\Lib\Base\Installer
{
    public function __construct()
    {
        // Load l10n for fixtures creating.
        load_plugin_textdomain( Plugin::getTextDomain(), false, Plugin::getSlug() . '/languages' );

        $this->options = array(
            'bookly_service_extras_data_loaded'             => '0',
            'bookly_service_extras_enabled'                 => '1',
            'bookly_service_extras_installation_time'       => time(),
            'bookly_service_extras_show'                    => array( 'title', 'price' ),
            // DB version.
            'bookly_service_extras_db_version'              => Plugin::getVersion(),
            Plugin::getPurchaseCode()                       => '',
            'ab_appearance_text_step_extras'                => __( 'Extras', 'bookly-service-extras' ),
            'ab_appearance_text_info_extras_step'           => __( 'Select the Extras you\'d like (Multiple Selection)', 'bookly-service-extras' )
        );

        $this->tables = array(
            Entities\ServiceExtra::getTableName(),
        );
    }

    /**
     * Create tables in database.
     */
    protected function _create_tables()
    {
        /** @global \wpdb $wpdb */
        global $wpdb;

        parent::_create_tables();

        $wpdb->query(
            'CREATE TABLE IF NOT EXISTS `' . Entities\ServiceExtra::getTableName() . '` (
                `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `service_id`    INT UNSIGNED NOT NULL,
                `attachment_id` INT UNSIGNED DEFAULT NULL,
                `title`         VARCHAR(255) DEFAULT "",
                `duration`      INT NOT NULL DEFAULT 0,
                `price`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `max_quantity`  INT NOT NULL DEFAULT 1,
                `position`      INT NOT NULL DEFAULT 9999,
                CONSTRAINT
                    FOREIGN KEY (service_id)
                    REFERENCES ' . \Bookly\Lib\Entities\Service::getTableName() . '(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE = INNODB
            DEFAULT CHARACTER SET = utf8
            COLLATE = utf8_general_ci'
        );
    }

}