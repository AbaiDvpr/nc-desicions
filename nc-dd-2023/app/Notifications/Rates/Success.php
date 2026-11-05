<?php declare(strict_types=1);

namespace App\Notifications\Rates;

use App\Models\Rates;
use Illuminate\Bus\Queueable;
use App\Notifications\AlertStatuses;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Класс для уведомления об успешном получении курсов валют
 */
class Success extends Notification
{
    use Queueable, AlertStatuses;

    /**
     * @var Rates
     */
    private $rates;

    /**
     * @param $rates
     */
    public function __construct($rates)
    {
        $this->rates = $rates;
    }

    /**
     * Get the notification’s delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Курс успешно получен')
            ->markdown('mail.get_rates_success', [
                'rates' => $this->rates
        ]);
    }


    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        return [
            'alert' => $this->getAlertSuccess(),
            'message' => 'Курсы успешно получены',
            'context' => $this->rates
        ];
    }
}
