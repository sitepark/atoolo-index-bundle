<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Index\Dto\Indexer\IndexerConfiguration;
use RuntimeException;

class IndexerConfigurationLoader
{
    public function __construct(
        private readonly ResourceChannel $resourceChannel,
    ) {}

    /**
     * @return array<IndexerConfiguration>
     */
    public function loadAll(): array
    {
        $dir = $this->resourceChannel->configDir . '/indexer';
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob($dir . '/*.php') ?: [];

        $configurations = [];
        foreach ($files as $file) {
            $source = pathinfo($file, PATHINFO_FILENAME);
            $configurations[] = $this->load($source);
        }
        return $configurations;
    }

    private function getFile(string $source): string
    {
        return $this->resourceChannel->configDir
            . '/indexer/' . $source . '.php';
    }

    public function exists(string $source): bool
    {
        $file = $this->getFile($source);
        return file_exists($file);
    }

    public function load(string $source): IndexerConfiguration
    {
        $file = $this->getFile($source);

        if (!file_exists($file)) {
            return new IndexerConfiguration(
                $source,
                $source,
                new DataBag([]),
            );
        }

        $saveErrorReporting = error_reporting();

        try {
            error_reporting(E_ERROR | E_PARSE);
            ob_start();
            $data = require $file;
            if (!is_array($data)) {
                throw new RuntimeException(
                    'The indexer configuration '
                    . $file . ' should return an array',
                );
            }

            $name = $data['name'] ?? $source;
            /** @var array<string,mixed> $configData */
            $configData = is_array($data['data'] ?? null) ? $data['data'] : [];

            return new IndexerConfiguration(
                $source,
                is_string($name) ? $name : $source,
                new DataBag($configData),
            );
        } finally {
            ob_end_clean();
            error_reporting($saveErrorReporting);
        }
    }
}
