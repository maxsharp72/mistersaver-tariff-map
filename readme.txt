=== MisterSaver Tariff Map ===
Contributors: maxsharp72
Tags: жку, тарифы, карта, регионы, ленинградская область, openlayers
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPL v2 or later

Интерактивная карта тарифов ЖКУ по 89 регионам России. CPT region_tariff + OpenLayers + Яндекс Tiles API.

== Description ==

Создаёт интерактивную карту тарифов на коммунальные услуги по всем 89 субъектам Российской Федерации.

* CPT `region_tariff` с URL `/tarify-zhku/{slug}/`
* Шорткод `[ms_tariff_map]`
* REST-эндпоинты для фронта карты
* Schema.org Article + FAQPage + Dataset
* LLM-генерация уникального контента через OpenRouter API
* WP-CLI команды для импорта и обновления

Полная документация: https://github.com/maxsharp72/mistersaver-tariff-map

== Installation ==

1. Скачайте/клонируйте плагин в `wp-content/plugins/mistersaver-tariff-map`
2. Активируйте через меню «Плагины»
3. Настройте API ключи в «Тарифы ЖКУ → Настройки»
4. Запустите `wp ms-tariffs import` для загрузки данных
5. (Опционально) `wp ms-tariffs generate-content` для генерации текстов

== Changelog ==

= 0.2.2 =
* Hotfix: исправлен неправильный селектор обнаружения full-mode в main() (карта в 0.2.1 не инициализировалась)

= 0.2.1 =
* Фикс: opacity-контрол перенесён в правый нижний угол (больше не накрывает табы)
* Фикс: tooltip региона больше не вылетает в левый верхний угол (используется правильный offset-parent)
* Фикс: атрибуция Yandex/OL сворачиваемая, прикреплена в правом нижнем, компактная
* Фикс: мини-карта на странице региона рендерится (добавлен initMiniMap)

= 0.2.0 =
* Admin UI для импорта без WP-CLI
* Шаблоны страниц регионов

= 0.1.0 =
* Первый релиз: CPT + 89 регионов + REST + шорткод + LLM-интеграция
