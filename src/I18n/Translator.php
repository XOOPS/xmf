<?php
declare(strict_types=1);

namespace Xmf\I18n;

/**
 * Minimal translator that resolves XOOPS-style constant labels.
 */
final class Translator
{
    /**
     * Translate a label string by resolving it as a PHP constant if it
     * matches the XOOPS naming convention (_UPPERCASE_NAME).
     *
     * Security: The regex restricts input to uppercase constants starting
     * with underscore, preventing arbitrary constant access.
     *
     * @param string $label Label to translate (e.g. '_MI_MYMODULE_NAME')
     * @return string Resolved constant value, or the raw label if not a defined constant
     */
    public static function t(string $label): string
    {
        if (\preg_match('/^_[A-Z][A-Z0-9_]*$/', $label) === 1 && \defined($label)) {
            return (string) \constant($label);
        }
        return $label;
    }
}
