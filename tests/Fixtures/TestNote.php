<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property string $title
 * @property int    $user_id
 *
 * @method static self create(array<string, mixed> $attributes)
 */
final class TestNote extends Model
{
    protected $table = 'notes';

    protected $guarded = [];

    public $timestamps = false;
}
