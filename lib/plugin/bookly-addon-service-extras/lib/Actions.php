<?php
namespace BooklyServiceExtras\Lib;

/**
 * Class Actions
 * @package BooklyServiceExtras\Lib
 */
class Actions
{
    /**
     * Render form for creating new Extra in service profile.
     *
     * @throws \Exception
     */
    public static function renderAfterServiceList()
    {
        Render::render( 'backend/new_extra' );
    }

    /**
     * Save Extra.
     *
     * @param \Bookly\Lib\Entities\Service $service
     * @param array $parameters
     */
    public static function updateService( \Bookly\Lib\Entities\Service $service, $parameters )
    {
        foreach ( $parameters['extras'] as $data ) {
            $form = new \BooklyServiceExtras\Forms\ServiceExtra();
            $data['service_id'] = $service->get( 'id' );
            $form->bind( $data );
            $form->save();
        }
    }

    /**
     * Reorder Extras.
     *
     * @param $order
     */
    public static function reorder( $order )
    {
        foreach ( (array) $order as $position => $extra_id ) {
            $extra = new Entities\ServiceExtra();
            $extra->load( $extra_id );
            $extra->set( 'position', $position );
            $extra->save();
        }
    }

    /**
     * Render extras settings form in Bookly Settings.
     *
     * @throws \Exception
     */
    public static function renderSettingsForm()
    {
        Render::render( 'backend/settings_form' );
    }

    /**
     * Render extras menu in Bookly Settings.
     */
    public static function renderSettingsMenu()
    {
        printf( '<li class="bookly-nav-item" data-target="#ab_settings_service_extras" data-toggle="tab">%s</li>', __( 'Service Extras', 'bookly-service-extras' ) );
    }

    /**
     * Render extras tab in Bookly Appearance.
     *
     * @param $progress_tracker
     * @throws \Exception
     */
    public static function renderAppearanceTab( $progress_tracker )
    {
        if ( \Bookly\Lib\Config::extrasEnabled() ) {
            Render::render( 'backend/appearance_tab', array( 'progress_tracker' => $progress_tracker ) );
        }
    }

    public static function executeDeleteServiceExtra()
    {
        $extra = new Entities\ServiceExtra();
        $extra->set( 'id', $_POST['id'] );
        $extra->delete();
        wp_send_json_success();
    }

    public static function registerWpActions( $prefix )
    {
        $reflection = new \ReflectionClass( '\BooklyServiceExtras\Lib\Actions' );
        foreach ( $reflection->getMethods( \ReflectionMethod::IS_STATIC ) as $method ) {
            if ( preg_match( '/^execute(.*)/', $method->name, $match ) ) {
                add_action(
                    $prefix . strtolower( preg_replace( '/([a-z])([A-Z])/', '$1_$2', $match[1] ) ),
                    array( $method->class, $method->name )
                );
            }
        }
    }

}