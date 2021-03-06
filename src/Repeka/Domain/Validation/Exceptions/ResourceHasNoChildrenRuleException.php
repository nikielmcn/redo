<?php
namespace Repeka\Domain\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class ResourceHasNoChildrenRuleException extends ValidationException {
    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => 'resource has children',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => 'resource has no children',
        ],
    ];
}
