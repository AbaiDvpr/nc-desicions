<?php declare(strict_types=1);
namespace Helpers;

use App\Helpers\Banking;

/**
 * Класс для данных для оплаты/пополнения
 */
class PaymentData
{
    /**
     * Номер тестовой карты для пополнения
     */
    public const TEST_CARD_NUMBER = '4242424242424242';

    /**
     * Возвращает случайный БИН из списка
     *
     * @return string
     */
    public static function getBin(): string
    {
        $binList = [
            "980740000057",
            "920140000508",
            "920140000143",
            "940140000513",
            "990740000683",
            "971240001315",
            "950140000367",
            "950740000130",
            "941240000341",
            "951140000151",
            "920440001102",
            "080540014021",
            "090740019001",
            "080940010300",
            "010540001007",
            "980640000093",
            "950240000112",
        ];

        return $binList[array_rand($binList)];
    }

    /**
     * Возвращает случайный ИИК из списка
     *
     * @return string
     */
    public static function getIik(): string
    {
        $iikList = [
            "KZ97125KZT1001300327",
            "KZ09125KZT1001300262",
            "KZ867812860193980027",
            "KZ74922KZT0002068974",
            "KZ23125KZT1001300204",
            "KZ69125KZT1001300249",
            "KZ21125KZT1002300310",
            "KZ46125KZT1001300275",
            "KZ71125KZT1001300213",
            "KZ87125KZT1001300216",
            "KZ57125KZT1002300244",
            "KZ81125KZT1001300615",
            "KZ23125KZT1001300883",
            "KZ06125KZT1001302062",
            "KZ32907A287000000003",
        ];

        return $iikList[array_rand($iikList)];
    }

    /**
     * Возвращает случайный ИИК из списка
     *
     * @return string
     */
    public static function getBik(): string
    {
        $iikList = array_keys(Banking::SECOND_TIER_BANKS);

        return $iikList[array_rand($iikList)];
    }
}
