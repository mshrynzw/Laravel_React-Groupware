<?php

return [
    /*
    | database: テーブル横断（PostgreSQL 全文検索 / SQLite LIKE）
    | elasticsearch: ELASTICSEARCH_URL が有効なとき ES を優先（失敗時は database にフォールバック）
    */
    'driver' => env('SEARCH_DRIVER', 'database'),

    'elasticsearch' => [
        'url' => env('ELASTICSEARCH_URL', 'http://localhost:9200'),
        'index' => env('ELASTICSEARCH_INDEX', 'groupware_search'),
        'timeout_seconds' => (int) env('ELASTICSEARCH_TIMEOUT', 2),
    ],
];
