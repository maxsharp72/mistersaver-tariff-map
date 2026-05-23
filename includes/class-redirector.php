<?php
/**
 * Партнёрский редиректор + клик-лог.
 *
 * 1) Ссылки идут на /go/{slug}/?ref={region-slug} — невидимы для AdBlock.
 * 2) Сервер логирует клик и делает 302 на партнёрку с автоматической подстановкой
 *    sub_id={region-slug} для трекинга по регионам в LeadGid.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

class MS_Tariff_Map_Redirector {

    const LOG_OPTION  = 'ms_tariff_partner_clicks';
    const STATS_OPTION = 'ms_tariff_partner_stats';
    const MAX_LOG_SIZE = 500; // последние N кликов

    /**
     * Карта slug → URL партнёрки. Параметр sub_id будет дописан автоматически.
     */
    public static function targets(): array {
        return [
            'cashback' => 'https://go.leadgid.ru/aff_c?aff_id=139564&offer_id=7249',
            'tbank'    => 'https://go.leadgid.ru/aff_c?aff_id=139564&offer_id=6432&p=10249&erid=LjN8KDq59',
        ];
    }

    public static function register(): void {
        add_action( 'init', [ __CLASS__, 'add_rewrite' ] );
        add_filter( 'query_vars', [ __CLASS__, 'add_query_var' ] );
        add_action( 'template_redirect', [ __CLASS__, 'maybe_redirect' ] );
    }

    public static function add_rewrite(): void {
        add_rewrite_rule(
            '^go/([a-z0-9_-]+)/?$',
            'index.php?ms_go=$matches[1]',
            'top'
        );
    }

    public static function add_query_var( array $vars ): array {
        $vars[] = 'ms_go';
        return $vars;
    }

    public static function maybe_redirect(): void {
        $slug = get_query_var( 'ms_go' );
        if ( empty( $slug ) ) {
            return;
        }
        $targets = self::targets();
        if ( ! isset( $targets[ $slug ] ) ) {
            wp_safe_redirect( home_url( '/' ), 302 );
            exit;
        }

        // region-slug из ?ref=
        $region_ref = isset( $_GET['ref'] ) ? sanitize_title( wp_unslash( $_GET['ref'] ) ) : '';

        $target_url = $targets[ $slug ];
        // Дописываем sub_id для региона — LeadGid передаст это партнёру для трекинга.
        if ( $region_ref ) {
            $sep = ( strpos( $target_url, '?' ) === false ) ? '?' : '&';
            $target_url .= $sep . 'sub_id=' . rawurlencode( $region_ref );
        }

        self::log_click( $slug, $region_ref );

        nocache_headers();
        wp_redirect( $target_url, 302, 'MisterSaver Tariff Map' );
        exit;
    }

    /**
     * Локальный URL для slug. $region_slug — опциональный slug региона (попадает в ?ref=).
     */
    public static function local_url( string $slug, string $region_slug = '' ): string {
        $url = home_url( '/go/' . $slug . '/' );
        if ( $region_slug ) {
            $url = add_query_arg( 'ref', $region_slug, $url );
        }
        return $url;
    }

    /**
     * Лог клика — последние 500 кликов в wp_options + агрегированные счётчики.
     */
    private static function log_click( string $slug, string $region_ref ): void {
        // Агрегированные счётчики (slug + region).
        $stats = get_option( self::STATS_OPTION, [] );
        $key   = $slug . '|' . ( $region_ref ?: '__all__' );
        $stats[ $key ] = ( $stats[ $key ] ?? 0 ) + 1;
        update_option( self::STATS_OPTION, $stats, false );

        // Подробный лог (последние N).
        $log = get_option( self::LOG_OPTION, [] );
        $log[] = [
            'ts'      => current_time( 'mysql' ),
            'slug'    => $slug,
            'region'  => $region_ref,
            'referer' => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
            'ip'      => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
            'ua'      => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( substr( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ), 0, 200 ) ) : '',
        ];
        if ( count( $log ) > self::MAX_LOG_SIZE ) {
            $log = array_slice( $log, -self::MAX_LOG_SIZE );
        }
        update_option( self::LOG_OPTION, $log, false );
    }

    /**
     * Публичные геттеры для админ-страницы статистики.
     */
    public static function get_log(): array {
        return get_option( self::LOG_OPTION, [] );
    }

    public static function get_stats(): array {
        return get_option( self::STATS_OPTION, [] );
    }

    public static function reset_log(): void {
        delete_option( self::LOG_OPTION );
        delete_option( self::STATS_OPTION );
    }
}
