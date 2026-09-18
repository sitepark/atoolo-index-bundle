![phpstan](https://img.shields.io/badge/PHPStan-level%209-brightgreen)
[![php](https://img.shields.io/badge/PHP-8.1-yellow)](## "is no longer checked automatically")
![php](https://img.shields.io/badge/PHP-8.2-blue)
![php](https://img.shields.io/badge/PHP-8.3-blue)
![php](https://img.shields.io/badge/PHP-8.4-blue)

# Atoolo index bundle

Provides the backend agnostic core for indexing [resources](https://github.com/sitepark/atoolo-resource-bundle).
It defines the indexer interface, the CMS side indexer configuration, the document
enricher mechanics, status handling, abortion, the console commands and the scheduler.
The index target itself (Solr, a GenAI application, …) is implemented by other bundles.

[Documentation](https://sitepark.github.io/atoolo-docs/develop/bundles/index-bundle/)
