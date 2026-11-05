<?php

namespace App\Notifications\Rates;

use App\Notifications\AlertStatuses;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Класс для уведомления об ошибке получения курсов валют
 */
class Error extends Notification
{
    use Queueable, AlertStatuses;

    protected $rates;

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
     * @param  mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed       $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Курс валют не получен')
            ->markdown('mail.cli.rates.error', [
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
            'alert'   => $this->getAlertError(),
            'message' => 'Курс валют не получен',
            'context' => $this->rates
        ];
    }
}
