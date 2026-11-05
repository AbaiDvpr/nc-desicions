<?php declare(strict_types=1);

namespace Tests\Unit\DataHandlers;

use App\DI;
use PHPUnit\Framework\TestCase;

/**
 * Класс для тестирования DataHandler-a проекта
 */
class ProjectTest extends TestCase
{
    /**
     * Тест на проверку метода который суммирует значения по столбцам массива
     *
     * @dataProvider sumColumnProvider
     *
     * @param  array $asserts
     * @param  array $equals
     * @return void
     */
    public function testGetSumColumns(array $asserts, array $equals): void
    {
        $result = DI::getDH()->getProject()->getSumColumns($asserts);

        $this->assertStringContainsString(json_encode($result), json_encode($equals));

        $this->assertTrue(true);
    }

    /**
     * Провайдер для суммирования значений по столбцам массива
     *
     * @return array[]
     */
    public static function sumColumnProvider(): array
    {
        return [
            [
                'asserts' => [
                    [1, 2, 3, 4, 5],
                    [1, 2, 3, 4, 5],
                    [1, 2, 3, 4, 5]
                ],
                'equals'  => [3, 6, 9, 12, 15]
            ],
            [
                'asserts' => [
                    [1, 5, 3, 4, 5],
                    [1, 2, 3, 8, 5],
                    [1, 2, 3, 4, 5]
                ],
                'equals'  => [3, 9, 9, 16, 15]
            ],
            [
                'asserts' => [
                    [1, 5, 3, 4, 5],
                    [1, 0, 3, 4, 5],
                    [0, 2, 3, 11, 5]
                ],
                'equals'  => [2, 7, 9, 19, 15]
            ],
            [
                'asserts' => [
                    [1, 5, 3, 4, 5],
                    [1, 0, 3, 4, 5],
                ],
                'equals'  => [2, 5, 6, 8, 10]
            ],
            [
                'asserts' => [
                    [1, 5, null, 4, 5],
                    [1, 0, 3, true, 5],
                ],
                'equals'  => [2, 5, 3, 4, 10]
            ],
            [
                'asserts' => [
                    [1, 5, null, 4, 5],
                    [1, 0, 'test', true, 5],
                ],
                'equals'  => [2, 5, 0, 4, 10]
            ]
        ];
    }
}
