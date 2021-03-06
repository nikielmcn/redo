<?php
namespace Repeka\Domain\Constants;

use Assert\Assertion;
use MyCLabs\Enum\Enum;
use Repeka\Domain\Entity\Metadata;
use Repeka\Domain\Entity\MetadataControl;
use Repeka\Domain\Utils\EntityUtils;

/**
 * @method static SystemMetadata PARENT()
 * @method static SystemMetadata USERNAME()
 * @method static SystemMetadata GROUP_MEMBER()
 * @method static SystemMetadata REPRODUCTOR()
 * @method static SystemMetadata RESOURCE_LABEL()
 */
class SystemMetadata extends Enum {
    const PARENT = -1;
    const USERNAME = -2;
    const GROUP_MEMBER = -3;
    const REPRODUCTOR = -4;
    const RESOURCE_LABEL = -5;

    public function toMetadata() {
        $value = $this->getValue();
        $metadata = null;
        if ($value == self::PARENT) {
            $metadata = Metadata::create(
                '',
                MetadataControl::RELATIONSHIP(),
                'Parent',
                ['EN' => 'Parent resource', 'PL' => 'Zasób nadrzędny']
            );
        } elseif ($value == self::USERNAME) {
            $metadata = Metadata::create(
                SystemResourceClass::USER,
                MetadataControl::TEXT(),
                'Username',
                ['EN' => 'Username', 'PL' => 'Nazwa użytkownika'],
                [],
                [],
                [],
                Metadata::DEFAULT_GROUP,
                true
            );
        } elseif ($value == self::GROUP_MEMBER) {
            $metadata = Metadata::create(
                SystemResourceClass::USER,
                MetadataControl::RELATIONSHIP(),
                'Group member',
                ['EN' => 'Group member', 'PL' => 'Członek grupy']
            );
        } elseif ($value == self::REPRODUCTOR) {
            $metadata = Metadata::create(
                '',
                MetadataControl::RELATIONSHIP(),
                'Reproductor',
                ['EN' => 'Reproductor', 'PL' => 'Reproduktor'],
                [],
                [],
                [],
                Metadata::DEFAULT_GROUP,
                false,
                true
            );
        } elseif ($value == self::RESOURCE_LABEL) {
            $metadata = Metadata::create(
                '',
                MetadataControl::DISPLAY_STRATEGY(),
                'label',
                ['EN' => 'Label', 'PL' => 'Etykieta'],
                [],
                [],
                ['displayStrategy' => '#{{ r.id }}'],
                Metadata::DEFAULT_GROUP,
                true
            );
        }
        /** @noinspection PhpUndefinedVariableInspection */
        Assertion::notNull($metadata, "Not implemented: metadata for value $value");
        EntityUtils::forceSetId($metadata, $value);
        return $metadata;
    }
}
