<?php
namespace Repeka\Domain\MetadataImport\Transform;

use Assert\Assertion;

class ImportTransformComposite {
    /** @var ImportTransform[] */
    private $transforms = [];

    /** @param ImportTransform[] $transforms */
    public function __construct(iterable $transforms) {
        foreach ($transforms as $transform) {
            $this->transforms[$transform->getName()] = $transform;
        }
    }

    public function apply(array $values, array $config): array {
        $name = $config['name'];
        Assertion::keyExists($this->transforms, $name, "Invalid transform name: $name");
        return $this->transforms[$name]->apply($values, $config);
    }
}
