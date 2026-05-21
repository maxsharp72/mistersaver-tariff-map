<?php
/**
 * Регистрация Custom Post Type `region_tariff` и таксономии `federal_district`.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

class MS_Tariff_Map_CPT {

    public const POST_TYPE = 'region_tariff';
    public const TAXONOMY  = 'federal_district';

    public static function register(): void {
        self::register_post_type();
        self::register_taxonomy();
    }

    private static function register_post_type(): void {
        $labels = [
            'name'               => 'Тарифы регионов',
            'singular_name'      => 'Тарифы региона',
            'menu_name'          => 'Тарифы ЖКУ',
            'name_admin_bar'     => 'Регион (тарифы)',
            'add_new'            => 'Добавить регион',
            'add_new_item'       => 'Добавить регион',
            'new_item'           => 'Новый регион',
            'edit_item'          => 'Редактировать регион',
            'view_item'          => 'Посмотреть страницу региона',
            'all_items'          => 'Все регионы',
            'search_items'       => 'Найти регион',
            'parent_item_colon'  => 'Родительский регион:',
            'not_found'          => 'Регионы не найдены',
            'not_found_in_trash' => 'В корзине регионы не найдены',
        ];

        $args = [
            'labels'             => $labels,
            'description'        => 'Региональные страницы тарифов ЖКУ',
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => true, // Gutenberg + REST.
            'query_var'          => true,
            'rewrite'            => [
                'slug'       => MS_TARIFF_MAP_URL_PREFIX,
                'with_front' => false,
                'feeds'      => false,
            ],
            'capability_type'    => 'post',
            'has_archive'        => MS_TARIFF_MAP_URL_PREFIX, // URL архива = /tarify-zhku/
            'hierarchical'       => false,
            'menu_position'      => 25,
            'menu_icon'          => 'dashicons-location-alt',
            'supports'           => [ 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions' ],
            'taxonomies'         => [ self::TAXONOMY ],
        ];

        register_post_type( self::POST_TYPE, $args );
    }

    private static function register_taxonomy(): void {
        $labels = [
            'name'              => 'Федеральные округа',
            'singular_name'     => 'Федеральный округ',
            'search_items'      => 'Найти округ',
            'all_items'         => 'Все округа',
            'parent_item'       => 'Родительский округ',
            'parent_item_colon' => 'Родительский округ:',
            'edit_item'         => 'Редактировать округ',
            'update_item'       => 'Обновить округ',
            'add_new_item'      => 'Добавить округ',
            'new_item_name'     => 'Новый округ',
            'menu_name'         => 'Округа',
        ];

        register_taxonomy( self::TAXONOMY, [ self::POST_TYPE ], [
            'labels'            => $labels,
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'okrug', 'with_front' => false ],
        ] );

        // Предзаполнение терминов округа при первой инициализации.
        foreach ( self::districts() as $code => $name ) {
            if ( ! term_exists( $code, self::TAXONOMY ) ) {
                wp_insert_term( $name, self::TAXONOMY, [ 'slug' => strtolower( $code ) ] );
            }
        }
    }

    /**
     * Список всех федеральных округов: код → название.
     */
    public static function districts(): array {
        return [
            'ЦФО'  => 'Центральный',
            'СЗФО' => 'Северо-Западный',
            'ПФО'  => 'Приволжский',
            'УФО'  => 'Уральский',
            'СФО'  => 'Сибирский',
            'ДФО'  => 'Дальневосточный',
            'ЮФО'  => 'Южный',
            'СКФО' => 'Северо-Кавказский',
        ];
    }
}
