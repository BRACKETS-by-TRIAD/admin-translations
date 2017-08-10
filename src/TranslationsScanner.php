<?php

namespace Brackets\AdminTranslations;

use Illuminate\Filesystem\Filesystem;

/*
 * This class is a fork from themsaid/laravel-langman and adjusted to our purpose.
 * We have chosen not to use whole package as long as it will auto-register commands
 * (once Laravel's 5.5 providers auto-discovery is out) that we would like not to
 * be available. If you find a better way, we would appreciate an advice :)
 */
class TranslationsScanner
{
    /**
     * The Filesystem instance.
     *
     * @var Filesystem
     */
    private $disk;

    /**
     * The paths to directories where we look for localised strings to scan.
     *
     * @var array
     */
    private $scannedPaths;

    /**
     * Manager constructor.
     *
     * @param Filesystem $disk
     * @param array $scannedPaths
     */
    public function __construct(Filesystem $disk, array $scannedPaths)
    {
        $this->disk = $disk;
        $this->scannedPaths = $scannedPaths;
    }

    /**
     * Get found translation lines found per file.
     *
     * e.g. ['users.blade.php' => ['users.name'], 'users/index.blade.php' => ['users.phone', 'users.city']]
     *
     * @return array
     */
    public function getAllViewFilesWithTranslations()
    {
        /*
         * This pattern is derived from Barryvdh\TranslationManager by Barry vd. Heuvel <barryvdh@gmail.com>
         *
         * https://github.com/barryvdh/laravel-translation-manager/blob/master/src/Manager.php
         */
        $functions = ['trans', 'trans_choice', 'Lang::get', 'Lang::choice', 'Lang::trans', 'Lang::transChoice', '@lang', '@choice'];

        $patternA =
            // See https://regex101.com/r/jS5fX0/4
            '[^\w]'. // Must not start with any alphanum or _
            '(?<!->)'. // Must not start with ->
            '('.implode('|', $functions).')'.// Must start with one of the functions
            "\(".// Match opening parentheses
            "[\'\"]".// Match " or '
            '('.// Start a new group to match:
            '[a-zA-Z0-9_-]+'.// Must start with group
            "([.][^\1)$]+)+".// Be followed by one or more items/keys
            ')'.// Close group
            "[\'\"]".// Closing quote
            "[\),]"  // Close parentheses or new parameter
        ;

        $patternB =
            // See https://regex101.com/r/jS5fX0/4
            '[^\w]'. // Must not start with any alphanum or _
            '(?<!->)'. // Must not start with ->
            '(__|getFromJson)'.// Must start with one of the functions
            "\(".// Match opening parentheses
            "[\'\"]".// Match " or '
            '('.// Start a new group to match:
            '[a-zA-Z0-9_-]+'.// Must start with group
            "([^\1)$]+)+".// Be followed by one or more items/keys
            ')'.// Close group
            "[\'\"]".// Closing quote
            "[\),]"  // Close parentheses or new parameter
        ;

        $allMatches = [];

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->disk->allFiles($this->scannedPaths) as $file) {
            if (preg_match_all("/$patternA/siU", $file->getContents(), $matches)) {
                $allMatches[$file->getRelativePathname()] = $matches[2];
            }

            if (preg_match_all("/$patternB/siU", $file->getContents(), $matches)) {
                if (isset($allMatches[$file->getRelativePathname()])) {
                    $allMatches[$file->getRelativePathname()] = array_merge($allMatches[$file->getRelativePathname()], $matches[2]);
                } else {
                    $allMatches[$file->getRelativePathname()] = $matches[2];
                }
            }
        }

        return $allMatches;
    }

}
