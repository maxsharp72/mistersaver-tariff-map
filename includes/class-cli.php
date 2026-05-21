<?php
/**
 * WP-CLI команды: wp ms-tariffs ...
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

class MS_Tariff_Map_CLI {

    /**
     * Статистика по регионам.
     *
     * ## EXAMPLES
     *
     *     wp ms-tariffs status
     */
    public function status(): void {
        $posts = get_posts( [
            'post_type'      => MS_Tariff_Map_CPT::POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => -1,
        ] );

        $total = count( $posts );
        $with_bill = 0; $with_llm = 0;
        foreach ( $posts as $p ) {
            if ( MS_Tariff_Map_ACF::get( $p->ID, 'avg_monthly_bill' ) !== null ) $with_bill++;
            if ( get_post_meta( $p->ID, '_ms_llm_generated_at', true ) ) $with_llm++;
        }

        WP_CLI::log( "Регионов в БД:                {$total}" );
        WP_CLI::log( "С данными по avg_bill:        {$with_bill}" );
        WP_CLI::log( "С LLM-сгенерированным текстом: {$with_llm}" );

        $settings = get_option( 'ms_tariff_map_settings', [] );
        WP_CLI::log( "Яндекс ключ:    " . ( ! empty( $settings['yandex_api_key'] ) ? '✓ настроен' : '✗ не задан' ) );
        WP_CLI::log( "OpenRouter:     " . ( ! empty( $settings['openrouter_api_key'] ) ? '✓ настроен' : '✗ не задан' ) );
    }

    /**
     * Импорт регионов из JSON.
     *
     * ## OPTIONS
     *
     * [<file>]
     * : Путь к JSON-файлу. По умолчанию — data/regions-tariffs.json из плагина.
     *
     * ## EXAMPLES
     *
     *     wp ms-tariffs import
     *     wp ms-tariffs import /var/data/tariffs.json
     */
    public function import( $args ): void {
        $file = $args[0] ?? null;
        $result = $file
            ? MS_Tariff_Map_Importer::import_file( $file )
            : MS_Tariff_Map_Importer::import_default();

        if ( ! $result['success'] ) {
            WP_CLI::error( $result['message'] );
        }

        WP_CLI::success( sprintf(
            'Импорт завершён. Создано: %d, обновлено: %d, пропущено: %d, ошибок: %d',
            $result['created'], $result['updated'], $result['skipped'], count( $result['errors'] )
        ) );

        foreach ( $result['errors'] as $err ) {
            WP_CLI::warning( $err );
        }
    }

    /**
     * Генерация контента через LLM (OpenRouter).
     *
     * ## OPTIONS
     *
     * [--force]
     * : Перегенерировать даже регионы где уже есть LLM-текст.
     *
     * [--slug=<slug>]
     * : Сгенерировать только для одного региона.
     *
     * [--limit=<n>]
     * : Ограничить количество (для тестов).
     *
     * ## EXAMPLES
     *
     *     wp ms-tariffs generate-content
     *     wp ms-tariffs generate-content --slug=moskva --force
     *     wp ms-tariffs generate-content --limit=5
     */
    public function generate_content( $args, $assoc ): void {
        $force = isset( $assoc['force'] );
        $slug  = $assoc['slug'] ?? null;
        $limit = (int) ( $assoc['limit'] ?? 0 );

        if ( $slug ) {
            $post = get_page_by_path( $slug, OBJECT, MS_Tariff_Map_CPT::POST_TYPE );
            if ( ! $post ) {
                WP_CLI::error( "Регион $slug не найден" );
            }
            WP_CLI::log( "Генерируем для $slug..." );
            $result = MS_Tariff_Map_LLM_Generator::generate_for_post( $post->ID, $force );
            $result['success'] ? WP_CLI::success( $result['message'] ) : WP_CLI::error( $result['message'] );
            return;
        }

        WP_CLI::log( 'Запускаем массовую генерацию...' );
        $stats = MS_Tariff_Map_LLM_Generator::generate_for_all( $force, $limit );
        WP_CLI::success( sprintf(
            'Готово. Успешно: %d, пропущено: %d, ошибок: %d',
            $stats['success'], $stats['skipped'], count( $stats['errors'] )
        ) );
        foreach ( $stats['errors'] as $err ) WP_CLI::warning( $err );
    }

    /**
     * Сбросить кеш REST + rewrite.
     */
    public function flush_cache(): void {
        MS_Tariff_Map_REST::invalidate_cache();
        flush_rewrite_rules();
        WP_CLI::success( 'Кеш сброшен, rewrite правила обновлены.' );
    }
}

WP_CLI::add_command( 'ms-tariffs', MS_Tariff_Map_CLI::class );
