<?php declare(strict_types=1);

namespace App\Console\Commands\OnceOnly;

use App\DI;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Консольный класс для создания пользовательских тарифов
 *
 * php artisan userTariff:generate
 */
class UserTariff extends Command
{
    /**
     * @var string
     */
    protected $signature = 'userTariff:generate';

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
        (new User())
            ->role(User::ROLE_CLIENT)
            ->whereNotNull('email_verified_at')
            ->orderBy('id')
            ->chunk(1000, function($users) {
                $bar = $this->output->createProgressBar($users->count());

                $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% ');
                $bar->setBarCharacter('<comment>=</comment>');
                $bar->setEmptyBarCharacter('-');
                $bar->setProgressCharacter('>');
                $bar->setBarWidth(100);

                foreach ($users as $user) {
                    /** @var \App\Models\User $user */
                    $user->applyTariff(
                        DI::getRepository()->getModelsTariff()->getOne($user->getTariffId())
                    );

                    $bar->advance();
                }

                $bar->finish();

                echo "\n";
            });
    }
}
