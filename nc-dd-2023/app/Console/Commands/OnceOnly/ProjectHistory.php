<?php declare(strict_types=1);

namespace App\Console\Commands\OnceOnly;

use App\DI;
use App\Models\Project;
use Illuminate\Console\Command;

/**
 * Консольный класс для создания историй фин моделей
 *
 * php artisan projectHistory:generate
 */
class ProjectHistory extends Command
{
    /**
     * @var string
     */
    protected $signature = 'projectHistory:generate';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * Обработчик
     *
     * @return void
     */
    public function handle(): void
    {
        (new Project())
            ->orderBy('created_at')
            ->chunk(1000, function($projects) {
                $bar = $this->output->createProgressBar($projects->count());

                $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% ');
                $bar->setBarCharacter('<comment>=</comment>');
                $bar->setEmptyBarCharacter('-');
                $bar->setProgressCharacter('>');
                $bar->setBarWidth(100);

                foreach ($projects as $project) {
                    /** @var \App\Models\Project $project */
                    DI::getRepository()->getProject()->getHistory()->create(
                        DI::getRepository()->getUser()->findById($project->getUserId()),
                        $project
                    );

                    $bar->advance();
                }

                $bar->finish();

                echo "\n";
            });
    }
}
