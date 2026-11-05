<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 * @method static first()
 * @method static findOrFail(int $param)
 */
class Seo extends Model
{
    use HasFactory, HasTranslations;

    /**
     * @var string
     */
    protected $table = 'seo';

    /**
     * @inheritdoc
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Field for translatable
     *
     * @var string[]
     */
    public $translatable = ['title', 'description', 'keywords'];
}
