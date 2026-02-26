<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property string $name
 *
 * @method static self create(array<string, mixed> $attributes)
 */
final class TestCategory extends Model
{
    protected $table = 'categories';

    protected $guarded = [];

    public $timestamps = false;
}
