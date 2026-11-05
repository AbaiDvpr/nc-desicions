<?php declare(strict_types=1);

namespace App\Jobs\Frontend\Registration;

use App\DI;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Отправляет письмо для завершения регистрации
 */
class RegisterVerificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * ID пользователя, которому нужно отправить письмо
     *
     * @var int
     */
    protected int $id;

    /**
     * Create a new job instance.
     *
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            if ($this->id > 0) {
                $user = DI::getRepository()->getUser()->findById($this->id);

                if ($user) {
                    Mail::send('mail.frontend.registration.registration', ['user' => $user], function ($message) use ($user) {
                        $message->from(config('mail.from.address'), config('mail.from.name'));
                        $message->subject('Регистрация - на ncdecision.com');
                        $message->to($user->email);
                    });

                    return;
                }
            }
        } catch (\Throwable $throwable) {
            Log::error('Ошибка во время отправки письма подтверждения регистрации', [
                'exception' => $throwable
            ]);
        }
    }
}
