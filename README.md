# MisterSaver Tariff Map

WordPress-плагин для проекта [MisterSaver.ru](https://mistersaver.ru): интерактивная карта тарифов ЖКУ по 89 регионам России. Создаёт CPT `region_tariff`, шорткод `[ms_tariff_map]`, REST-эндпоинты, страницы регионов с Schema.org разметкой и автогенерацией контента через OpenRouter API.

**Стек:** WordPress 6.5+, PHP 8.1+, OpenLayers 9, Яндекс Tiles API, ACF (опционально), Rank Math (опционально).

---

## Быстрая установка (5 минут)

### Шаг 1. Подключитесь к серверу по SSH

```bash
ssh user@your-server.ru
cd /var/www/mistersaver.ru/wp-content/plugins/
```

> Замените путь на реальный путь до `wp-content/plugins/` вашего сайта.

### Шаг 2. Клонируйте репозиторий

```bash
git clone https://github.com/maxsharp72/mistersaver-tariff-map.git
```

После этого появится папка `mistersaver-tariff-map/` с плагином.

### Шаг 3. Активируйте плагин в админке

Откройте `https://mistersaver.ru/wp-admin/plugins.php` и нажмите **«Активировать»** рядом с «MisterSaver Tariff Map».

При активации плагин:
- Создаёт CPT `region_tariff` (тип записей «Тарифы регионов»)
- Регистрирует таксономию `federal_district` и создаёт 8 терминов (ЦФО, СЗФО, …)
- Сбрасывает rewrite-правила (URL `/tarify-zhku/{slug}/` начинают работать)
- Создаёт пустую запись в `wp_options` для настроек

### Шаг 4. Откройте «Тарифы ЖКУ → Настройки» в админке

Введите два ключа:

| Поле | Откуда взять |
|---|---|
| **Яндекс Tiles API ключ** | [developer.tech.yandex.ru](https://developer.tech.yandex.ru/) → создать приложение → JavaScript API и HTTP Геокодер |
| **OpenRouter API ключ** | [openrouter.ai/keys](https://openrouter.ai/keys) — нужен только для автогенерации текстов |

Можно начать без OpenRouter — карта будет работать, а тексты регионов соберутся по дефолтному шаблону.

### Шаг 5. Импортируйте данные регионов

Через WP-CLI (рекомендую):

```bash
cd /var/www/mistersaver.ru/wp-content/plugins/mistersaver-tariff-map
wp ms-tariffs import --path=/var/www/mistersaver.ru
```

Эта команда:
1. Читает `data/regions-tariffs.json` (89 регионов)
2. Создаёт 89 записей CPT `region_tariff` с тарифами в ACF/meta-полях
3. Привязывает каждый регион к таксономии федерального округа
4. Заполняет `post_content` дефолтным шаблоном (с маркером для LLM)

После импорта проверьте: `wp ms-tariffs status`

```
Регионов в БД:                89
С данными по avg_bill:        89
С LLM-сгенерированным текстом: 0
Яндекс ключ:    ✓ настроен
OpenRouter:     ✓ настроен
```

### Шаг 6. (Опционально) Сгенерируйте уникальные тексты

```bash
wp ms-tariffs generate-content
```

Запустит LLM (по умолчанию `anthropic/claude-3.5-sonnet`) для всех 89 регионов. Займёт ~5 минут, ~$1-2 на OpenRouter (по claude-sonnet).

Для тестов на 5 регионах:
```bash
wp ms-tariffs generate-content --limit=5
```

### Шаг 7. Проверьте результат

Откройте в браузере:

- **Главная карта:** `https://mistersaver.ru/tarify-zhku/`
- **Страница региона:** `https://mistersaver.ru/tarify-zhku/moskva/`
- **REST API:** `https://mistersaver.ru/wp-json/mistersaver/v1/regions`

Если URL отдают 404 — выполните `wp rewrite flush` или зайдите в `Settings → Permalinks` и нажмите «Save».

---

## Структура плагина

```
mistersaver-tariff-map/
├── ms-tariff-map.php              # bootstrap, регистрация хуков
├── README.md                      # этот файл
├── includes/
│   ├── class-cpt.php              # CPT region_tariff + таксономия federal_district
│   ├── class-acf-fields.php       # 30 ACF-полей региона
│   ├── class-rest.php             # /wp-json/mistersaver/v1/regions[.geojson]
│   ├── class-shortcode.php        # [ms_tariff_map]
│   ├── class-template-loader.php  # подмена single/archive шаблонов
│   ├── class-schema.php           # Schema.org Article/FAQPage/Dataset
│   ├── class-importer.php         # импорт JSON → БД
│   ├── class-llm-generator.php    # OpenRouter API
│   ├── class-settings.php         # страница настроек в админке
│   └── class-cli.php              # WP-CLI команды
├── templates/
│   ├── archive-region_tariff.php  # /tarify-zhku/ (главная карта)
│   ├── single-region_tariff.php   # /tarify-zhku/{slug}/
│   └── parts/
│       ├── map-full.php           # HTML полной карты (8 секций)
│       ├── map-mini.php           # мини-карта для страницы региона
│       ├── tariff-table.php       # таблица тарифов
│       ├── avg-payment-card.php   # карточка среднего платежа
│       ├── indexation-card.php    # карточка индексации
│       ├── faq.php                # 4 вопроса (для UI и Schema)
│       ├── cta-partner.php        # партнёрский CTA
│       └── similar-regions.php    # 5 соседей по ФО
├── assets/
│   ├── css/map.css                # стили в духе MisterSaver
│   └── js/map.js                  # OpenLayers + интерактив
└── data/
    ├── regions-tariffs.json       # 89 регионов с тарифами (источник правды)
    └── regions.geojson            # геометрия 89 регионов (587 KB)
```

---

## Шорткоды

### `[ms_tariff_map]` — полная карта

Главная страница карты (используется в архивном шаблоне). Можно вставить и на любую другую страницу.

```
[ms_tariff_map]
```

### `[ms_tariff_map mode="mini" region="moskva"]` — мини-карта одного региона

Используется автоматически на страницах региона (см. `templates/single-region_tariff.php`).

### Атрибуты

| Атрибут  | Значения | Описание |
|---|---|---|
| `mode`   | `full` / `mini` | По умолчанию `full` |
| `region` | slug региона | Только для `mode="mini"` |
| `height` | px | Высота контейнера, по умолчанию `700` |
| `layer`  | `bill` / `electricity` / `water` / `hot_water` / `gas` | Активный слой при загрузке |

---

## WP-CLI команды

```bash
wp ms-tariffs status                     # статистика по регионам
wp ms-tariffs import                     # импорт из data/regions-tariffs.json
wp ms-tariffs import /path/to/file.json  # импорт из своего файла
wp ms-tariffs generate-content           # LLM для всех без контента
wp ms-tariffs generate-content --force   # перегенерировать все
wp ms-tariffs generate-content --slug=moskva --force
wp ms-tariffs generate-content --limit=5
wp ms-tariffs flush-cache                # сбросить кеш REST + rewrite
```

---

## Workflow обновления тарифов (2 раза в год)

При плановой индексации 1 января и 1 июля:

```bash
ssh user@server
cd /var/www/mistersaver.ru/wp-content/plugins/mistersaver-tariff-map

# Вариант 1: обновить через git pull (если изменения в репозитории)
git pull
wp ms-tariffs import

# Вариант 2: обновить точечно через свой JSON
wp ms-tariffs import /tmp/new-tariffs-2026-07.json
wp ms-tariffs flush-cache
```

Тексты регионов (post_content) при импорте **не перезаписываются** — только числа в ACF. Если хотите перегенерировать тексты:

```bash
wp ms-tariffs generate-content --force
```

---

## REST API

### `GET /wp-json/mistersaver/v1/regions`

Возвращает массив всех регионов:

```json
[
  {
    "id": 77,
    "slug": "moskva",
    "name": "г. Москва",
    "short_name": "Москва",
    "district": "ЦФО",
    "center_city": "Москва",
    "bill": 8649,
    "bill_estimated": false,
    "electricity": 7.87,
    "water": 65.77,
    "gas": 7.99,
    "tier": 5,
    "has_data": true,
    "url": "https://mistersaver.ru/tarify-zhku/moskva/"
  },
  ...
]
```

### `GET /wp-json/mistersaver/v1/regions.geojson`

GeoJSON FeatureCollection с геометрией + теми же properties. Кешируется на 1 час.

---

## Интеграция с Rank Math

Плагин **не конфликтует** с Rank Math. Дополнительно:

- В админке каждой записи `region_tariff` можно настроить SEO title/description через Rank Math
- Sitemap Rank Math автоматически подхватит CPT
- Schema.org от нашего плагина дополняет (а не дублирует) разметку Rank Math

---

## Зависимости

| Что | Требуется | Альтернатива |
|---|---|---|
| WordPress | 6.0+ | — |
| PHP | 8.1+ | — |
| ACF Pro | Желательно | Плагин работает и без ACF — использует нативные meta-поля |
| OpenRouter API | Только для LLM | Можно вручную писать тексты в админке |
| Яндекс Tiles | Желательно | Fallback на OpenStreetMap |

---

## Лицензия

GPL v2 or later.

## Поддержка

Issues и pull requests: [github.com/maxsharp72/mistersaver-tariff-map/issues](https://github.com/maxsharp72/mistersaver-tariff-map/issues)
