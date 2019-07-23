<?php
namespace BooklyServiceExtras\Lib;

/**
 * Class Render
 *
 * @package BooklyServiceExtras\Lib
 */
class Render
{
    /**
     * Render a template file.
     *
     * @param $template
     * @param array $variables
     * @param bool $echo
     * @return string
     * @throws \Exception
     */
    static function render( $template, $variables = array(), $echo = true )
    {
        extract( $variables );

        // Start output buffering.
        ob_start();
        ob_implicit_flush( 0 );

        try {
            include Plugin::getDirectory() . '/templates/' . $template . '.php';
        } catch ( \Exception $e ) {
            ob_end_clean();
            throw $e;
        }

        if ( $echo ) {
            echo ob_get_clean();
        } else {
            return ob_get_clean();
        }
    }

}