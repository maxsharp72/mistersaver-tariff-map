<?php
/**
 * Шорткод [ms_tariff_map] для встраивания карты.
 *
 *   [ms_tariff_map mode="full"]            — полная карта (для главной /tarify-zhku/)
 *   [ms_tariff_map mode="mini" region="moskva"] — мини-карта с подсветкой 1 региона
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

class MS_Tariff_Map_Shortcode {

    private static $rendered_on_page = false;

    public static function register(): void {
        add_shortcode( 'ms_tariff_map', [ self::class, 'render' ] );
    }

    /**
     * Условный enqueue: только если шорткод реально на странице.
     */
    public static function maybe_enqueue(): void {
        if ( ! is_singular() && ! is_post_type_archive( MS_Tariff_Map_CPT::POST_TYPE ) ) {
            return;
        }

        global $post;
        $need = false;

        if ( is_post_type_archive( MS_Tariff_Map_CPT::POST_TYPE ) ) {
            $need = true;
        } elseif ( $post && has_shortcode( $post->post_content, 'ms_tariff_map' ) ) {
            $need = true;
        } elseif ( is_singular( MS_Tariff_Map_CPT::POST_TYPE ) ) {
            $need = true; // На странице региона тоже хотим стили (мини-карта).
        }

        if ( ! $need ) {
            return;
        }

        self::enqueue_assets();
    }

    /**
     * Подключаем CSS и JS, выводим инлайн данные (key, REST URL).
     */
    private static function enqueue_assets(): void {
        $version = MS_TARIFF_MAP_VERSION;

        wp_enqueue_style(
            'ms-tariff-map',
            MS_TARIFF_MAP_URL . 'assets/css/map.css',
            [],
            $version
        );

        // OpenLayers с CDN.
        wp_enqueue_script(
            'openlayers',
            'https://cdn.jsdelivr.net/npm/ol@9.2.4/dist/ol.js',
            [],
            '9.2.4',
            true
        );

        // Наш скрипт.
        wp_enqueue_script(
            'ms-tariff-map',
            MS_TARIFF_MAP_URL . 'assets/js/map.js',
            [ 'openlayers' ],
            $version,
            true
        );

        // Опции (Яндекс ключ, REST endpoint, партнёрские ссылки).
        $settings = get_option( 'ms_tariff_map_settings', [] );
        $config = [
            'restUrl'      => rest_url( MS_Tariff_Map_REST::NAMESPACE . '/regions' ),
            'geojsonUrl'   => rest_url( MS_Tariff_Map_REST::NAMESPACE . '/regions.geojson' ),
            'yandexKey'    => $settings['yandex_api_key'] ?? '',
            'urlPrefix'    => '/' . MS_TARIFF_MAP_URL_PREFIX . '/',
            'partnerUrl'   => $settings['partner_payment_url'] ?? '',
        ];

        wp_add_inline_script(
            'ms-tariff-map',
            'window.__MS_TARIFF_MAP_CONFIG__ = ' . wp_json_encode( $config ) . ';',
            'before'
        );
    }

    /**
     * Рендер шорткода.
     */
    public static function render( $atts ): string {
        $atts = shortcode_atts( [
            'mode'   => 'full',     // full|mini
            'region' => '',         // slug для mini
            'height' => '700',      // высота в пикселях
            'layer'  => 'bill',     // bill|electricity|water|hot_water|gas
        ], $atts, 'ms_tariff_map' );

        // На случай если enqueue не сработал — форсим.
        if ( ! wp_script_is( 'ms-tariff-map', 'enqueued' ) ) {
            self::enqueue_assets();
        }

        // Уникальный ID контейнера если шорткод выведен несколько раз.
        $instance_id = 'ms-map-' . wp_unique_id();

        ob_start();
        if ( 'mini' === $atts['mode'] ) {
            self::render_mini( $instance_id, $atts );
        } else {
            self::render_full( $instance_id, $atts );
        }
        return ob_get_clean();
    }

    /**
     * Полная карта (для архива).
     */
    private static function render_full( string $id, array $atts ): void {
        $template = MS_TARIFF_MAP_DIR . 'templates/parts/map-full.php';
        if ( file_exists( $template ) ) {
            include $template;
        } else {
            // Fallback: минимальный HTML, если шаблон не найден.
            echo '<div class="ms-tariff-map ms-tariff-map--full" id="' . esc_attr( $id ) . '" data-layer="' . esc_attr( $atts['layer'] ) . '" style="min-height:' . intval( $atts['height'] ) . 'px"></div>';
        }
    }

    /**
     * Мини-карта с фокусом на один регион.
     */
    private static function render_mini( string $id, array $atts ): void {
        $template = MS_TARIFF_MAP_DIR . 'templates/parts/map-mini.php';
        if ( file_exists( $template ) ) {
            include $template;
        } else {
            echo '<div class="ms-tariff-map ms-tariff-map--mini" id="' . esc_attr( $id ) . '" data-region="' . esc_attr( $atts['region'] ) . '" style="min-height:' . intval( $atts['height'] ) . 'px"></div>';
        }
    }
}
