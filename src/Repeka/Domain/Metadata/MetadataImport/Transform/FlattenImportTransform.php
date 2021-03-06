<?php
namespace Repeka\Domain\Metadata\MetadataImport\Transform;

use Repeka\Domain\Utils\ArrayUtils;

class FlattenImportTransform extends AbstractImportTransform {
    public function apply(array $values, array $config, array $dataBeingImported, string $parentMetadataValue = null): array {
        return ArrayUtils::flatten($values);
    }
}
