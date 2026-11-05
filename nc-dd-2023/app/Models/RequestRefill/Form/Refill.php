<?php declare(strict_types=1);

namespace App\Models\RequestRefill\Form;

use App\DI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Объект формы заполненный полями
 */
class Refill
{
    /**
     * ФИО указанный в форме
     *
     * @var string
     */
    protected string $fullName;

    /**
     * Телефон указанный в форме
     *
     * @var string
     */
    protected string $phone;

    /**
     * Комментарий указанное в форме
     *
     * @var string|null
     */
    protected ?string $comment;

    /**
     * ID пользователя, который отправляет запрос на пополнение счёта
     *
     * @var int
     */
    protected int $userId;

    /**
     * Заполняет свойства данными из запроса
     *
     * @param  \Illuminate\Http\Request              $request
     * @return \App\Models\RequestRefill\Form\Refill
     */
    public static function createFromRequest(Request $request): Refill
    {
        $form = new self();

        $form->fullName = (string) $request->get('fullName');
        $form->phone    = sanitizePhone($request->get('phone'));
        $form->comment  = $request->get('comment');

        return $form;
    }

    /**
     * Возвращает ID пользователя
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Устанавливает ID пользователя
     *
     * @param  int  $id
     * @return void
     */
    public function setUserId(int $id): void
    {
        $this->userId = $id;
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }


    public function toArray(): array
    {
        return [
            'userId'   => $this->getUserId(),
            'fullName' => $this->getFullName(),
            'phone'    => $this->getPhone(),
            'comment'  => $this->getComment(),
        ];
    }

    /**
     * Сохраняет форму и отправляет уведомление менеджеру
     *
     * @return bool
     */
    public function save(): bool
    {
        $refill = DI::getRepository()->getRequestRefill()->create($this->toArray());

        if (is_null($refill)) {
            return false;
        }

        try {
            DI::getAdminTelegram()->sendMessage([
                'chat_id'    => config('telegram.bots.admin.payments'),
                'parse_mode' => 'HTML',
                'text'       => view('notifications.telegram.refill.message', [
                    'refill' => $refill,
                ])->render(),
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Ошибка при отправке уведомления в телеграм', [
                'exception' => $throwable,
                'form' => $this->toArray(),
            ]);

            return false;
        }

        return true;
    }
}
