<?php
namespace Repeka\Domain\Metadata\MetadataValueAdjuster;

use Repeka\Domain\Entity\MetadataControl;
use Repeka\Domain\Entity\MetadataDateControl\FlexibleDate;
use Repeka\Domain\Entity\MetadataDateControl\MetadataDateControlConverterUtil;
use Repeka\Domain\Entity\MetadataValue;

class FlexibleDateMetadataValueAdjuster implements MetadataValueAdjuster {
    public function supports(string $control): bool {
        return $control == MetadataControl::FLEXIBLE_DATE || $control == MetadataControl::DATE_RANGE;
    }

    public function adjustMetadataValue(MetadataValue $value, MetadataControl $control): MetadataValue {
        return $value->withNewValue($this->convertDateControlMetadataValuesToFlexibleDate($value->getValue()));
    }

    /**
     * @param FlexibleDate | array $value
     * @return array
     */
    private function convertDateControlMetadataValuesToFlexibleDate($value): array {
        if (!($value instanceof FlexibleDate)) {
            $rangeMode = array_key_exists('rangeMode', $value) ? $value['rangeMode'] : null;
            $value = new FlexibleDate(
                $value['from'] ?? null,
                $value['to'] ?? null,
                $value['mode'] ?? null,
                $rangeMode
            );
        }
        return MetadataDateControlConverterUtil::convertDateToFlexibleDate(
            $value->getFrom(),
            $value->getTo(),
            $value->getMode(),
            $value->getRangeMode()
        )->toArray();
    }
}
