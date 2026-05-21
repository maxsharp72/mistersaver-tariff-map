<?php
/**
 * ACF поля для CPT region_tariff.
 *
 * Поддерживает оба сценария:
 *   1. ACF Pro установлен → используем acf-json/ для Local JSON.
 *   2. ACF не установлен → используем native WP meta-поля (читаем через get_post_meta).
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

class MS_Tariff_Map_ACF {

    /**
     * Путь к acf-json внутри плагина — для загрузки полей.
     */
    public static function add_load_path( array $paths ): array {
        $paths[] = MS_TARIFF_MAP_DIR . 'acf-json';
        return $paths;
    }

    /**
     * Путь, куда ACF сохраняет JSON при экспорте.
     */
    public static function set_save_path( string $path ): string {
        return MS_TARIFF_MAP_DIR . 'acf-json';
    }

    /**
     * Программная регистрация полей (запасной путь, если acf-json не сработал).
     */
    public static function register_fields(): void {
        if ( ! function_exists( 'acf_add_local_field_group' ) ) {
            return;
        }

        acf_add_local_field_group( [
            'key'      => 'group_ms_tariff_region',
            'title'    => 'Тарифы региона',
            'fields'   => self::fields(),
            'location' => [ [ [
                'param'    => 'post_type',
                'operator' => '==',
                'value'    => MS_Tariff_Map_CPT::POST_TYPE,
            ] ] ],
            'menu_order'            => 0,
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
            'active'                => true,
        ] );
    }

    /**
     * Описание всех ACF-полей региона.
     */
    private static function fields(): array {
        return [
            // --- Идентификация ---
            self::field( 'region_id',          'ID региона',             'number', [ 'instructions' => 'Числовой ID по реестру субъектов РФ' ] ),
            self::field( 'region_name_short',  'Краткое название',       'text',   [ 'instructions' => 'Например: Ленинградская обл.' ] ),
            self::field( 'federal_district',   'Федеральный округ',      'select', [
                'choices' => MS_Tariff_Map_CPT::districts(),
            ] ),
            self::field( 'center_city',        'Административный центр', 'text' ),
            self::field( 'lat',                'Широта',                 'number', [ 'step' => '0.0001' ] ),
            self::field( 'lon',                'Долгота',                'number', [ 'step' => '0.0001' ] ),

            // --- Тарифы ---
            self::field( 'tariff_cold_water',        'Холодная вода, ₽/м³',          'number', [ 'step' => '0.01' ] ),
            self::field( 'tariff_hot_water',         'Горячая вода, ₽/м³',           'number', [ 'step' => '0.01' ] ),
            self::field( 'tariff_wastewater',        'Водоотведение, ₽/м³',          'number', [ 'step' => '0.01' ] ),
            self::field( 'tariff_electricity_day',   'Электроэнергия день, ₽/кВт·ч', 'number', [ 'step' => '0.01' ] ),
            self::field( 'tariff_electricity_night', 'Электроэнергия ночь, ₽/кВт·ч', 'number', [ 'step' => '0.01' ] ),
            self::field( 'tariff_heat',              'Отопление, ₽/Гкал',            'number', [ 'step' => '0.01' ] ),
            self::field( 'tariff_gas',               'Газ, ₽/м³',                    'number', [ 'step' => '0.01' ] ),
            self::field( 'tariff_garbage',           'ТКО, ₽/чел./мес.',             'number', [ 'step' => '0.01' ] ),

            // --- Расчётные значения ---
            self::field( 'avg_monthly_bill',  'Средний платёж семьи 3 чел., ₽/мес', 'number' ),
            self::field( 'bill_estimated',    'Bill оценочный?', 'true_false',     [ 'instructions' => 'Включить, если значение platежа рассчитано приблизительно' ] ),
            self::field( 'color_tier',        'Уровень тарифа (1-5)', 'number',    [ 'min' => 1, 'max' => 5 ] ),

            // --- Индексация ---
            self::field( 'index_2024', 'Индексация 2024, %', 'number', [ 'step' => '0.1' ] ),
            self::field( 'index_2025', 'Индексация 2025, %', 'number', [ 'step' => '0.1' ] ),
            self::field( 'index_2026', 'Индексация 2026, %', 'number', [ 'step' => '0.1' ] ),

            // --- Источник / даты ---
            self::field( 'tariff_date',     'Дата актуальности тарифов', 'date_picker', [ 'display_format' => 'Y-m-d', 'return_format' => 'Y-m-d' ] ),
            self::field( 'regulatory_body', 'Регулятор',                 'text' ),
            self::field( 'regulatory_url',  'Сайт регулятора',           'url' ),

            // --- Партнёрские ссылки ---
            self::field( 'partner_link_payment',  'Партнёрская ссылка: оплата ЖКУ', 'url' ),
            self::field( 'partner_link_cashback', 'Партнёрская ссылка: кэшбэк',     'url' ),
        ];
    }

    /**
     * Хелпер для создания поля.
     */
    private static function field( string $name, string $label, string $type, array $extra = [] ): array {
        return array_merge( [
            'key'   => 'field_ms_tariff_' . $name,
            'name'  => $name,
            'label' => $label,
            'type'  => $type,
        ], $extra );
    }

    /**
     * Универсальный геттер: работает и с ACF, и с native meta.
     */
    public static function get( int $post_id, string $field_name, $default = null ) {
        if ( function_exists( 'get_field' ) ) {
            $value = get_field( $field_name, $post_id );
            if ( $value !== null && $value !== '' ) {
                return $value;
            }
        }
        $meta = get_post_meta( $post_id, $field_name, true );
        return ( $meta === '' || $meta === false ) ? $default : $meta;
    }

    /**
     * Универсальный сеттер.
     */
    public static function set( int $post_id, string $field_name, $value ): void {
        if ( function_exists( 'update_field' ) ) {
            update_field( $field_name, $value, $post_id );
            return;
        }
        update_post_meta( $post_id, $field_name, $value );
    }
}
