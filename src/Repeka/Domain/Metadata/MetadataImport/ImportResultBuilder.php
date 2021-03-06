<?php
namespace Repeka\Domain\Metadata\MetadataImport;

use Repeka\Domain\Entity\ResourceContents;

class ImportResultBuilder {
    /** @var array[] */
    private $acceptedValues = [];
    /** @var string[][] */
    private $unfitTypeValues = [];
    /** @var string[] */
    private $invalidMetadataKeys;

    /**
     * @param string[] $invalidMetadataKeys
     */
    public function __construct(array $invalidMetadataKeys) {
        $this->invalidMetadataKeys = $invalidMetadataKeys;
    }

    /**
     * @param array $values
     */
    public function addAcceptedValues(int $metadataId, array $values): void {
        $this->acceptedValues[$metadataId] = $values;
    }

    /**
     * @param array $values
     */
    public function setAcceptedValues(array $values): void {
        $this->acceptedValues = $values;
    }

    /**
     * @param string[] $values
     */
    public function addUnfitTypeValues(int $metadataId, $value): void {
        $this->unfitTypeValues[$metadataId][] = $value;
    }

    public function build(): ImportResult {
        $notEmptyCallback = function (array $array) {
            return !empty($array);
        };
        $this->acceptedValues = ResourceContents::fromArray((array_filter($this->acceptedValues, $notEmptyCallback)))
            ->filterOutEmptyMetadata();
        $this->unfitTypeValues = array_filter($this->unfitTypeValues, $notEmptyCallback);
        return new ImportResult($this->acceptedValues, $this->unfitTypeValues, $this->invalidMetadataKeys);
    }
}
