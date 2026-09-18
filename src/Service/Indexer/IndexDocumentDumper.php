<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

use Atoolo\Resource\ResourceLoader;
use Atoolo\Resource\ResourceLocation;

/**
 * Dumps the index document of a single index target.
 *
 * The dumper is generic, one instance per target is registered. The document
 * itself is created by the same {@see IndexDocumentFactory} the target's
 * {@see IndexUpdater} uses, so a dump always shows what an index run would
 * write.
 */
class IndexDocumentDumper
{
    /**
     * @param iterable<DocumentEnricher<IndexDocument>> $documentEnricherList
     */
    public function __construct(
        private readonly ResourceLoader $resourceLoader,
        private readonly iterable $documentEnricherList,
        private readonly IndexDocumentFactory $documentFactory,
        private readonly string $source,
    ) {}

    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @param string[] $paths
     * @return array<int,array<string,mixed>>
     *    Returns the raw array data of the documents to be able to
     *    output them as JSON, for example.
     */
    public function dump(array $paths): array
    {
        $documents = [];
        foreach ($paths as $path) {
            $location = ResourceLocation::of($path);
            $resource = $this->resourceLoader->load($location);
            $doc = $this->documentFactory->create();
            $processId = 'process-id';

            foreach ($this->documentEnricherList as $enricher) {
                $doc = $enricher->enrichDocument(
                    $resource,
                    $doc,
                    $processId,
                );
            }

            $documents[] = $doc->getFields();
        }

        return $documents;
    }
}
