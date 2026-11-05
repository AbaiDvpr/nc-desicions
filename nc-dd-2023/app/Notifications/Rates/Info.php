<?php declare(strict_types=1);

namespace App\Notifications\Rates;

use App\Notifications\AlertStatuses;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Класс для информационного уведомления о получении курсов валют
 */
class Info extends Notification
{
    use Queueable, AlertStatuses;

    private $rates;

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
            ->subject('Курс на данную дату уже получен')
            ->markdown('mail.get_rates_error', [
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
            'alert' => $this->getAlertInfo(),
            'message' => 'Курс на данную дату уже получен',
            'context' => $this->rates
        ];
    }
}
