<?php
/**
 * Ссылка "Нашли ошибку?" — ведёт на контакты с предзаполненной темой.
 *
 * Опциональная переменная $region_name — название региона. Если есть — попадает в тему.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

$region_name_local = isset( $region_name ) && $region_name ? $region_name : '';

if ( $region_name_local ) {
    $subject = 'Ошибка в тарифах ЖКУ — ' . $region_name_local;
} else {
    $subject = 'Ошибка на карте тарифов ЖКУ';
}

// Контактная страница принимает GET-параметр ?subject= для предзаполнения формы.
// Gutena Forms / Contact Form 7 / любая другая форма читает параметр через JS-сниппет.
$contacts_url = add_query_arg( [
    'subject' => rawurlencode( $subject ),
    'ref'     => isset( $_SERVER['REQUEST_URI'] ) ? rawurlencode( wp_strip_all_tags( $_SERVER['REQUEST_URI'] ) ) : '',
], home_url( '/contacts/' ) );
?>
<p class="ms-report-error">
    Нашли ошибку или устаревшие данные?
    <a href="<?php echo esc_url( $contacts_url ); ?>" rel="nofollow">Сообщите нам →</a>
</p>
