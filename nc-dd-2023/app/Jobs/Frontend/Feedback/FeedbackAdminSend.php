<?php declare(strict_types=1);

namespace App\Jobs\Frontend\Feedback;

use App\DI;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Джоба для работы с формой Обратной связи
 */
class FeedbackAdminSend
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * ID записи
     *
     * @var int
     */
    private int $id;

    /**
     * Create a new job instance.
     *
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function handle(): void
    {
        try {
            $feedback = DI::getRepository()->getModelsFeedback()->getOne($this->id);

            if (is_null($feedback)) {
                throw new ModelNotFoundException('Заявка не найдена');
            }

            Mail::send('mail.frontend.feedback.admin', ['feedback' => $feedback], function ($message) use ($feedback) {
                $message->from(config('mail.from.address'), config('mail.from.name'));
                $message->subject('Уведомление о новой заявке на обратный звонок');
                $message->to(config('mail.email_admin'));
            });

            DI::getAdminTelegram()->sendMessage([
                'chat_id'    => config('telegram.bots.admin.feedbacks'),
                'parse_mode' => 'HTML',
                'text'       => view('notifications.telegram.feedback.message', compact('feedback'))->render(),
            ]);

            return;
        } catch (QueryException $exception) {
            Log::error('[Feedback] Ошибка при выполнении джобы на отправку письма: ', [
                'exception' => $exception
            ]);
        }
    }
}
