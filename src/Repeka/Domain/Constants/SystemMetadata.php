<?php
namespace Repeka\Domain\Constants;

use Assert\Assertion;
use MyCLabs\Enum\Enum;
use Repeka\Application\Entity\EntityUtils;
use Repeka\Domain\Entity\Metadata;

/**
 * @method static SystemMetadata PARENT()
 */
class SystemMetadata extends Enum {
    const PARENT = -1;

    public function toMetadata() {
        $value = $this->getValue();
        $metadata = null;
        if ($value == self::PARENT) {
            $metadata = Metadata::create('relationship', 'Parent', ['EN' => 'Parent', 'PL' => 'Rodzic',], [], [], [], true);
        }
        /** @noinspection PhpUndefinedVariableInspection */
        Assertion::notNull($metadata, "Not implemented: metadata for value $value");
        EntityUtils::forceSetId($metadata, $value);
        return $metadata;
    }
}
