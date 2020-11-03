<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\CI;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Output messages using the Github Actions annotations format.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 *
 * @see https://docs.github.com/en/free-pro-team@latest/actions/reference/workflow-commands-for-github-actions#setting-a-debug-message
 */
class GithubAnnotationsReporter
{
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public static function isGithubActionEnvironment(): bool
    {
        return false !== getenv('GITHUB_ACTIONS');
    }

    public function error(string $message, string $file = null, int $line = null, int $col = null): void
    {
        $this->log('error', $message, $file, $line, $col);
    }

    public function warning(string $message, string $file = null, int $line = null, int $col = null): void
    {
        $this->log('warning', $message, $file, $line, $col);
    }

    public function debug(string $message, string $file = null, int $line = null, int $col = null): void
    {
        $this->log('debug', $message, $file, $line, $col);
    }

    private function log(string $type, string $message, string $file = null, int $line = null, int $col = null): void
    {
        // Some values must be encoded.
        // See https://github.com/actions/toolkit/blob/5e5e1b7aacba68a53836a34db4a288c3c1c1585b/packages/core/src/command.ts#L80-L85
        $message = strtr($message, [
            '%' => '%25',
            "\r" => '%0D',
            "\n" => '%0A',
        ]);

        if (!$file) {
            // No file provided, output the message solely:
            $this->output->writeln(sprintf('::%s::%s', $type, $message));

            return;
        }

        $metas = ['file' => $file, 'line' => $line ?? 1, 'col' => $col ?? 0];

        array_walk($metas, static function (&$value, string $key): void {
            // Some property values must be encoded:
            // See https://github.com/actions/toolkit/blob/5e5e1b7aacba68a53836a34db4a288c3c1c1585b/packages/core/src/command.ts#L87-L94
            $value = sprintf('%s=%s', $key, strtr((string) $value, [
                '%' => '%25',
                "\r" => '%0D',
                "\n" => '%0A',
                ':' => '%3A',
                ',' => '%2C',
            ]));
        });

        $this->output->writeln(sprintf('::%s %s::%s', $type, implode(',', $metas), $message));
    }
}
