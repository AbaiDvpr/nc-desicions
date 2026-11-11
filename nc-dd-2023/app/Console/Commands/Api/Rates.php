<?php declare(strict_types=1);

namespace App\Console\Commands\Api;

use App\Models\User;
use App\Notifications\Rates\Error as NotifyError;
use App\Notifications\Rates\Info as NotifyInfo;
use App\Notifications\Rates\Success as NotifySuccess;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Models\Rates as RatesModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Console\Command\Command as CommandBase;

/**
 * Команда для получения курсов валют
 */
class Rates extends Command
{
    /**
     * Пользователи с доступом в админ панель, чтоб рассылать уведомления
     *
     * @var \App\Models\User[]
     */
    protected $admins;

    /**
     * @inheritdoc
     */
    protected $signature = 'get:rates';

    /**
     * @inheritdoc
     */
    protected $description = 'Получение курсов валют на следующий день';

    /**
     * @inheritdoc
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->admins = User::whereAdmin(true)->get();

        $config = config('rates');

        try {
            $date    = Carbon::now()->addDay();
            $xml     = simplexml_load_string(file_get_contents(sprintf(
                '%s?fdate=%s',
                $config['path'],
                $date->format('d.m.Y')
            )));
            $content = json_decode(json_encode($xml), TRUE);

            // Если на выбранную дату нет курсов
            if (array_key_exists('info', $content)) {
                Notification::send($this->admins, new NotifyError(
                    'Ошибка нет курсов валют'
                ));

                return CommandBase::SUCCESS;
            }

            $isExists = RatesModel::whereRelevant($date->format('Y-m-d'))->exists();
            if ($isExists) {
                return CommandBase::SUCCESS;
            }

            $rates           = new RatesModel;
            $rates->relevant = $date->format('Y-m-d');

            collect($content['item'])->whereIn('title', $config['supportedRates'])->map(function ($item) use ($rates) {
                $rates->{'amount_' . $item['title']} = (float)$item['description'];
                $rates->{'unit_' . $item['title']}   = $item['quant'];
            });

            if ($rates->save()) {
                Notification::send($this->admins, new NotifySuccess($rates));

                return CommandBase::SUCCESS;
            }

            Notification::send($this->admins, new NotifyInfo(
                'Курс на данную дату уже получен'
            ));
        } catch (\Throwable $throwable) {
            Notification::send($this->admins, new NotifyError($throwable->getMessage()));

            Log::error('[Rates] Ошибка при получении курсов валю', [
                'exception' => $throwable
            ]);
        }

        return CommandBase::SUCCESS;
    }
}
