<?php
namespace Repeka\Domain\Upload;

use Repeka\Application\Upload\ResourceFileExistException;
use Repeka\Domain\Entity\ResourceEntity;

interface ResourceFileHelper {
    public function moveFilesToDestinationPaths(ResourceEntity $resource): int;

    public function prune(ResourceEntity $resource): void;

    public function toAbsolutePath(string $path): string;
}
