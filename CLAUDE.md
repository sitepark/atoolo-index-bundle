# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Atoolo Index Bundle** is a Symfony bundle providing the backend agnostic
indexer core for the Atoolo resource bundle. It was extracted from
`atoolo/search-bundle`, because indexers no longer differ only in their
**source** but also in their **target**: Solr, a GenAI application, and
whatever comes next.

The bundle owns the `Indexer` interface, the CMS side indexer configuration,
the document enricher mechanics, status handling, abortion, the console
commands and the scheduler. It owns no index target of its own.

## Common Commands

```bash
composer install
composer analyse          # phplint, phpstan level 9, php-cs-fixer, compatibility
composer fix              # auto-fix code style
composer test             # phpunit with coverage
./tools/phpunit.phar -c phpunit.xml --no-coverage --filter SomeTest
```

## Architecture

### The target ports (`src/Service/Indexer/`)

An index target implements four interfaces; everything else in this bundle is
written against them:

- `IndexService` — the index itself: name, managed indices, updater, commit,
  delete, and a `prepareIndexing()` hook for whatever a target needs before a
  full run
- `IndexUpdater` — collects the documents of one chunk and transfers them
- `IndexUpdateResult` — `isSuccess()` / `getErrorMessage()`; a target that
  recognises unchanged documents implements `IndexUpdateResultWithUnchanged`
  instead, and the indexer shows their number as `unchanged` in the status
- `IndexDocumentFactory` — creates the target's `IndexDocument`

`IndexService` carries only what a run of the indexer needs. Free-form target
queries — Solr's delete-by-query for instance — stay with the target; the two
deletions in the port are the ones the lifecycle itself requires. The only
thing the port expects of a document is that it keeps the process id of the
run that wrote it, otherwise `deleteExcludingProcessId()` cannot tell stale
documents from current ones.

`IndexDocument` prescribes **no structure**. It extends `\JsonSerializable`
and nothing else: a document only has to represent itself as data, so that
`index:dump-document` can show what a run would write. Whether that is a flat
map of fields, a nested tree or a list of sections is the target's decision —
the indexer never looks inside.

### Indexing pipeline

`InternalResourceIndexer` is target agnostic. It

1. discovers resources via `LocationFinder`
2. filters them through a `ResourceFilter`
3. creates a document through the target's `IndexUpdater::createDocument()`
4. enriches it with the target's tagged `DocumentEnricher` implementations
5. sends batched updates through the target's `IndexUpdater`

A target bundle registers its own indexer instance - its own `IndexService`,
its own enricher iterator, its own progress state - and tags it
`atoolo_index.indexer`. Enrichers are always target specific and therefore
carry a tag of their own bundle.

### Id and source

An indexer has an **id** and a **source**. The source names where the
content comes from and goes to the target with every document, delete and
purge; indexers that read the same content into different targets share it -
the solr and the GenAI indexer both have the source `internal`. The id names
the indexer and is unique: it selects it on the console and in the schedule,
names its configuration `configs/indexer/<id>.php` and keys its status,
abortion and lock. `AbstractIndexer` and `IndexDocumentDumper` take it as an
optional last argument that defaults to the source. The `Indexer` interface
gains `getId()` only with 2.0; until then `IndexerId::of()` answers for any
indexer, falling back to the source. `IndexerProgressState`,
`IndexerConfiguration` and `IndexerMessage` keep their `$source` parameter
until then, but it holds the id.

### Document dumper

`IndexDocumentDumper` is generic as well, one instance per target, tagged
`atoolo_index.indexer.document_dumper`. It builds its document with the same
`IndexDocumentFactory` the target's updater uses, so a dump always shows what
an index run writes.

### Console

- `index:indexer [paths] [--source] [--indexer]` — run an indexer
- `index:update <paths> [--source] [--indexer]` — update single paths of
  every `UpdatableIndexer`
- `index:dump-document <paths> [--source] [--indexer]` — dump a document

`--source` narrows the candidates to a source, `--indexer` to one id. With
exactly one candidate it is used silently, with several the command asks.

### Scheduler

`AddScheduleMessengerPass` creates one transport per schedule name, so one
provider per indexer would need one `messenger:consume` worker per indexer.
`IndexerScheduleProvider` therefore builds a **single** schedule named
`atoolo_index` with one `RecurringMessage` per entry of the parameter
`atoolo_index.indexer.schedules` (`{ <id>: '<cron>' }`), and
`IndexerMessageHandler` resolves the indexer by the id of the message.

## Conventions

- PHPStan level 9, no baseline
- PER-CS code style
- Tests mirror `src/` under `test/`, PHPUnit 10 attributes
