<?php

use App\Http\Controllers\Frontend\Cabinet\Projects\Form\ProjectForm;
use Illuminate\Support\Str;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

if (!function_exists('isFrontend')) {
    /**
     * Check if backend
     * @return bool
     */
    function isFrontend(): bool
    {
        $host = request()->getHost();

        if (\Illuminate\Support\Str::startsWith($host, 'www.')) {
            $host = preg_replace('/^www./', '', $host);
        }

        return $host === config('app.domain');
    }
}

if (!function_exists('isAdminProject')) {
    /**
     * Проверяет, запущено ли приложение в режиме admin
     *
     * @return bool
     */
    function isAdminProject(): bool
    {
        return config('app.project') === 'admin';
    }
}

if (!function_exists('getProjectFormValue')) {
    /**
     * Получить название элемента формы сохраненного в базу
     *
     * @param string $element
     * @param $value
     * @return mixed|null
     */
    function getProjectFormValue(string $element, $value)
    {
        return (new ProjectForm())->getElementValue($element, $value);
    }
}

if (! function_exists('str_limit')) {
    /**
     * Limit the number of characters in a string.
     *
     * @param  string $value
     * @param  int    $limit
     * @param  string $end
     * @return string
     */
    function str_limit(string $value, int $limit = 100, string $end = '...'): string
    {
        return Str::limit($value, $limit, $end);
    }
}

if (! function_exists('sanitizePhone')) {
    /**
     * Очищает номер телефона от лишних символов
     *
     * @param  string $value
     * @return string
     */
    function sanitizePhone(string $value): string
    {
        $value = str_replace('-', '', $value);
        $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        $value = trim($value, '+');

        if (substr($value, 0, 1) == 8) {
            $value = substr_replace($value, '7', 0, 1);
        }

        return $value;
    }
}

if (!function_exists('formatPhone')) {
    /**
     * Форматирует номер телефона в нужный формат
     *
     * @param string $phone
     * @param string $country Регион по умолчанию
     * @param int    $format
     * @return string
     */
    function formatPhone(string $phone, string $country = 'KZ', int $format = PhoneNumberFormat::INTERNATIONAL): string
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            // Добавляем + если нет перед телефоном
            if ($phone && $phone[0] !== '+') {
                $phone = '+' . $phone;
            }

            $proto = $phoneUtil->parse($phone, $country);

            return $phoneUtil->format($proto, $format);
        } catch (NumberParseException $e) {
            // ничего не делаем
        }

        return $phone;
    }
}

if (!function_exists('cleanPhone')) {
    /**
     * Очищает номер телефона оставляю только цифры
     *
     * @param  string $phone
     * @return string
     */
    function cleanPhone(string $phone): string
    {
        return preg_replace('|[^0-9]|', '', formatPhone($phone));
    }
}

if (!function_exists('formatDate')) {
    /**
     * Форматирует дату в нужный формат
     *
     * @param  string $date
     * @param  string $format
     * @return string
     */
    function formatDate(string $date = '', string $format = 'd M, Y'): string
    {
        $date = date($format, strtotime($date));

        foreach (config('months.in.' . app()->getLocale()) as $k => $v) {
            $date = str_replace($k, $v, $date);
        }

        return $date;
    }
}

if (!function_exists('icon')) {
    /**
     * Форматирует svg иконку
     *
     * @param  string $name
     * @param  string $mod
     * @return string
     */
    function icon(string $name, string $mod = ''): string
    {
        return '
            <svg class="icon icon--' . $name . ' ' . $mod . '">
                <use xlink:href="/static/images/sprites/site.svg#' . $name . '"></use>
            </svg>
        ';
    }
}

if (!function_exists('getIDYoutubeVideo')) {
    /**
     * Возвращает код youtube ролика
     *
     * @param  $url
     * @return string
     */
    function getIDYoutubeVideo($url): string
    {
        $values = '';

        if (preg_match("/(http|https):\/\/(www.youtube|youtube|youtu)\.(be|com)\/([^<\s]*)/", $url, $match)) {
            if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $url, $id)) {
                $values = $id[1];
            } else if (preg_match('/youtube\.com\/embed\/([^\&\?\/]+)/', $url, $id)) {
                $values = $id[1];
            } else if (preg_match('/youtube\.com\/v\/([^\&\?\/]+)/', $url, $id)) {
                $values = $id[1];
            } else if (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $id)) {
                $values = $id[1];
            } else if (preg_match('/youtube\.com\/verify_age\?next_url=\/watch%3Fv%3D([^\&\?\/]+)/', $url, $id)) {
                $values = $id[1];
            }
        }

        return $values;
    }
}
