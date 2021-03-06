<?php
namespace Repeka\Domain\Entity;

use Assert\Assertion;
use Cocur\Slugify\Slugify;
use Repeka\Domain\Constants\SystemResourceClass;
use Repeka\Domain\Repository\ResourceKindRepository;

/** @SuppressWarnings(PHPMD.TooManyPublicMethods)
 *  @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Metadata implements Identifiable, HasResourceClass {
    private $id;
    /** @var string */
    private $control;
    private $name;
    private $label = [];
    private $description = [];
    private $placeholder = [];
    /** @var Metadata */
    private $baseMetadata;
    private $ordinalNumber;
    /** @var Metadata */
    private $parentMetadata;
    private $constraints = [];
    /** @var string */
    private $groupId;
    private $shownInBrief = false;
    private $copyToChildResource = false;
    private $resourceClass;
    private $overrides = [];

    public const DEFAULT_GROUP = 'DEFAULT_GROUP';

    private function __construct() {
    }

    /** @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public static function create(
        string $resourceClass,
        MetadataControl $control,
        string $name,
        array $label,
        array $placeholder = [],
        array $description = [],
        array $constraints = [],
        string $groupId = Metadata::DEFAULT_GROUP,
        bool $shownInBrief = false,
        bool $copyToChildResource = false
    ): Metadata {
        Assertion::allNotNull($constraints);
        $metadata = new self();
        $metadata->resourceClass = $resourceClass;
        $metadata->control = $control->getValue();  // $control must be a string internally because it's so when read from DB
        $metadata->name = self::normalizeMetadataName($name);
        $metadata->ordinalNumber = -1;
        $metadata->update($label, $placeholder, $description, $constraints, $groupId, $shownInBrief, $copyToChildResource);
        return $metadata;
    }

    public static function createChild(Metadata $base, Metadata $parent): Metadata {
        $metadata = new self();
        $metadata->baseMetadata = $base;
        $metadata->ordinalNumber = -1;
        $metadata->resourceClass = $base->resourceClass;
        $metadata->control = $base->control;
        $metadata->parentMetadata = $parent;
        $metadata->shownInBrief = $base->shownInBrief;
        $metadata->copyToChildResource = $base->copyToChildResource;
        return $metadata;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getControl(): MetadataControl {
        return new MetadataControl($this->control);
    }

    public function getName(): string {
        return $this->isBase() ? $this->name : $this->baseMetadata->getName();
    }

    public function getResourceClass(): string {
        return $this->resourceClass;
    }

    public function getLabel(): array {
        return array_merge($this->label, array_filter($this->overrides['label'] ?? [], 'trim'));
    }

    public function getDescription(): array {
        return array_merge($this->description, array_filter($this->overrides['description'] ?? [], 'trim'));
    }

    public function getPlaceholder(): array {
        return array_merge($this->placeholder, array_filter($this->overrides['placeholder'] ?? [], 'trim'));
    }

    public function isBase(): bool {
        return !$this->baseMetadata;
    }

    public function isTopLevel(): bool {
        return !$this->parentMetadata;
    }

    public function getParentId() {
        return $this->isTopLevel() ? null : $this->parentMetadata->getId();
    }

    public function getParent(): Metadata {
        Assertion::false($this->isTopLevel());
        return $this->parentMetadata;
    }

    public function setParent(Metadata $parent) {
        $this->parentMetadata = $parent;
    }

    public function getConstraints(): array {
        return array_merge($this->constraints, $this->overrides['constraints'] ?? []);
    }

    public function getBaseConstraints(): array {
        return $this->constraints;
    }

    public function getGroupId(): string {
        if (isset($this->overrides['groupId'])) {
            return $this->overrides['groupId'];
        }
        return $this->groupId ?: Metadata::DEFAULT_GROUP;
    }

    public function isShownInBrief(): bool {
        return is_bool($this->overrides['shownInBrief'] ?? null) ? $this->overrides['shownInBrief'] : $this->shownInBrief;
    }

    public function isCopiedToChildResource(): bool {
        return is_bool($this->overrides['copyToChildResource'] ?? null)
            ? $this->overrides['copyToChildResource']
            : $this->copyToChildResource;
    }

    public function getBaseId(): ?int {
        return $this->isBase() ? null : $this->baseMetadata->getId();
    }

    public function updateOrdinalNumber($newOrdinalNumber) {
        Assertion::greaterOrEqualThan($newOrdinalNumber, 0);
        $this->ordinalNumber = $newOrdinalNumber;
    }

    public function update(
        array $newLabel,
        array $newPlaceholder,
        array $newDescription,
        array $newConstraints,
        string $newGroupId,
        bool $shownInBrief,
        bool $copyToChildResource
    ) {
        Assertion::allNotNull($newConstraints);
        $this->label = array_filter($newLabel, 'trim');
        $this->placeholder = $newPlaceholder;
        $this->description = $newDescription;
        $this->constraints = $newConstraints;
        $this->groupId = $newGroupId ?? Metadata::DEFAULT_GROUP;
        $this->shownInBrief = $shownInBrief;
        $this->copyToChildResource = $copyToChildResource;
    }

    public function withOverrides(array $overrides): Metadata {
        $overrides = [
            'label' => $this->removeValuesOverridingToTheSameThing($overrides['label'] ?? [], $this->label),
            'description' => $this->removeValuesOverridingToTheSameThing($overrides['description'] ?? [], $this->description),
            'placeholder' => $this->removeValuesOverridingToTheSameThing($overrides['placeholder'] ?? [], $this->placeholder),
            'constraints' => $this->removeValuesOverridingToTheSameThing($overrides['constraints'] ?? [], $this->constraints),
            'groupId' => $this->getOverrideIfDifferentThanActualValue($overrides, 'groupId', $this->groupId),
            'shownInBrief' => $this->isSetOverride($overrides, 'shownInBrief'),
            'copyToChildResource' => $this->isSetOverride($overrides, 'copyToChildResource'),
        ];
        $overrides = array_filter(
            $overrides,
            function ($override) {
                return !is_null($override) && !is_array($override) || !empty($override);
            }
        );
        $metadata = clone $this;
        $metadata->overrides = $overrides;
        return $metadata;
    }

    private function isSetOverride(array $overrides, string $overrideKey): ?bool {
        return isset($overrides[$overrideKey]) && is_bool($overrides[$overrideKey]) ? $overrides[$overrideKey] : null;
    }

    private function removeValuesOverridingToTheSameThing(array $overrides, array $actualValues): array {
        $filtered = $overrides;
        foreach ($actualValues as $key => $actualValue) {
            if (array_key_exists($key, $overrides) && $overrides[$key] == $actualValue) {
                unset($filtered[$key]);
            }
        }
        return $filtered;
    }

    private function getOverrideIfDifferentThanActualValue($overrides, $overrideKey, $actualValue) {
        if (isset($overrides[$overrideKey]) && $actualValue !== $overrides[$overrideKey]) {
            return $overrides[$overrideKey];
        }
        return null;
    }

    public function getOverrides(): array {
        $overrides = $this->overrides;
        $overrides = $this->getSingleOverride($overrides, $this->shownInBrief, 'shownInBrief');
        $overrides = $this->getSingleOverride($overrides, $this->copyToChildResource, 'copyToChildResource');
        return $overrides;
    }

    private function getSingleOverride(array $overrides, bool $overrideValue, string $overrideKey): array {
        if (isset($overrides[$overrideKey])) {
            if ($overrideValue === $overrides[$overrideKey] || !is_bool($overrides[$overrideKey])) {
                unset($overrides[$overrideKey]);
            }
        }
        return $overrides;
    }

    /** @SuppressWarnings(PHPMD.BooleanArgumentFlag) */
    public function canDetermineAssignees(ResourceKindRepository $resourceKindRepository, bool $baseMetadataConstraints = false): bool {
        if ($this->control == MetadataControl::RELATIONSHIP) {
            $allowedResourceKindIds = $baseMetadataConstraints
                ? ($this->getBaseConstraints()['resourceKind'] ?? [])
                : ($this->getConstraints()['resourceKind'] ?? []);
            if ($allowedResourceKindIds) {
                $resourceClasses = array_map(
                    function (int $resourceKindId) use ($resourceKindRepository) {
                        return $resourceKindRepository->findOne($resourceKindId)->getResourceClass();
                    },
                    $allowedResourceKindIds
                );
                return array_unique($resourceClasses) == [SystemResourceClass::USER];
            }
        }
        return false;
    }

    public static function normalizeMetadataName(string $name): string {
        $unCamelCased = preg_replace('#([a-z])([A-Z])#', '$1 $2', $name);
        return (new Slugify(['separator' => '_']))->slugify($unCamelCased);
    }
}
