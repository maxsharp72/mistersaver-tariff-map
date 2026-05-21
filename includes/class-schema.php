<?php
/**
 * Генерация Schema.org JSON-LD разметки.
 *
 *   /tarify-zhku/                  → Dataset + BreadcrumbList
 *   /tarify-zhku/{slug}/           → Article + FAQPage + BreadcrumbList
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

class MS_Tariff_Map_Schema {

    public static function render(): void {
        if ( is_singular( MS_Tariff_Map_CPT::POST_TYPE ) ) {
            self::render_region();
        } elseif ( is_post_type_archive( MS_Tariff_Map_CPT::POST_TYPE ) ) {
            self::render_archive();
        }
    }

    private static function render_region(): void {
        global $post;
        if ( ! $post ) return;

        $bill = MS_Tariff_Map_ACF::get( $post->ID, 'avg_monthly_bill' );
        $region_name = MS_Tariff_Map_ACF::get( $post->ID, 'region_name_short' ) ?: $post->post_title;

        $article = [
            '@context'         => 'https://schema.org',
            '@type'            => [ 'Article', 'FAQPage' ],
            'headline'         => get_the_title( $post ),
            'datePublished'    => get_the_date( 'c', $post ),
            'dateModified'     => get_the_modified_date( 'c', $post ),
            'author'           => [ '@type' => 'Organization', 'name' => 'MisterSaver', 'url' => home_url() ],
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => 'MisterSaver',
                'logo'  => [ '@type' => 'ImageObject', 'url' => home_url( '/wp-content/uploads/logo.png' ) ],
            ],
            'mainEntityOfPage' => get_permalink( $post ),
            'description'      => wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 ),
            'mainEntity'       => self::region_faqs( $post, $region_name, $bill ),
        ];

        echo '<script type="application/ld+json">' . wp_json_encode( $article, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";

        // Breadcrumb
        echo '<script type="application/ld+json">' . wp_json_encode( self::breadcrumb_list( [
            [ 'name' => 'Главная',         'url' => home_url() ],
            [ 'name' => 'Тарифы ЖКУ',      'url' => get_post_type_archive_link( MS_Tariff_Map_CPT::POST_TYPE ) ],
            [ 'name' => $region_name,      'url' => get_permalink( $post ) ],
        ] ), JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    }

    private static function render_archive(): void {
        $dataset = [
            '@context'        => 'https://schema.org',
            '@type'           => 'Dataset',
            'name'            => 'Тарифы ЖКУ по регионам России 2026',
            'description'     => 'Актуальные тарифы на коммунальные услуги по всем 89 субъектам Российской Федерации: электроэнергия, вода, газ, отопление, ТКО.',
            'keywords'        => 'ЖКХ, тарифы, регионы России, коммуналка, электричество, газ, вода',
            'url'             => get_post_type_archive_link( MS_Tariff_Map_CPT::POST_TYPE ),
            'creator'         => [ '@type' => 'Organization', 'name' => 'MisterSaver' ],
            'dateModified'    => current_time( 'c' ),
            'spatialCoverage' => [ '@type' => 'Place', 'name' => 'Россия' ],
            'license'         => 'https://creativecommons.org/licenses/by/4.0/',
        ];
        echo '<script type="application/ld+json">' . wp_json_encode( $dataset, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    }

    private static function region_faqs( WP_Post $post, string $region_name, $bill ): array {
        $faqs = [
            [
                'q' => "Когда повысятся тарифы ЖКХ в {$region_name}?",
                'a' => 'Плановая индексация тарифов на 2026 год в среднем по России составит 4,0% (постановление Правительства РФ № 3147-р). Точная дата индексации — 1 июля 2026 года.',
            ],
            [
                'q' => "Как рассчитать квитанцию ЖКХ в {$region_name}?",
                'a' => 'Используйте калькулятор на MisterSaver — введите площадь квартиры, число прописанных и потребление, и калькулятор сам подставит тарифы региона.',
            ],
            [
                'q' => "Где посмотреть официальные тарифы {$region_name}?",
                'a' => 'Официальные тарифы публикуются на сайте регионального регулятора: ' . ( MS_Tariff_Map_ACF::get( $post->ID, 'regulatory_url' ) ?: 'см. сайт регионального комитета по тарифам.' ),
            ],
        ];
        if ( $bill ) {
            $faqs[] = [
                'q' => "Сколько в среднем платят за ЖКУ в {$region_name}?",
                'a' => 'Средний платёж семьи из 3 человек в ' . $region_name . ' составляет около ' . number_format( (float) $bill, 0, ',', ' ' ) . ' ₽ в месяц.',
            ];
        }

        return array_map( function ( $f ) {
            return [
                '@type'          => 'Question',
                'name'           => $f['q'],
                'acceptedAnswer' => [ '@type' => 'Answer', 'text' => $f['a'] ],
            ];
        }, $faqs );
    }

    private static function breadcrumb_list( array $items ): array {
        $list = [];
        foreach ( $items as $i => $item ) {
            $list[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $item['name'],
                'item'     => $item['url'],
            ];
        }
        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }
}
