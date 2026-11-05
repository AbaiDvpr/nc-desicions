<?php declare(strict_types=1);

namespace App\Jobs\Frontend\Faq;

use App\Models\Faq;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FaqAdminSend implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * id записи из таблицы faq
     * @var $id
     */
    protected $id;

    /**
     * Create a new job instance.
     *
     * @param null $id
     */
    public function __construct($id = 0)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if ($this->id > 0) {
                $faq = Faq::findOrFail($this->id);

                if ($faq) {
                    Mail::send('mail.frontend.faq.admin', ['faq' => $faq], function ($message) use ($faq) {
                        $message->from(config('mail.from.address'), config('mail.from.name'));
                        $message->subject('Уведомление о новом вопросе');
                        $message->to(config('mail.email_admin'));
                    });

                    return;
                }
            }
        } catch (QueryException $exception) {
            Log::error(
                'Ошибка при выполнении консольной задачи: ' . $exception->getCode() . ': ' . $exception->getMessage()
            );
        }
    }
}
