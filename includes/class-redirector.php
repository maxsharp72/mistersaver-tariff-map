<?php
/**
 * Партнёрский редиректор. Делает партнёрские ссылки невидимыми для AdBlock —
 * посетитель кликает на mistersaver.ru/go/cashback/, плагин отдаёт 302 на go.leadgid.ru/...
 *
 * Конфигурация — массив slug => target URL.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

class MS_Tariff_Map_Redirector {

    /**
     * Карта slug → URL партнёрки.
     */
    public static function targets(): array {
        return [
            'cashback' => 'https://go.leadgid.ru/aff_c?aff_id=139564&offer_id=7249',
            'tbank'    => 'https://go.leadgid.ru/aff_c?aff_id=139564&offer_id=6432&p=10249&erid=LjN8KDq59',
        ];
    }

    /**
     * Регистрирует rewrite-правило /go/{slug}/ → query var ms_go=slug.
     */
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
        // Можно тут логировать клик в options или БД — пока пропускаем.
        // Используем wp_redirect, а не wp_safe_redirect, потому что цель внешняя.
        nocache_headers();
        wp_redirect( $targets[ $slug ], 302, 'MisterSaver Tariff Map' );
        exit;
    }

    /**
     * Локальный URL для slug.
     */
    public static function local_url( string $slug ): string {
        return home_url( '/go/' . $slug . '/' );
    }
}
