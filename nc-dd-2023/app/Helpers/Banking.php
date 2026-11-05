<?php declare(strict_types=1);
namespace App\Helpers;

/**
 * Хэлпер для работы с банковской информацией
 */
class Banking
{
    /**
     * Длина ИИК
     *
     * @const int
     */
    const IBAN_LENGTH = 20;

    /**
     * Постоянный код Казахстана
     *
     * @const string
     */
    const KZ_CODE = 'KZ';

    /**
     * Список банковских идентификационных кодов (БИКов) Республики Казахстан
     *
     * @link http://www.nationalbank.kz/?docid=648&switch=russian
     * @const array
     */
    const SECOND_TIER_BANKS = [
        'ABNAKZKX' => '922',
        'ALFAKZKA' => '947',
        'ALMNKZKA' => '826',
        'ASFBKZKA' => '450',
        'ATYNKZKA' => '949',
        'BKCHKZKA' => '913',
        'CASPKZKA' => '722',
        'CEDUKZKA' => '766',
        'CITIKZKA' => '832',
        'DVKAKZKA' => '907',
        'EABRKZKA' => '700',
        'EURIKZKA' => '948',
        'EXKAKZKA' => '942',
        'GCVPKZ2A' => '009',
        'HCSKKZKA' => '972',
        'HLALKZKZ' => '246',
        'HSBKKZKX' => '601',
        'ICBKKZKX' => '930',
        'INLMKZKA' => '886',
        'IRTYKZKA' => '965',
        'KCJBKZKX' => '856',
        'KICEKZKX' => '927',
        'KINCKZKA' => '821',
        'KISCKZKX' => '224',
        'KKMFKZ2A' => '070',
        'KPSTKZKA' => '563',
        'KSNVKZKA' => '551',
        'KZIBKZKA' => '885',
        'KZKOKZKX' => '926',
        'LARIKZKA' => '774',
        'NBPAKZKA' => '553',
        'NBPFKZKX' => '147',
        'NBRKKZKX' => '125',
        'NURSKZKX' => '849',
        'SABRKZKA' => '914',
        'SENIKZKA' => '549',
        'SHBKKZKA' => '435',
        'TBKBKZKA' => '781',
        'TNGRKZKX' => '620',
        'TSESKZKA' => '998',
        'VTBAKZKZ' => '432',
        'ZAJSKZ22' => '896',
        'BRKEKZKA' => '914',
    ];

    /**
     * Проверка постоянного кода Казахстана
     *
     * @param  string $iban
     * @return bool
     */
    public static function checkIbanCountryCode(string $iban): bool
    {
        return !empty($iban) && substr($iban, 0, 2) === self::KZ_CODE;
    }

    /**
     * Проверка постоянного кода Казахстана
     *
     * @param  string $iban
     * @return bool
     */
    public static function checkBankCode(string $iban): bool
    {
        if (!empty($iban)) {
            $bankCode = substr($iban, 4, 3);

            return in_array($bankCode, self::SECOND_TIER_BANKS);
        }

        return false;
    }

    /**
     * Проверка постоянного кода Казахстана
     *
     * @param  string $bik
     * @return bool
     */
    public static function checkBikCountryCode(string $bik): bool
    {
        return !empty($bik) && substr($bik, 4, 2) === self::KZ_CODE;
    }

    /**
     * Проверка БИКа на наличие в списке валидных
     *
     * @param  string $bik
     * @return bool
     */
    public static function checkBik(string $bik): bool
    {
        return !empty($bik) && array_key_exists($bik, self::SECOND_TIER_BANKS);
    }
}
