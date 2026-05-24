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

        // Обработчики admin-action для кнопок «Импорт» / «ЛЛМ» / «Очистить».
        add_action( 'admin_post_ms_tariffs_import',    [ self::class, 'handle_import' ] );
        add_action( 'admin_post_ms_tariffs_llm',       [ self::class, 'handle_generate' ] );
        add_action( 'admin_post_ms_tariffs_flush',     [ self::class, 'handle_flush' ] );
        add_action( 'admin_post_ms_tariffs_uninstall', [ self::class, 'handle_uninstall' ] );
        add_action( 'admin_post_ms_tariffs_reset_clicks', [ self::class, 'handle_reset_clicks' ] );

        add_settings_section( 'ms_api_section', 'API ключи', null, self::PAGE_SLUG );

        self::field( 'yandex_api_key',     'Яндекс Tiles API ключ',     'Бесплатный ключ из <a href="https://developer.tech.yandex.ru/" target="_blank">кабинета разработчика Яндекс Карт</a>', 'ms_api_section' );
        self::field( 'openrouter_api_key', 'OpenRouter API ключ',       'Для LLM-генерации текстов регионов. Получить на <a href="https://openrouter.ai/keys" target="_blank">openrouter.ai/keys</a>',   'ms_api_section' );
        self::field( 'openrouter_model',   'OpenRouter модель',         'Например: anthropic/claude-3.5-sonnet, openai/gpt-4o-mini', 'ms_api_section' );

        add_settings_section( 'ms_partner_section', 'Партнёрские ссылки', null, self::PAGE_SLUG );
        self::field( 'partner_payment_url',  'URL партнёра «Оплата ЖКУ»', '', 'ms_partner_section' );
        self::field( 'partner_cashback_url', 'URL партнёра «Кэшбэк»',     '', 'ms_partner_section' );

        add_settings_section( 'ms_analytics_section', 'Аналитика', null, self::PAGE_SLUG );
        self::field( 'yandex_metrika_id', 'ID счётчика Яндекс.Метрики', 'При указании — плагин будет автоматически отправлять цели <code>partner_click</code>, <code>partner_click_cashback</code>, <code>partner_click_tbank</code> при кликах по партнёрским кнопкам. Например: 17963905. <a href="https://github.com/maxsharp72/mistersaver-tariff-map/blob/main/docs/yandex-metrika-cpa-setup.md" target="_blank">Подробная инструкция</a>', 'ms_analytics_section' );
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

    /**
     * Блок «Статус» вверху секции действий.
     */
    private static function render_status_box(): void {
        $count = wp_count_posts( MS_Tariff_Map_CPT::POST_TYPE );
        $published = (int) ( $count->publish ?? 0 );
        $with_llm = (int) get_posts( [
            'post_type'      => MS_Tariff_Map_CPT::POST_TYPE,
            'meta_key'       => '_ms_llm_generated_at',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ] );
        $with_llm_count = is_array( $with_llm ) ? count( $with_llm ) : $with_llm;
        ?>
        <div style="display:flex; gap:12px; margin-bottom:8px;">
            <div style="background:#fff; border:1px solid #c3c4c7; border-radius:8px; padding:14px 18px; min-width:160px;">
                <div style="font-size:12px; color:#646970; text-transform:uppercase;">Регионов в БД</div>
                <div style="font-size:28px; font-weight:700; color:<?php echo $published > 0 ? '#16A34A' : '#DC2626'; ?>"><?php echo (int) $published; ?> / 89</div>
            </div>
            <div style="background:#fff; border:1px solid #c3c4c7; border-radius:8px; padding:14px 18px; min-width:160px;">
                <div style="font-size:12px; color:#646970; text-transform:uppercase;">С LLM-текстом</div>
                <div style="font-size:28px; font-weight:700; color:#046BD2"><?php echo (int) $with_llm_count; ?></div>
            </div>
            <div style="background:#fff; border:1px solid #c3c4c7; border-radius:8px; padding:14px 18px; min-width:200px;">
                <div style="font-size:12px; color:#646970; text-transform:uppercase;">Карта</div>
                <?php if ( $published > 0 ) : ?>
                    <a href="<?php echo esc_url( get_post_type_archive_link( MS_Tariff_Map_CPT::POST_TYPE ) ); ?>" target="_blank" style="font-size:14px; font-weight:600;">Открыть /tarify-zhku/ ↗</a>
                <?php else : ?>
                    <div style="color:#646970;">Сначала импортируйте данные</div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Admin actions.
     */
    public static function handle_import(): void {
        check_admin_referer( 'ms_tariffs_import' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
        $result = MS_Tariff_Map_Importer::import_default();
        if ( ! $result['success'] ) {
            self::redirect_with_message( 'error', $result['message'] );
        }
        $msg = sprintf(
            'Импорт завершён. Создано: %d, обновлено: %d, пропущено: %d, ошибок: %d',
            $result['created'], $result['updated'], $result['skipped'], count( $result['errors'] )
        );
        self::redirect_with_message( 'success', $msg );
    }

    public static function handle_generate(): void {
        check_admin_referer( 'ms_tariffs_llm' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
        // Ограничим лимитом 10 регионов за раз — иначе PHP вывалится по таймауту на reg.ru.
        @set_time_limit( 120 );
        $stats = MS_Tariff_Map_LLM_Generator::generate_for_all( false, 10 );
        $msg = sprintf(
            'LLM-генерация за 1 батч (10 регионов): успешно %d, пропущено %d, ошибок %d. Нажмите ещё раз для продолжения.',
            $stats['success'], $stats['skipped'], count( $stats['errors'] )
        );
        if ( ! empty( $stats['errors'] ) ) {
            $msg .= ' Ошибки: ' . esc_html( implode( '; ', array_slice( $stats['errors'], 0, 3 ) ) );
        }
        self::redirect_with_message( 'success', $msg );
    }

    public static function handle_flush(): void {
        check_admin_referer( 'ms_tariffs_flush' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
        MS_Tariff_Map_REST::invalidate_cache();
        flush_rewrite_rules();
        self::redirect_with_message( 'success', 'Кеш сброшен, rewrite-правила обновлены.' );
    }

    public static function handle_uninstall(): void {
        check_admin_referer( 'ms_tariffs_uninstall' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
        $posts = get_posts( [ 'post_type' => MS_Tariff_Map_CPT::POST_TYPE, 'posts_per_page' => -1, 'fields' => 'ids', 'post_status' => 'any' ] );
        foreach ( $posts as $pid ) wp_delete_post( $pid, true );
        MS_Tariff_Map_REST::invalidate_cache();
        self::redirect_with_message( 'success', sprintf( 'Удалено %d записей.', count( $posts ) ) );
    }

    public static function handle_reset_clicks(): void {
        check_admin_referer( 'ms_tariffs_reset_clicks' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
        MS_Tariff_Map_Redirector::reset_log();
        self::redirect_with_message( 'success', 'Лог партнёрских кликов очищен.' );
    }

    /**
     * Блок статистики партнёрских кликов.
     */
    private static function render_clicks_section(): void {
        $stats = MS_Tariff_Map_Redirector::get_stats();
        $log   = MS_Tariff_Map_Redirector::get_log();
        $total = array_sum( $stats );

        // Сводка по предложениям
        $by_offer = [];
        foreach ( $stats as $key => $cnt ) {
            [ $slug, ] = array_pad( explode( '|', $key, 2 ), 2, '' );
            $by_offer[ $slug ] = ( $by_offer[ $slug ] ?? 0 ) + (int) $cnt;
        }

        // Топ-10 регионов
        $by_region = [];
        foreach ( $stats as $key => $cnt ) {
            $parts = explode( '|', $key, 2 );
            $region = $parts[1] ?? '';
            if ( $region && $region !== '__all__' ) {
                $by_region[ $region ] = ( $by_region[ $region ] ?? 0 ) + (int) $cnt;
            }
        }
        arsort( $by_region );
        $top_regions = array_slice( $by_region, 0, 10, true );
        ?>
        <hr>
        <h2>📊 Партнёрские клики</h2>

        <?php if ( $total === 0 ) : ?>
            <p style="color:#646970;">Пока кликов нет. Ссылки логируются при первом переходе через <code>/go/{slug}/</code>.</p>
        <?php else : ?>
            <div style="display:flex; gap:12px; margin-bottom:14px;">
                <div style="background:#fff; border:1px solid #c3c4c7; border-radius:8px; padding:14px 18px; min-width:160px;">
                    <div style="font-size:12px; color:#646970; text-transform:uppercase;">Всего кликов</div>
                    <div style="font-size:28px; font-weight:700; color:#046BD2"><?php echo (int) $total; ?></div>
                </div>
                <?php foreach ( $by_offer as $offer_slug => $cnt ) :
                    $label = $offer_slug === 'cashback' ? 'Кешбэк-карта' : ( $offer_slug === 'tbank' ? 'Т-Банк / Оплата ЖКУ' : $offer_slug );
                ?>
                    <div style="background:#fff; border:1px solid #c3c4c7; border-radius:8px; padding:14px 18px; min-width:160px;">
                        <div style="font-size:12px; color:#646970; text-transform:uppercase;"><?php echo esc_html( $label ); ?></div>
                        <div style="font-size:28px; font-weight:700; color:#16A34A"><?php echo (int) $cnt; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ( ! empty( $top_regions ) ) : ?>
                <h3>Топ-10 регионов по кликам</h3>
                <table class="widefat striped" style="max-width:520px;">
                    <thead><tr><th>Регион (slug)</th><th style="text-align:right;">Кликов</th></tr></thead>
                    <tbody>
                        <?php foreach ( $top_regions as $region => $cnt ) : ?>
                            <tr><td><code><?php echo esc_html( $region ); ?></code></td><td style="text-align:right;"><?php echo (int) $cnt; ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <h3 style="margin-top:18px;">Последние 20 кликов</h3>
            <table class="widefat striped">
                <thead><tr><th>Дата</th><th>Предложение</th><th>Регион</th><th>Страница</th><th>IP</th></tr></thead>
                <tbody>
                <?php
                $recent = array_slice( array_reverse( $log ), 0, 20 );
                foreach ( $recent as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( $row['ts'] ?? '' ); ?></td>
                        <td><?php echo esc_html( $row['slug'] ?? '' ); ?></td>
                        <td><code><?php echo esc_html( $row['region'] ?? '' ); ?></code></td>
                        <td style="font-size:11px;"><?php echo esc_html( wp_parse_url( $row['referer'] ?? '', PHP_URL_PATH ) ?? '' ); ?></td>
                        <td style="font-size:11px;"><?php echo esc_html( $row['ip'] ?? '' ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px;" onsubmit="return confirm('Очистить всю статистику и лог?');">
                <?php wp_nonce_field( 'ms_tariffs_reset_clicks' ); ?>
                <input type="hidden" name="action" value="ms_tariffs_reset_clicks">
                <button class="button" type="submit">Очистить лог кликов</button>
            </form>
        <?php endif; ?>
        <?php
    }

    private static function redirect_with_message( string $type, string $msg ): void {
        $url = add_query_arg( [
            'page'   => self::PAGE_SLUG,
            'ms_msg' => urlencode( $msg ),
            'ms_t'   => $type,
        ], admin_url( 'edit.php?post_type=' . MS_Tariff_Map_CPT::POST_TYPE ) );
        wp_safe_redirect( $url );
        exit;
    }

    public static function render_page(): void {
        // Сообщения после admin_post.
        if ( ! empty( $_GET['ms_msg'] ) ) {
            $type = sanitize_key( $_GET['ms_t'] ?? 'success' );
            $msg = sanitize_text_field( wp_unslash( $_GET['ms_msg'] ) );
            $class = $type === 'error' ? 'notice-error' : 'notice-success';
            echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
        }
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
            <h2>📥 Действия с данными</h2>

            <?php self::render_status_box(); ?>

            <div style="display:flex; flex-wrap:wrap; gap:12px; margin-top:16px;">

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0;">
                    <?php wp_nonce_field( 'ms_tariffs_import' ); ?>
                    <input type="hidden" name="action" value="ms_tariffs_import">
                    <button class="button button-primary button-large" type="submit">⬇ Импортировать 89 регионов</button>
                    <p class="description" style="margin:6px 0 0;">Создаёт/обновляет записи из data/regions-tariffs.json</p>
                </form>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0;">
                    <?php wp_nonce_field( 'ms_tariffs_llm' ); ?>
                    <input type="hidden" name="action" value="ms_tariffs_llm">
                    <button class="button button-large" type="submit" <?php echo empty( get_option( self::OPTION_NAME )['openrouter_api_key'] ?? '' ) ? 'disabled title="Сначала введите OpenRouter API ключ"' : ''; ?>>✍ Сгенерировать тексты (LLM)</button>
                    <p class="description" style="margin:6px 0 0;">Объявляет OpenRouter для всех регионов без LLM-контента</p>
                </form>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0;">
                    <?php wp_nonce_field( 'ms_tariffs_flush' ); ?>
                    <input type="hidden" name="action" value="ms_tariffs_flush">
                    <button class="button button-large" type="submit">⟳ Сбросить кеш</button>
                    <p class="description" style="margin:6px 0 0;">Инвалидирует REST-кеш + flush rewrite</p>
                </form>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0; margin-left:auto;" onsubmit="return confirm('Уверены? Будут удалены ВСЕ 89 записей регионов. Данные можно будет вернуть через повторный импорт.');">
                    <?php wp_nonce_field( 'ms_tariffs_uninstall' ); ?>
                    <input type="hidden" name="action" value="ms_tariffs_uninstall">
                    <button class="button button-link-delete" type="submit">🗑 Удалить все записи</button>
                </form>

            </div>

            <hr>
            <h2>⚡ WP-CLI команды (альтернатива)</h2>
            <p>Если у вас есть SSH и WP-CLI:</p>
            <pre style="background:#f3f4f6;padding:14px;border-radius:6px;">
wp ms-tariffs status                  # статистика по регионам
wp ms-tariffs import                  # импорт из data/regions-tariffs.json
wp ms-tariffs import /path/to/file.json
wp ms-tariffs generate-content        # LLM для всех без контента
wp ms-tariffs generate-content --force --slug=moskva
wp ms-tariffs flush-cache             # сбросить кеш REST
            </pre>

            <?php self::render_clicks_section(); ?>

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
