<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 * @method static first()
 * @method static findOrFail(int $param)
 * @method static findById(int|null $id)
 * @method static whereMain(bool $true)
 * @method static find(int $id)
 * @method static wherePublished(bool $true)
 * @method static whereLang(string $getLocale)
 */
class Faq extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'faq';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];
}
