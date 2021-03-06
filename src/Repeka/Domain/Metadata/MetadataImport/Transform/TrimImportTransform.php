<?php
namespace Repeka\Domain\Metadata\MetadataImport\Transform;

class TrimImportTransform extends AbstractImportTransform {
    public function apply(array $values, array $config, array $dataBeingImported, string $parentMetadataValue = null): array {
        return array_map(
            function ($value) use ($config, $dataBeingImported) {
                if (is_array($value)) {
                    return $this->apply($value, $config, $dataBeingImported);
                }
                return trim($value, $config['characters'] ?? " \t\n\r\0\x0B");
            },
            $values
        );
    }
}
