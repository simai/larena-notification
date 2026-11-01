<?php

declare(strict_types=1);

namespace Larena\Notification\Runtime;

use Illuminate\Contracts\Translation\Translator;

/**
 * The names people read for notification sources. A package that records notifications declares, for a source or
 * a source prefix it owns, a translation key of its own (e.g. "env:warning:backup_stale" →
 * "larena-env::admin.notification_sources.backup_stale"); the inbox screens show that name instead of the raw key.
 * The longest declared prefix wins; a source nobody named is shown as it is.
 */
final class NotificationSources
{
    /** @var array<string, string> prefix => translation key */
    private array $labels = [];

    public function __construct(private readonly ?Translator $translator = null)
    {
    }

    /**
     * A declaration that is not a source-like prefix or has no label is left out, so a mistyped one can never hide
     * another package's sources.
     */
    public function declare(string $prefix, string $label): void
    {
        if (preg_match('/^[a-z0-9][a-z0-9._:\/-]{0,99}$/', $prefix) === 1 && trim($label) !== '') {
            $this->labels[$prefix] = $label;
        }
    }

    /** The readable name of a source, or the source itself when no package named it. */
    public function name(string $source): string
    {
        $match = null;
        foreach ($this->labels as $prefix => $label) {
            if (str_starts_with($source, (string) $prefix) && ($match === null || strlen((string) $prefix) > strlen($match))) {
                $match = (string) $prefix;
            }
        }
        if ($match === null) {
            return $source;
        }
        $label = $this->labels[$match];
        $text = $this->translator?->get($label);

        return is_string($text) && $text !== '' && $text !== $label ? $text : $source;
    }
}
