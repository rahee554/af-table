<?php

namespace ArtflowStudio\Table\Tests\Support;

/**
 * Reads a class's source with all comments removed.
 *
 * Several regression tests assert that a construct is *absent* from a file.
 * Plain file_get_contents() makes those tests lie the moment someone documents
 * the old behaviour in a docblock — e.g. "this used to call SET FOREIGN_KEY_CHECKS"
 * would keep a passing fix looking broken. Stripping comments first means the
 * assertions only ever see executable code.
 */
final class SourceScanner
{
    /** Executable PHP source of a class, with comments and docblocks removed. */
    public static function executableSourceOf(string $class): string
    {
        $file = (new \ReflectionClass($class))->getFileName();

        if ($file === false) {
            throw new \RuntimeException("Cannot locate source for [{$class}].");
        }

        return self::stripComments(file_get_contents($file));
    }

    public static function stripComments(string $source): string
    {
        $out = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token)) {
                if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    // Preserve line count so any reported offsets stay meaningful.
                    $out .= str_repeat("\n", substr_count($token[1], "\n"));

                    continue;
                }

                $out .= $token[1];

                continue;
            }

            $out .= $token;
        }

        return $out;
    }

    /** True when the executable source of $class contains $needle. */
    public static function contains(string $class, string $needle): bool
    {
        return str_contains(self::executableSourceOf($class), $needle);
    }
}
