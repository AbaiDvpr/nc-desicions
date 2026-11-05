<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static findOrFail(int $param)
 * @method static whereRelevant(mixed $relevant)
 * @method static create(array $post)
 * @method static orderBy(string $string, string $string1)
 * @method static latest()
 */
class Indicator extends Model
{
    /**
     * @var string
     */
    protected $table = 'indicators';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];
}
