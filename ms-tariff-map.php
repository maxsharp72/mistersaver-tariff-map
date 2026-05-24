<?php
/**
 * Plugin Name:       MisterSaver Tariff Map
 * Plugin URI:        https://github.com/maxsharp72/mistersaver-tariff-map
 * Description:       Интерактивная карта тарифов ЖКУ по 89 регионам России. CPT region_tariff + шорткод [ms_tariff_map] + Яндекс Tiles API + OpenLayers.
 * Version:           0.2.23
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
define( 'MS_TARIFF_MAP_VERSION', '0.2.23' );
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
        $opts       = get_option( 'ms_tariff_map_settings', [] );
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

    /**
     * На странице /contacts/ — читает ?subject= и ?ref= из URL,
     * вставляет в поле «Тема» любой формы (Gutena/CF7/WPForms/др.).
     */
    public static function render_contacts_prefill(): void {
        $path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '';
        if ( ! $path || strpos( (string) $path, '/contacts' ) === false ) {
            return;
        }
        ?>
<script id="ms-contacts-prefill">
(function() {
  var params = new URLSearchParams(window.location.search);
  var subject = params.get('subject');
  var ref = params.get('ref');
  if (!subject) return;

  function fillSubjectField() {
    var selectors = [
      'input[name="subject"]', 'input[name="Subject"]',
      'input[name="your-subject"]',
      'input[name*="theme" i]', 'input[name*="tema" i]',
      'input[placeholder*="Тема" i]',
      'input#subject', 'input#tema',
      'select[name="subject"]', 'select[name="Subject"]'
    ];
    var field = null;
    for (var i = 0; i < selectors.length; i++) {
      field = document.querySelector(selectors[i]);
      if (field) break;
    }
    if (!field) return false;

    if (field.tagName === 'SELECT') {
      var opt = document.createElement('option');
      opt.value = subject;
      opt.textContent = subject;
      opt.selected = true;
      field.appendChild(opt);
    } else {
      field.value = subject;
      field.dispatchEvent(new Event('input', { bubbles: true }));
      field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Поле «Сообщение» не заполняем — пусть пользователь опишет сам. Тема уже подскажет контекст.
    // Параметр ref остаётся в URL — его видно в referer'е, при желании можно логировать на сервере.
    return true;
  }

  if (!fillSubjectField()) {
    var tries = 0;
    var iv = setInterval(function() {
      tries++;
      if (fillSubjectField() || tries > 10) clearInterval(iv);
    }, 300);
  }
})();
</script>
        <?php
    }

    /**
     * Глобальный CSS: переносит reCAPTCHA-бейдж в левый нижний угол и уменьшает обратную прозрачность.
     * Бейдж остаётся видимым (требование Google), просто не перекрывает кнопку «вверх».
     */
    public static function render_recaptcha_relocator(): void {
        ?>
<style id="ms-recaptcha-relocator">
/* reCAPTCHA-бейдж — в левом нижнем углу, свёрнут до значка.
   При наведении — разворачивается на полную ширину (сохраняется видимость согласно TOS Google). */
.grecaptcha-badge {
  left: 4px !important;
  right: auto !important;
  bottom: 14px !important;
  box-shadow: 0 0 5px rgba(0,0,0,.15) !important;
  transition: width .25s ease, opacity .2s ease, transform .2s ease !important;
  opacity: .85;
  z-index: 998 !important;
  width: 70px !important;
  overflow: hidden !important;
}
.grecaptcha-badge:hover {
  width: 256px !important;
  opacity: 1;
}
@media (max-width: 768px) {
  .grecaptcha-badge {
    transform: scale(.85);
    transform-origin: bottom left;
  }
}
</style>
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

        // Автозаполнение формы на /contacts/ из GET-параметров.
        add_action( 'wp_footer', [ 'MS_Tariff_Map', 'render_contacts_prefill' ], 99 );

        // Перенос reCAPTCHA-бейджа в левый нижний угол глобально (не только на наших страницах).
        add_action( 'wp_head', [ 'MS_Tariff_Map', 'render_recaptcha_relocator' ], 100 );

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
