<?php
/**
 * Plugin Name:       MisterSaver Tariff Map
 * Plugin URI:        https://github.com/maxsharp72/mistersaver-tariff-map
 * Description:       Интерактивная карта тарифов ЖКУ по 89 регионам России. CPT region_tariff + шорткод [ms_tariff_map] + Яндекс Tiles API + OpenLayers.
 * Version:           0.2.19
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            MisterSaver
 * Author URI:        https://mistersaver.ru
 * License:           GPL v2 or later
 * Text Domain:       ms-tariff-map
 * Domain Path:       /languages
 *
 * @package MisterSaver\TariffMap
 */

defined( 'ABSPATH' ) || exit;

// Версия плагина.
define( 'MS_TARIFF_MAP_VERSION', '0.2.19' );
define( 'MS_TARIFF_MAP_FILE', __FILE__ );
define( 'MS_TARIFF_MAP_DIR', plugin_dir_path( __FILE__ ) );
define( 'MS_TARIFF_MAP_URL', plugin_dir_url( __FILE__ ) );
define( 'MS_TARIFF_MAP_BASENAME', plugin_basename( __FILE__ ) );

// Префикс URL для региональных страниц (можно менять в настройках плагина в будущем).
if ( ! defined( 'MS_TARIFF_MAP_URL_PREFIX' ) ) {
    define( 'MS_TARIFF_MAP_URL_PREFIX', 'tarify-zhku' );
}

/**
 * Подгружаем основные классы.
 */
require_once MS_TARIFF_MAP_DIR . 'includes/class-cpt.php';
require_once MS_TARIFF_MAP_DIR . 'includes/class-acf-fields.php';
require_once MS_TARIFF_MAP_DIR . 'includes/class-rest.php';
require_once MS_TARIFF_MAP_DIR . 'includes/class-shortcode.php';
require_once MS_TARIFF_MAP_DIR . 'includes/class-template-loader.php';
require_once MS_TARIFF_MAP_DIR . 'includes/class-schema.php';
require_once MS_TARIFF_MAP_DIR . 'includes/class-importer.php';
require_once MS_TARIFF_MAP_DIR . 'includes/class-llm-generator.php';
require_once MS_TARIFF_MAP_DIR . 'includes/class-settings.php';
require_once MS_TARIFF_MAP_DIR . 'includes/class-redirector.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once MS_TARIFF_MAP_DIR . 'includes/class-cli.php';
}

/**
 * Главный класс плагина — координатор.
 */
final class MS_Tariff_Map {

    /**
     * Singleton.
     */
    private static $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    /**
     * Выводит JS-трекер Яндекс.Метрики для партнёрских кнопок.
     * Работает только на страницах региона/архива, и только если в настройках указан ID счётчика.
     */
    public static function render_metrika_tracker(): void {
        if ( ! is_singular( MS_Tariff_Map_CPT::POST_TYPE ) && ! is_post_type_archive( MS_Tariff_Map_CPT::POST_TYPE ) ) {
            return;
        }
        $opts       = get_option( 'ms_tariff_map_options', [] );
        $counter_id = isset( $opts['yandex_metrika_id'] ) ? trim( (string) $opts['yandex_metrika_id'] ) : '';
        if ( ! preg_match( '/^\d+$/', $counter_id ) ) {
            return;
        }
        $counter_id = (int) $counter_id;
        ?>
<script id="ms-tariff-map-tracker-footer">
(function() {
  if (window.__msTariffTrackerInit) return;
  window.__msTariffTrackerInit = true;
  var COUNTER_ID = <?php echo esc_js( (string) $counter_id ); ?>;
  document.addEventListener('click', function(e) {
    var link = e.target.closest('a[data-offer]');
    if (!link || typeof ym !== 'function') return;
    var offer  = link.getAttribute('data-offer');
    var region = link.getAttribute('data-region');
    ym(COUNTER_ID, 'reachGoal', 'partner_click');
    if (offer === 'cashback-card') {
      ym(COUNTER_ID, 'reachGoal', 'partner_click_cashback');
    } else if (offer === 'tbank-zhku') {
      ym(COUNTER_ID, 'reachGoal', 'partner_click_tbank');
    }
    ym(COUNTER_ID, 'params', { partner_offer: offer, partner_region: region || 'archive' });
  }, { capture: true });
})();
</script>
        <?php
    }

    private function init(): void {
        // Регистрация CPT и таксономии — на init.
        add_action( 'init', [ MS_Tariff_Map_CPT::class, 'register' ], 5 );

        // ACF Local JSON — путь.
        add_filter( 'acf/settings/load_json', [ MS_Tariff_Map_ACF::class, 'add_load_path' ] );
        add_filter( 'acf/settings/save_json', [ MS_Tariff_Map_ACF::class, 'set_save_path' ] );

        // Программная регистрация полей если ACF доступен (fallback).
        add_action( 'acf/init', [ MS_Tariff_Map_ACF::class, 'register_fields' ] );

        // REST endpoints.
        add_action( 'rest_api_init', [ MS_Tariff_Map_REST::class, 'register_routes' ] );

        // Шорткод и enqueue.
        add_action( 'init', [ MS_Tariff_Map_Shortcode::class, 'register' ] );
        add_action( 'wp_enqueue_scripts', [ MS_Tariff_Map_Shortcode::class, 'maybe_enqueue' ] );

        // Шаблоны single / archive.
        add_filter( 'single_template', [ MS_Tariff_Map_Template_Loader::class, 'single_template' ] );
        add_filter( 'archive_template', [ MS_Tariff_Map_Template_Loader::class, 'archive_template' ] );

        // Партнёрский редиректор (/go/{slug}/) — обход AdBlock.
        MS_Tariff_Map_Redirector::register();

        // Schema.org JSON-LD.
        add_action( 'wp_head', [ MS_Tariff_Map_Schema::class, 'render' ], 5 );

        // Страницы настроек.
        add_action( 'admin_menu', [ MS_Tariff_Map_Settings::class, 'register_menu' ] );
        add_action( 'admin_init', [ MS_Tariff_Map_Settings::class, 'register_settings' ] );
    }

}

// Запуск.
add_action( 'plugins_loaded', [ 'MS_Tariff_Map', 'instance' ] );

/**
 * Активация: создаём CPT и сбрасываем правила перезаписи URL.
 */
register_activation_hook( __FILE__, function () {
    MS_Tariff_Map_CPT::register();
    flush_rewrite_rules();

    // Сохраняем дефолтные настройки.
    if ( false === get_option( 'ms_tariff_map_settings' ) ) {
        add_option( 'ms_tariff_map_settings', [
            'yandex_api_key'      => '',
            'openrouter_api_key'  => '',
            'openrouter_model'    => 'anthropic/claude-3.5-sonnet',
            'partner_payment_url' => '',
            'partner_cashback_url'=> '',
        ] );
    }
} );

/**
 * Деактивация: только сброс rewrite, данные сохраняем.
 */
register_deactivation_hook( __FILE__, function () {
    flush_rewrite_rules();
} );
