<?php namespace Brackets\AdminTranslations\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class AdminTranslationsInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin-translations:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install a brackets/admin-translations package';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Installing package brackets/admin-translations');

        $this->call('admin-ui:install');

        $this->call('vendor:publish', [
            '--provider' => "Brackets\\AdminTranslations\\AdminTranslationsServiceProvider",
        ]);

        $this->call('vendor:publish', [
            '--provider' => "Brackets\\Translatable\\TranslatableServiceProvider",
            '--tag' => 'config'
        ]);

        $this->frontendAdjustments();

        $this->call('migrate');

        $this->info('Package brackets/admin-translations installed');
    }

    private function strReplaceInFile($fileName, $ifExistsRegex, $find, $replaceWith) {
        $content = File::get($fileName);
        if (preg_match($ifExistsRegex, $content)) {
            return;
        }

        return File::put($fileName, str_replace($find, $replaceWith, $content));
    }

    private function appendIfNotExists($fileName, $ifExistsRegex, $append) {
        $content = File::get($fileName);
        if (preg_match($ifExistsRegex, $content)) {
            return;
        }

        return File::put($fileName, $content.$append);
    }

    private function frontendAdjustments() {
        // webpack
        $this->strReplaceInFile(
            'webpack.mix.js',
            '|vendor/brackets/admin-translations|',
            '// Do not delete this comment, it\'s used for auto-generation :)',
            'path.resolve(__dirname, \'vendor/brackets/admin-translations/resources/assets/js\'),
				// Do not delete this comment, it\'s used for auto-generation :)');
        $this->info('Webpack configuration updated');

        // register translation assets
        $this->appendIfNotExists(resource_path('assets/admin/js/index.js'), '|import \'translation\'|', "\nimport 'translation';\n");
        $this->info('Admin Translation assets registered');

    }
}