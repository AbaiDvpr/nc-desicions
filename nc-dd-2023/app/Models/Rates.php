<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static findOrFail(int $param)
 * @method static whereRelevant(mixed $relevant)
 * @method static create(array $post)
 * @method static orderBy(string $string, string $string1)
 * @method static latest()
 */
class Rates extends Model
{
    use HasFactory;

    protected $table = 'rates';

    protected $guarded = [];
}
