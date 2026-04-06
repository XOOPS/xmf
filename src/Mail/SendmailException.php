<?php

declare(strict_types=1);

namespace Xmf\Mail;

/**
 * Specific exception for sendmail delivery failures.
 *
 * @category  Xmf\Mail
 * @package   Xmf
 * @author    XOOPS Development Team <contact@xoops.org>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
final class SendmailException extends \RuntimeException
{
    public static function invalidPath(): self
    {
        return new self('Invalid sendmail path.');
    }

    public static function failedToStartProcess(): self
    {
        return new self('Failed to start sendmail process.');
    }

    public static function failedToOpenPipes(): self
    {
        return new self('Failed to open sendmail pipes.');
    }

    public static function writeFailure(): self
    {
        return new self('Failed to write message to sendmail (broken pipe).');
    }

    public static function prematurePipeClosure(): self
    {
        return new self('sendmail closed the input pipe prematurely.');
    }

    public static function exitedWithCode(int $code, string $firstLine = ''): self
    {
        return new self('Sendmail exited with code ' . $code . ($firstLine !== '' ? ': ' . $firstLine : ''));
    }
}
