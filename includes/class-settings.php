<?php
/**
 * Страница настроек плагина: API ключи, партнёрские ссылки.
 *
 * Settings → MisterSaver Tariff Map
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

class MS_Tariff_Map_Settings {

    private const OPTION_NAME = 'ms_tariff_map_settings';
    private const PAGE_SLUG   = 'ms-tariff-map-settings';

    public static function register_menu(): void {
        add_submenu_page(
            'edit.php?post_type=' . MS_Tariff_Map_CPT::POST_TYPE,
            'Настройки карты тарифов',
            'Настройки',
            'manage_options',
            self::PAGE_SLUG,
            [ self::class, 'render_page' ]
        );
    }

    public static function register_settings(): void {
        register_setting( 'ms_tariff_map', self::OPTION_NAME, [
            'sanitize_callback' => [ self::class, 'sanitize' ],
            'default'           => [],
        ] );

        add_settings_section( 'ms_api_section', 'API ключи', null, self::PAGE_SLUG );

        self::field( 'yandex_api_key',     'Яндекс Tiles API ключ',     'Бесплатный ключ из <a href="https://developer.tech.yandex.ru/" target="_blank">кабинета разработчика Яндекс Карт</a>', 'ms_api_section' );
        self::field( 'openrouter_api_key', 'OpenRouter API ключ',       'Для LLM-генерации текстов регионов. Получить на <a href="https://openrouter.ai/keys" target="_blank">openrouter.ai/keys</a>',   'ms_api_section' );
        self::field( 'openrouter_model',   'OpenRouter модель',         'Например: anthropic/claude-3.5-sonnet, openai/gpt-4o-mini', 'ms_api_section' );

        add_settings_section( 'ms_partner_section', 'Партнёрские ссылки', null, self::PAGE_SLUG );
        self::field( 'partner_payment_url',  'URL партнёра «Оплата ЖКУ»', '', 'ms_partner_section' );
        self::field( 'partner_cashback_url', 'URL партнёра «Кэшбэк»',     '', 'ms_partner_section' );
    }

    private static function field( string $key, string $label, string $desc, string $section ): void {
        add_settings_field( $key, $label, function () use ( $key, $desc ) {
            $opts = get_option( self::OPTION_NAME, [] );
            $value = $opts[ $key ] ?? '';
            printf(
                '<input type="text" name="%s[%s]" value="%s" class="regular-text"><p class="description">%s</p>',
                esc_attr( self::OPTION_NAME ),
                esc_attr( $key ),
                esc_attr( $value ),
                wp_kses_post( $desc )
            );
        }, self::PAGE_SLUG, $section );
    }

    public static function sanitize( $input ): array {
        $clean = [];
        foreach ( (array) $input as $k => $v ) {
            $clean[ $k ] = sanitize_text_field( $v );
        }
        return $clean;
    }

    public static function render_page(): void {
        ?>
        <div class="wrap">
            <h1>Настройки MisterSaver Tariff Map</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'ms_tariff_map' );
                do_settings_sections( self::PAGE_SLUG );
                submit_button( 'Сохранить настройки' );
                ?>
            </form>

            <hr>
            <h2>WP-CLI команды</h2>
            <p>Управление данными удобнее всего через WP-CLI:</p>
            <pre style="background:#f3f4f6;padding:14px;border-radius:6px;">
wp ms-tariffs status                  # статистика по регионам
wp ms-tariffs import                  # импорт из data/regions-tariffs.json
wp ms-tariffs import /path/to/file.json
wp ms-tariffs generate-content        # LLM для всех без контента
wp ms-tariffs generate-content --force --slug=moskva
wp ms-tariffs flush-cache             # сбросить кеш REST
            </pre>

            <h2>Шорткоды</h2>
            <pre style="background:#f3f4f6;padding:14px;border-radius:6px;">
[ms_tariff_map]                           — полная карта (для архива)
[ms_tariff_map mode="mini" region="moskva"] — мини-карта 1 региона
[ms_tariff_map layer="electricity"]       — карта со слоем «электричество»
            </pre>
        </div>
        <?php
    }
}
