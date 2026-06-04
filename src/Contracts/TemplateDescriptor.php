<?php

declare(strict_types=1);

namespace Larena\Notification\Contracts;

final readonly class TemplateDescriptor
{
    /**
     * @param list<string> $requiredContextKeys
     */
    public function __construct(
        public string $templateKey,
        public string $locale,
        public string $subjectKey,
        public string $bodyKey,
        public array $requiredContextKeys = [],
        public bool $safePreviewAllowed = true,
    ) {
    }
}
