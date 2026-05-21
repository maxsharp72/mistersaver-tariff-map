<?php
/**
 * LLM-генерация уникального контента для региональных страниц через OpenRouter API.
 *
 * Использует промпт «Метод скользкой горки» (адаптированный из ТЗ MisterSaver):
 *   - Цепляющее вступление с конкретной цифрой
 *   - Сравнение с другими регионами
 *   - Информация о регуляторе и индексации
 *   - Советы по экономии (релевантные региону)
 *   - FAQ-блок (выводится в Schema.org)
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

class MS_Tariff_Map_LLM_Generator {

    private const OPENROUTER_ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';
    private const DEFAULT_MODEL       = 'anthropic/claude-3.5-sonnet';

    /**
     * Генерирует контент для одного региона и сохраняет в post_content.
     *
     * @param int  $post_id ID поста CPT region_tariff.
     * @param bool $force   Перегенерировать даже если контент уже есть.
     * @return array{success: bool, message: string, tokens?: int}
     */
    public static function generate_for_post( int $post_id, bool $force = false ): array {
        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== MS_Tariff_Map_CPT::POST_TYPE ) {
            return [ 'success' => false, 'message' => 'Пост не найден' ];
        }

        // Защита от двойной генерации.
        if ( ! $force && self::has_real_content( $post ) ) {
            return [ 'success' => false, 'message' => 'Контент уже сгенерирован (используйте --force)' ];
        }

        $settings = get_option( 'ms_tariff_map_settings', [] );
        $api_key  = trim( $settings['openrouter_api_key'] ?? '' );
        if ( empty( $api_key ) ) {
            return [ 'success' => false, 'message' => 'OpenRouter API ключ не задан в настройках плагина' ];
        }

        $model = $settings['openrouter_model'] ?? self::DEFAULT_MODEL;

        // Соберём данные региона.
        $data = MS_Tariff_Map_REST::post_to_properties( $post );

        $prompt = self::build_prompt( $data );

        // Запрос к OpenRouter.
        $response = wp_remote_post( self::OPENROUTER_ENDPOINT, [
            'timeout' => 60,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
                'HTTP-Referer'  => home_url(),
                'X-Title'       => 'MisterSaver Tariff Map',
            ],
            'body'    => wp_json_encode( [
                'model'       => $model,
                'messages'    => [
                    [ 'role' => 'system', 'content' => self::system_prompt() ],
                    [ 'role' => 'user',   'content' => $prompt ],
                ],
                'temperature' => 0.7,
                'max_tokens'  => 2000,
            ] ),
        ] );

        if ( is_wp_error( $response ) ) {
            return [ 'success' => false, 'message' => 'HTTP ошибка: ' . $response->get_error_message() ];
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['choices'][0]['message']['content'] ) ) {
            $error = $body['error']['message'] ?? 'неизвестная ошибка';
            return [ 'success' => false, 'message' => 'Ответ OpenRouter некорректен: ' . $error ];
        }

        $content = trim( $body['choices'][0]['message']['content'] );
        $content = self::sanitize_html( $content );

        // Обновляем пост.
        wp_update_post( [
            'ID'           => $post_id,
            'post_content' => $content,
        ] );

        // Метка времени и модель.
        update_post_meta( $post_id, '_ms_llm_generated_at', current_time( 'mysql' ) );
        update_post_meta( $post_id, '_ms_llm_model', $model );

        return [
            'success' => true,
            'message' => 'Контент сгенерирован',
            'tokens'  => (int) ( $body['usage']['total_tokens'] ?? 0 ),
        ];
    }

    /**
     * Массовая генерация для всех или для регионов без LLM-контента.
     */
    public static function generate_for_all( bool $force = false, int $limit = 0 ): array {
        $args = [
            'post_type'      => MS_Tariff_Map_CPT::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => $limit ?: -1,
            'orderby'        => 'date',
            'order'          => 'ASC',
        ];
        $posts = get_posts( $args );

        $stats = [ 'success' => 0, 'skipped' => 0, 'errors' => [] ];
        foreach ( $posts as $post ) {
            $result = self::generate_for_post( $post->ID, $force );
            if ( $result['success'] ) {
                $stats['success']++;
            } elseif ( strpos( $result['message'], 'уже сгенерирован' ) !== false ) {
                $stats['skipped']++;
            } else {
                $stats['errors'][] = $post->post_name . ': ' . $result['message'];
            }
            // Пауза между запросами, чтобы не упереться в rate limit.
            usleep( 500000 ); // 0.5 сек
        }

        return $stats;
    }

    private static function has_real_content( WP_Post $post ): bool {
        $content = trim( $post->post_content );
        // Если контент содержит маркер автогенерации шаблоном — считаем что LLM ещё не работал.
        if ( strpos( $content, '<!-- ms-tariff-table -->' ) !== false ) {
            return false;
        }
        // Слишком короткий контент — тоже считаем "пустым".
        return strlen( strip_tags( $content ) ) > 500;
    }

    private static function system_prompt(): string {
        return <<<PROMPT
Ты — SEO-копирайтер сайта MisterSaver.ru, который пишет уникальные статьи о тарифах ЖКХ по российским регионам.

Стиль письма (метод «Скользкой горки» Шугермана):
- Цепляющее вступление с конкретной цифрой или фактом.
- Каждое предложение мотивирует читать следующее.
- Дружелюбный, но экспертный тон.
- Конкретные числа, никакой воды.

Структура текста (HTML, валидный, без <html>/<body>):
1. <p> Вступление с фактом о регионе и платеже (2-3 предложения).
2. <h2>Тарифы ЖКУ в [регион] на 2026 год</h2>
3. <p> Краткое описание тарифов с числами из данных.
4. <h2>Сравнение с другими регионами</h2>
5. <p> На сколько процентов дороже/дешевле среднего по РФ (5 500 ₽).
6. <h2>Кто устанавливает тарифы</h2>
7. <p> Регулятор + ссылка на сайт + дата актуализации.
8. <h2>Как сэкономить на ЖКУ в [регион]</h2>
9. <ul> 3-5 конкретных советов с учётом климата региона (для северных — утепление, для южных — кондиционирование).

Объём: 400-600 слов. НЕ выдумывай числа — используй только те, что переданы в данных. Если какого-то тарифа нет в данных, не упоминай его.
PROMPT;
    }

    private static function build_prompt( array $data ): string {
        $lines = [];
        $lines[] = 'Сгенерируй уникальный SEO-текст для страницы региона со следующими данными:';
        $lines[] = '';
        $lines[] = 'Регион: ' . $data['name'];
        $lines[] = 'Округ: ' . $data['district'];
        $lines[] = 'Административный центр: ' . $data['center_city'];
        if ( $data['bill'] !== null ) {
            $lines[] = 'Средний платёж семьи 3 чел.: ' . $data['bill'] . ' ₽/мес';
        }
        if ( $data['electricity'] !== null ) {
            $lines[] = 'Электроэнергия (день): ' . $data['electricity'] . ' ₽/кВт·ч';
        }
        if ( $data['electricity_night'] !== null ) {
            $lines[] = 'Электроэнергия (ночь): ' . $data['electricity_night'] . ' ₽/кВт·ч';
        }
        if ( $data['water'] !== null ) {
            $lines[] = 'Холодная вода: ' . $data['water'] . ' ₽/м³';
        }
        if ( $data['hot_water'] !== null ) {
            $lines[] = 'Горячая вода: ' . $data['hot_water'] . ' ₽/м³';
        }
        if ( $data['gas'] !== null ) {
            $lines[] = 'Газ: ' . $data['gas'] . ' ₽/м³';
        }
        if ( $data['heat'] !== null ) {
            $lines[] = 'Отопление: ' . $data['heat'] . ' ₽/Гкал';
        }
        if ( $data['regulator'] ) {
            $lines[] = 'Регулятор: ' . $data['regulator'];
        }
        if ( $data['index_2025'] !== null ) {
            $lines[] = 'Индексация в 2025 году: ' . $data['index_2025'] . '%';
        }
        $lines[] = '';
        $lines[] = 'Среднероссийский платёж для сравнения: 5 500 ₽/мес.';
        $lines[] = 'Плановая индексация 2026: 4%.';
        $lines[] = '';
        $lines[] = 'Возвращай только HTML без блока <html>/<body>, без markdown.';
        return implode( "\n", $lines );
    }

    /**
     * Простая санитизация HTML от LLM — оставляем только безопасные теги.
     */
    private static function sanitize_html( string $html ): string {
        $allowed = [
            'p' => [], 'h2' => [], 'h3' => [], 'ul' => [], 'ol' => [], 'li' => [],
            'strong' => [], 'em' => [], 'b' => [], 'i' => [], 'br' => [],
            'a' => [ 'href' => true, 'target' => true, 'rel' => true ],
        ];
        return wp_kses( $html, $allowed );
    }
}
