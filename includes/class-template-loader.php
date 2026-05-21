<?php
/**
 * Подмена single/archive шаблонов для CPT region_tariff.
 *
 * Сначала ищем в дочерней теме (`/single-region_tariff.php`),
 * затем в родительской теме, и только потом — наш шаблон из плагина.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

class MS_Tariff_Map_Template_Loader {

    public static function single_template( string $template ): string {
        if ( is_singular( MS_Tariff_Map_CPT::POST_TYPE ) ) {
            $theme = locate_template( [ 'single-region_tariff.php' ] );
            if ( $theme ) {
                return $theme;
            }
            $plugin = MS_TARIFF_MAP_DIR . 'templates/single-region_tariff.php';
            if ( file_exists( $plugin ) ) {
                return $plugin;
            }
        }
        return $template;
    }

    public static function archive_template( string $template ): string {
        if ( is_post_type_archive( MS_Tariff_Map_CPT::POST_TYPE ) ) {
            $theme = locate_template( [ 'archive-region_tariff.php' ] );
            if ( $theme ) {
                return $theme;
            }
            $plugin = MS_TARIFF_MAP_DIR . 'templates/archive-region_tariff.php';
            if ( file_exists( $plugin ) ) {
                return $plugin;
            }
        }
        return $template;
    }
}
