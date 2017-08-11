<?php namespace Brackets\AdminTranslations\Commands;

use Brackets\AdminTranslations\Translation;
use Brackets\AdminTranslations\TranslationsScanner;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ScanAndSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin-translations:scan-and-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scans all PHP files, extract translations and stores them into the database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // FIXME register a binding in the provider
        $scanner = new TranslationsScanner(
            new Filesystem,
            [app_path(), resource_path('views')]
        );

        list($trans, $__) = $scanner->getAllViewFilesWithTranslations();

        Translation::truncate();

        // FIXME we would like to add only translation we do not have already
        $trans->each(function($trans){
            // TODO there was a better way in a themsaid package, check it out
            list($group, $key) = explode('.', $trans, 2);
            $text = [];
            Translation::create([
                'group' => $group,
                'key' => $key,
                'text' => [],
            ]);
        });

        // FIXME we would like to add only translation we do not have already
        $__->each(function($default){
            Translation::create([
                'group' => '*',
                'key' => $default,
                'text' => [],
            ]);
        });

    }
}
