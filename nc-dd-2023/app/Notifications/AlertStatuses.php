<?php declare(strict_types=1);

namespace App\Notifications;

/**
 *  Трейт со статусами алертов
 */
trait AlertStatuses
{
    /**
     * Get alert success status
     *
     * @return string
     */
    public function getAlertSuccess (): string
    {
        return 'success';
    }

    /**
     * Get alert error status
     *
     * @return string
     */
    public function getAlertError (): string
    {
        return 'danger';
    }

    /**
     * Get alert info status
     *
     * @return string
     */
    public function getAlertInfo (): string
    {
        return 'info';
    }

    /**
     * Get alert warning status
     *
     * @return string
     */
    public function getAlertWarning (): string
    {
        return 'warning';
    }
}
