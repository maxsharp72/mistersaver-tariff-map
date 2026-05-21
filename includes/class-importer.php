<?php
/**
 * Импортёр: создаёт/обновляет записи CPT region_tariff из JSON-датасета.
 *
 * Источник правды: data/regions-tariffs.json
 *
 * Логика:
 *   - Ищет регион по region_slug.
 *   - Если есть → update (тарифы обновляются, post_content НЕ затирается).
 *   - Если нет  → wp_insert_post + ACF-поля + сгенерированный из шаблона post_content.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

class MS_Tariff_Map_Importer {

    /**
     * Импорт из data/regions-tariffs.json.
     */
    public static function import_default(): array {
        $file = MS_TARIFF_MAP_DIR . 'data/regions-tariffs.json';
        if ( ! file_exists( $file ) ) {
            return [ 'success' => false, 'message' => 'Файл data/regions-tariffs.json не найден' ];
        }
        return self::import_file( $file );
    }

    /**
     * Импорт из произвольного файла.
     */
    public static function import_file( string $file_path ): array {
        if ( ! file_exists( $file_path ) ) {
            return [ 'success' => false, 'message' => "Файл не найден: $file_path" ];
        }
        $json = file_get_contents( $file_path );
        $data = json_decode( $json, true );
        if ( ! is_array( $data ) ) {
            return [ 'success' => false, 'message' => 'Невалидный JSON' ];
        }

        $stats = [ 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [] ];
        foreach ( $data as $row ) {
            $result = self::import_row( $row );
            if ( $result === 'created' ) $stats['created']++;
            elseif ( $result === 'updated' ) $stats['updated']++;
            elseif ( $result === 'skipped' ) $stats['skipped']++;
            else $stats['errors'][] = $result;
        }

        // Сбросим кеш REST.
        MS_Tariff_Map_REST::invalidate_cache();
        // Сбросим rewrite — на случай если slugs изменились.
        flush_rewrite_rules();

        return array_merge( [ 'success' => true ], $stats );
    }

    /**
     * Импортирует одну запись.
     *
     * @return string  'created' | 'updated' | 'skipped' | error message
     */
    private static function import_row( array $row ): string {
        if ( empty( $row['slug'] ) ) {
            return 'skipped: empty slug';
        }
        $slug = sanitize_title( $row['slug'] );

        // Ищем существующий пост по post_name.
        $existing = get_page_by_path( $slug, OBJECT, MS_Tariff_Map_CPT::POST_TYPE );

        $post_data = [
            'post_type'   => MS_Tariff_Map_CPT::POST_TYPE,
            'post_status' => 'publish',
            'post_name'   => $slug,
            'post_title'  => $row['name'] ?? $slug,
        ];

        $action = 'updated';
        if ( $existing ) {
            $post_data['ID'] = $existing->ID;
            $post_id = wp_update_post( $post_data, true );
        } else {
            $post_data['post_content'] = self::generate_default_content( $row );
            $post_id = wp_insert_post( $post_data, true );
            $action = 'created';
        }

        if ( is_wp_error( $post_id ) ) {
            return 'error: ' . $post_id->get_error_message();
        }

        // Записываем ACF / meta поля.
        $field_map = [
            'region_id'                => 'id',
            'region_name_short'        => 'short_name',
            'federal_district'         => 'district',
            'center_city'              => 'center_city',
            'lat'                      => 'lat',
            'lon'                      => 'lon',
            'tariff_cold_water'        => 'water',
            'tariff_hot_water'         => 'hot_water',
            'tariff_wastewater'        => 'wastewater',
            'tariff_electricity_day'   => 'electricity',
            'tariff_electricity_night' => 'electricity_night',
            'tariff_heat'              => 'heat',
            'tariff_gas'               => 'gas',
            'tariff_garbage'           => 'garbage',
            'avg_monthly_bill'         => 'bill',
            'bill_estimated'           => 'bill_estimated',
            'color_tier'               => 'tier',
            'index_2024'               => 'index_2024',
            'index_2025'               => 'index_2025',
            'index_2026'               => 'index_2026',
            'tariff_date'              => 'tariff_date',
            'regulatory_body'          => 'regulator',
            'regulatory_url'           => 'regulatory_url',
        ];
        foreach ( $field_map as $acf_key => $json_key ) {
            if ( isset( $row[ $json_key ] ) ) {
                MS_Tariff_Map_ACF::set( $post_id, $acf_key, $row[ $json_key ] );
            }
        }

        // Округ → таксономия.
        if ( ! empty( $row['district'] ) ) {
            wp_set_object_terms( $post_id, $row['district'], MS_Tariff_Map_CPT::TAXONOMY );
        }

        return $action;
    }

    /**
     * Дефолтный шаблон контента (без LLM). Используется при создании нового региона.
     */
    private static function generate_default_content( array $row ): string {
        $name = $row['name'] ?? '';
        $bill = isset( $row['bill'] ) ? number_format( (float) $row['bill'], 0, ',', ' ' ) : '—';
        $regulator = $row['regulator'] ?? '';
        $district = $row['district'] ?? '';

        $content = "<p>На странице собраны актуальные тарифы на жилищно-коммунальные услуги в регионе <strong>{$name}</strong>. ";
        $content .= "Данные обновлены с 1 июля 2025 года на основе официальных постановлений регионального регулятора";
        if ( $regulator ) {
            $content .= " ({$regulator})";
        }
        $content .= ".</p>\n\n";

        $content .= "<p>Средний платёж семьи из 3 человек в регионе составляет около <strong>{$bill} ₽ в&nbsp;месяц</strong>. ";
        $content .= "Используйте таблицу ниже, чтобы посмотреть тарифы на электроэнергию, холодную и горячую воду, газ, отопление и ТКО.</p>\n\n";

        $content .= "<!-- ms-tariff-table -->\n\n";
        $content .= "<p>Регион входит в {$district} федеральный округ. Сравните тарифы с соседними регионами на интерактивной карте.</p>\n";

        return $content;
    }
}
