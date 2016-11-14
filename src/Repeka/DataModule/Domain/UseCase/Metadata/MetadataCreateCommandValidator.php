<?php
namespace Repeka\DataModule\Domain\UseCase\Metadata;

use Repeka\CoreModule\Domain\Validation\CommandAttributesValidator;
use Repeka\CoreModule\Domain\Validation\Validator;

class MetadataCreateCommandValidator extends CommandAttributesValidator {
    /** @var string */
    private $mainLanguage;
    /** @var array */
    private $supportedControls;

    public function __construct(string $mainLanguage, array $supportedControls) {
        $this->mainLanguage = $mainLanguage;
        $this->supportedControls = $supportedControls;
    }

    protected function getValidator(): Validator {
        return Validator
            ::attribute('label', Validator::notBlankInLanguage($this->mainLanguage))
            ->attribute('name', Validator::notBlank())
            ->attribute('control', Validator::in($this->supportedControls));
    }
}
