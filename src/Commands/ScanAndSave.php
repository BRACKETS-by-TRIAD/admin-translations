<?php namespace Brackets\AdminTranslations\Commands;

use Brackets\AdminTranslations\Translation;
use Brackets\AdminTranslations\TranslationsScanner;
use Carbon\Carbon;
use DB;
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

        // TODO refactor this body into multiple methods

        // TODO add test coverage for this command

        DB::transaction(function() use ($trans, $__){
            Translation::query()
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => Carbon::now()
                ]);

            $trans->each(function($trans){
                // TODO there was a better way in a themsaid package, check it out
                list($group, $key) = explode('.', $trans, 2);
                $this->createOrUpdate($group, $key);
            });

            $__->each(function($default){
                $this->createOrUpdate('*', $default);
            });
        });

    }

    protected function createOrUpdate($group, $key) {
        /** @var Translation $translation */
        $translation = Translation::withTrashed()
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        if ($translation) {
            $translation->restore();
        } else {
            Translation::create([
                'group' => $group,
                'key' => $key,
                'text' => [],
            ]);
        }
    }
}
