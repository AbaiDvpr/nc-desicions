<?php declare(strict_types=1);
namespace App\Services\Payment;

use App\DI;
use App\Models\BalanceHistory;
use App\Models\User;
use App\Services\Payment\Exceptions\Available;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TODO: ничего не писать тут нужно все перенести в репозиторий
 */
class PaymentService
{

    /**
     * Код ответа об успешной транзакции
     */
    const SUCCESS_CODE = 200;

    /**
     * Код ответа с ошибкой
     */
    const ERROR_CODE = 401;

    /**
     * Код ошибки "баланс не может быть ниже 0"
     */
    const ERROR_CODE_402 = 402;

    /**
     * Модель пользователя для которого начинается работа с балансом
     *
     * @var User
     */
    private $user;

    /**
     * Текущий баланс пользователя
     *
     * @var float
     */
    private $balance;

    /**
     * PaymentService constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user    = $user;
        $this->balance = $user->getBalance();
    }

    /**
     * Изменение баланса
     *
     * @param  float       $balance
     * @param  string|null $comment
     * @param  bool        $isUp
     * @return array
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function changeBalance(float $balance = 0, string $comment = null, bool $isUp = true): array
    {
        $user = $this->user;

        try {
            $changeBalance = $isUp ? $this->upBalance($balance) : $this->downBalance($balance);

            if ($changeBalance['code'] !== self::SUCCESS_CODE) {
                return $changeBalance;
            }

            $user->balance = $changeBalance['balance'];

            DB::transaction(function () use ($user, $balance, $changeBalance, $comment) {
                if ($user->save()) {
                    $history = [
                        'operation'  => $changeBalance['operation'],
                        'paid'       => $balance,
                        'residue'    => $changeBalance['balance'],
                        'paid_at'    => Carbon::now(),
                        'comment'    => $comment,
                        'created_id' => auth()->id() ?? $user->id,
                    ];

                    $save = $user->balanceHistories()->save(new BalanceHistory($history));
                    if (!$save) {
                        Log::error('Ошибка при записи баланса в историю', $history);
                    }
                }
            }, 2);

            return [
                'code'    => self::SUCCESS_CODE,
                'message' => 'Изменение баланса прошло успешно'
            ];
        } catch (\Exception $exception) {
            DI::getAdminTelegram()->sendMessage([
                'chat_id'    => config('telegram.bots.admin.chat_id'),
                'parse_mode' => 'HTML',
                'text'       => view('notifications.telegram.payments.pay-notify-error', [
                    'message' => $exception->getMessage(),
                    'id'      => $user->getCabinetId(),
                    'name'    => $user->getName(),
                    'balance' => $balance,
                ])->render(),
            ]);

            Log::error($exception->getMessage(), [$exception->getTrace()]);
        }

        return [
            'code'    => self::ERROR_CODE,
            'message' => 'Ошибка при изменении баланса.'
        ];
    }

    /**
     * Увеличиваем баланс
     *
     * @param  float $balance
     * @return array
     */
    protected function upBalance(float $balance = 0): array
    {
        return [
            'code'      => self::SUCCESS_CODE,
            'operation' => BalanceHistory::OPERATION_REFILL,
            'balance'   => $this->balance + $balance
        ];
    }

    /**
     * Уменьшаем баланс с проверкой, чтоб баланс не был ниже 0
     *
     * @param  float $balance
     * @return array
     */
    protected function downBalance(float $balance = 0): array
    {
        $newBalance = $this->balance - $balance;

        if ($newBalance < 0) {
            return [
                'code'    => self::ERROR_CODE_402,
                'message' => 'Баланс после списания не может быть ниже 0',
            ];
        }

        return [
            'code'      => self::SUCCESS_CODE,
            'operation' => BalanceHistory::OPERATION_CUT,
            'balance'   => $newBalance,
        ];
    }

    /**
     * Проверяет достаточно ли денег на балансе для списания
     *
     * @param  float $sum
     * @return bool
     */
    public function checkAvailableBalance(float $sum): bool
    {
        return $this->user->getBalance() >= $sum;
    }

    public function pay(float $sum)
    {
        if (!$this->checkAvailableBalance($sum)) {
            throw new Available();
        }

    }
}
