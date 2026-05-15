<?php

namespace App\Console\Commands;

use App\Services\Search\ElasticsearchSearchAdapter;
use Illuminate\Console\Command;

class SearchReindexCommand extends Command
{
    protected $signature = 'search:reindex';

    protected $description = 'Elasticsearch インデックスを再構築する（SEARCH_DRIVER=elasticsearch 時）';

    public function handle(ElasticsearchSearchAdapter $adapter): int
    {
        if (! ElasticsearchSearchAdapter::isConfigured()) {
            $this->error('SEARCH_DRIVER=elasticsearch かつ ELASTICSEARCH_URL を設定してください。');

            return self::FAILURE;
        }

        if (! $adapter->ping()) {
            $this->error('Elasticsearch に接続できません: '.config('search.elasticsearch.url'));

            return self::FAILURE;
        }

        if (! $adapter->reindex()) {
            $this->error('再インデックスに失敗しました。');

            return self::FAILURE;
        }

        $this->info('検索インデックスを再構築しました。');

        return self::SUCCESS;
    }
}
