<?php
namespace Repeka\Application\Elasticsearch;

use Repeka\Application\Elasticsearch\Mapping\ESMapping;
use Repeka\Application\Elasticsearch\Mapping\FtsConstants;

class ESIndexManager {
    /** @var ESMapping */
    private $esMapping;
    /** @var string */
    private $indexName;
    /** @var \Elastica\Index */
    private $index;

    public function __construct(ESClient $client, ESMapping $esMapping, string $indexName) {
        $this->indexName = $indexName;
        $this->esMapping = $esMapping;
        $this->index = $client->getIndex($this->indexName);
    }

    public function create(int $numberOfShards = 1, int $numberOfReplicas = 0) {
        if ($this->index->exists()) {
            throw new \Exception("Index '{$this->indexName}' already exists");
        }
        $this->index->create(
            [
                'number_of_shards' => $numberOfShards,
                'number_of_replicas' => $numberOfReplicas,
                'index.mapping.ignore_malformed' => true,
                'analysis' => [
                    'analyzer' => [
                        'underscore_analyzer' => [
                            "type" => "custom",
                            "tokenizer" => "underscore_tokenizer",
                            "filter" => ["standard", "lowercase", "morfologik_stem"],
                        ],
                        'default' => [
                            "tokenizer" => "standard",
                            "filter" => ["standard", "lowercase", "morfologik_stem"],
                        ],
                    ],
                    "tokenizer" => [
                        "underscore_tokenizer" => [
                            "type" => "pattern",
                            "pattern" => "_|\.|-",
                        ],
                    ],
                ],
            ]
        );
        $type = $this->index->getType(FtsConstants::ES_DOCUMENT_TYPE);
        $this->esMapping->getMapping($type)->send();
    }

    public function delete() {
        if (!$this->index->exists()) {
            throw new \Exception("Index '{$this->indexName}' doesn't exist");
        }
        $this->index->delete();
    }

    public function exists(): bool {
        return $this->index->exists();
    }

    public function getIndex(): string {
        return $this->indexName;
    }
}
