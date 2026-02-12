<?php

namespace Xmf\I18n;

final class Translator
{
    public static function t(string $label): string
    {
        // “Looks like a constant” and is defined → translate; else return raw label
        if (\preg_match('/^_[A-Z][A-Z0-9_]*$/', $label) && \defined($label)) {
            return \constant($label);
        }
        return $label;
    }
}
