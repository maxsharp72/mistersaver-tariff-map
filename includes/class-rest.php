<?php
/**
 * REST API эндпоинты плагина.
 *
 * GET /wp-json/mistersaver/v1/regions          — список регионов с тарифами для фронта.
 * GET /wp-json/mistersaver/v1/regions.geojson  — GeoJSON с геометрией + properties.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

class MS_Tariff_Map_REST {

    public const NAMESPACE = 'mistersaver/v1';

    /**
     * Регистрация маршрутов.
     */
    public static function register_routes(): void {
        register_rest_route( self::NAMESPACE, '/regions', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'get_regions' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( self::NAMESPACE, '/regions.geojson', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'get_regions_geojson' ],
            'permission_callback' => '__return_true',
        ] );
    }

    /**
     * Список регионов (для таблицы и поиска).
     */
    public static function get_regions( WP_REST_Request $request ): WP_REST_Response {
        $cache_key = 'ms_tariff_map_regions_v1';
        $data = get_transient( $cache_key );
        if ( $data === false ) {
            $data = self::build_regions_array();
            set_transient( $cache_key, $data, HOUR_IN_SECONDS );
        }
        $response = new WP_REST_Response( $data, 200 );
        $response->header( 'Cache-Control', 'public, max-age=3600' );
        return $response;
    }

    /**
     * GeoJSON с геометрией.
     */
    public static function get_regions_geojson( WP_REST_Request $request ): WP_REST_Response {
        $cache_key = 'ms_tariff_map_geojson_v1';
        $data = get_transient( $cache_key );
        if ( $data === false ) {
            $data = self::build_geojson();
            set_transient( $cache_key, $data, HOUR_IN_SECONDS );
        }
        $response = new WP_REST_Response( $data, 200 );
        $response->header( 'Cache-Control', 'public, max-age=3600' );
        return $response;
    }

    /**
     * Строит массив регионов из БД.
     */
    public static function build_regions_array(): array {
        $posts = get_posts( [
            'post_type'      => MS_Tariff_Map_CPT::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'avg_monthly_bill',
            'order'          => 'ASC',
        ] );

        $regions = [];
        foreach ( $posts as $post ) {
            $regions[] = self::post_to_properties( $post );
        }
        return $regions;
    }

    /**
     * Превращает CPT-пост в массив свойств для фронта.
     */
    public static function post_to_properties( WP_Post $post ): array {
        $id = $post->ID;
        $acf = function_exists( 'get_field' );

        // Округ — берём из ACF либо из таксономии.
        $district = MS_Tariff_Map_ACF::get( $id, 'federal_district' );
        if ( empty( $district ) ) {
            $terms = wp_get_post_terms( $id, MS_Tariff_Map_CPT::TAXONOMY );
            $district = ! empty( $terms ) ? strtoupper( $terms[0]->slug ) : '';
        }

        $bill      = self::num( MS_Tariff_Map_ACF::get( $id, 'avg_monthly_bill' ) );
        $tier      = (int) ( MS_Tariff_Map_ACF::get( $id, 'color_tier' ) ?: 0 );
        $estimated = (bool) MS_Tariff_Map_ACF::get( $id, 'bill_estimated', false );

        return [
            'id'                => (int) ( MS_Tariff_Map_ACF::get( $id, 'region_id' ) ?: $id ),
            'slug'              => $post->post_name,
            'name'              => $post->post_title,
            'short_name'        => MS_Tariff_Map_ACF::get( $id, 'region_name_short' ) ?: $post->post_title,
            'district'          => $district,
            'center_city'       => MS_Tariff_Map_ACF::get( $id, 'center_city' ),
            'lat'               => self::num( MS_Tariff_Map_ACF::get( $id, 'lat' ) ),
            'lon'               => self::num( MS_Tariff_Map_ACF::get( $id, 'lon' ) ),
            'bill'              => $bill,
            'bill_estimated'    => $estimated,
            'electricity'       => self::num( MS_Tariff_Map_ACF::get( $id, 'tariff_electricity_day' ) ),
            'electricity_night' => self::num( MS_Tariff_Map_ACF::get( $id, 'tariff_electricity_night' ) ),
            'water'             => self::num( MS_Tariff_Map_ACF::get( $id, 'tariff_cold_water' ) ),
            'hot_water'         => self::num( MS_Tariff_Map_ACF::get( $id, 'tariff_hot_water' ) ),
            'wastewater'        => self::num( MS_Tariff_Map_ACF::get( $id, 'tariff_wastewater' ) ),
            'heat'              => self::num( MS_Tariff_Map_ACF::get( $id, 'tariff_heat' ) ),
            'gas'               => self::num( MS_Tariff_Map_ACF::get( $id, 'tariff_gas' ) ),
            'garbage'           => self::num( MS_Tariff_Map_ACF::get( $id, 'tariff_garbage' ) ),
            'index_2024'        => self::num( MS_Tariff_Map_ACF::get( $id, 'index_2024' ) ),
            'index_2025'        => self::num( MS_Tariff_Map_ACF::get( $id, 'index_2025' ) ),
            'index_2026'        => self::num( MS_Tariff_Map_ACF::get( $id, 'index_2026' ) ),
            'regulator'         => MS_Tariff_Map_ACF::get( $id, 'regulatory_body' ),
            'tariff_date'       => MS_Tariff_Map_ACF::get( $id, 'tariff_date' ),
            'tier'              => $tier,
            'has_data'          => $bill !== null,
            'url'               => get_permalink( $post ),
        ];
    }

    /**
     * Строит GeoJSON, подставляя геометрию из data/regions.geojson по slug.
     */
    public static function build_geojson(): array {
        $base = self::load_base_geojson();
        if ( empty( $base['features'] ) ) {
            return [ 'type' => 'FeatureCollection', 'features' => [] ];
        }

        // Маппинг slug → properties.
        $props_by_slug = [];
        foreach ( self::build_regions_array() as $r ) {
            $props_by_slug[ $r['slug'] ] = $r;
        }

        $features = [];
        foreach ( $base['features'] as $feature ) {
            $slug = $feature['properties']['slug'] ?? '';
            if ( ! isset( $props_by_slug[ $slug ] ) ) {
                continue;
            }
            // Подменяем properties на свежие из БД.
            $feature['properties'] = $props_by_slug[ $slug ];
            $features[] = $feature;
        }

        return [ 'type' => 'FeatureCollection', 'features' => $features ];
    }

    /**
     * Загружает базовый GeoJSON с геометрией.
     */
    private static function load_base_geojson(): array {
        $file = MS_TARIFF_MAP_DIR . 'data/regions.geojson';
        if ( ! file_exists( $file ) ) {
            return [];
        }
        $json = file_get_contents( $file );
        $data = json_decode( $json, true );
        return is_array( $data ) ? $data : [];
    }

    /**
     * Сбрасывает кеш при сохранении/удалении любого поста типа region_tariff.
     */
    public static function invalidate_cache(): void {
        delete_transient( 'ms_tariff_map_regions_v1' );
        delete_transient( 'ms_tariff_map_geojson_v1' );
    }

    private static function num( $v ) {
        if ( $v === null || $v === '' ) {
            return null;
        }
        if ( is_numeric( $v ) ) {
            return $v + 0; // int или float
        }
        return null;
    }
}

// Инвалидация кеша при изменениях.
add_action( 'save_post_' . MS_Tariff_Map_CPT::POST_TYPE, [ MS_Tariff_Map_REST::class, 'invalidate_cache' ] );
add_action( 'deleted_post', [ MS_Tariff_Map_REST::class, 'invalidate_cache' ] );
