<?php
namespace Repeka\Domain\Metadata\MetadataImport\Transform;

interface ImportTransform {
    public function apply(array $values, array $config): array;

    public function getName(): string;
}