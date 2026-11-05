<?php declare(strict_types=1);

namespace App\Console\Commands\Api;

use App\DI;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Notifications\Indicators\Error as NotifyError;
use App\Notifications\Indicators\Success as NotifySuccess;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Console\Command\Command as CommandBase;
use voku\helper\HtmlDomParser;

/**
 * Команда для получения показателей с НБРК
 */
class Indicators extends Command
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
    protected $signature = 'get:indicators';

    /**
     * @inheritdoc
     */
    protected $description = 'Получение показателей с НБРК';

    /**
     * Ключ для поиска в DOM дереве
     */
    const ANNUAL_INFLATION = 0;

    /**
     * Ключ для поиска в DOM дереве
     */
    const INFLATION = 1;

    /**
     * Ключ для поиска в DOM дереве
     */
    const BASE_RATE = 2;

    /**
     * Ключ для поиска в DOM дереве
     */
    const TONIA = 3;

    /**
     * Название ключей в таблице
     */
    const INDICATORS = [
        self::ANNUAL_INFLATION => 'annualInflation',
        self::INFLATION        => 'inflation',
        self::BASE_RATE        => 'baseRate',
        self::TONIA            => 'tonia',
    ];

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

        try {
            $dom = HtmlDomParser::str_get_html(file_get_contents(config('indicators.url')));
            $dom = $dom->findOneOrFalse('.intro-board');

            if (!$dom) {
                Notification::send($this->admins, new NotifyError());

                return CommandBase::SUCCESS;
            }

            $data = $this->getDataFromDOM($dom);

            if (!count($data)) {
                Notification::send($this->admins, new NotifyError());

                return CommandBase::SUCCESS;
            }

            $indicators = DI::getRepository()->getIndicators()->storeFromArray($data);

            if ($indicators) {
                Notification::send($this->admins, new NotifySuccess());

                return CommandBase::SUCCESS;
            }
        } catch (\Throwable $throwable) {
            Notification::send($this->admins, new NotifyError());

            Log::error('[Indicators] Ошибка при получении показателей', [
                'exception' => $throwable
            ]);
        }

        return CommandBase::SUCCESS;
    }

    /**
     * Возвращает данные из DOM дерева
     *
     * @param  $dom
     * @return array
     */
    protected function getDataFromDOM($dom): array
    {
        $data = [];

        foreach ($dom->find('.intro-board__item') as $key => $el) {
            $valueEl = $el->findOneOrFalse('.intro-board__title');

            if (!$valueEl) {
                continue;
            }

            $value = trim(strip_tags($valueEl->innerText()));
            $value = str_replace('%', '', $value);

            if ($key === self::INFLATION) {
                $value = last(explode('-', $value));
            }

            if (array_key_exists($key, self::INDICATORS)) {
                $data[self::INDICATORS[$key]] = (float) $value;
            }
        }

        if (count($data) === count(self::INDICATORS)) {
            $data['relevant'] = Carbon::now()->format('Y-m-d');

            return $data;
        }

        return [];
    }
}
