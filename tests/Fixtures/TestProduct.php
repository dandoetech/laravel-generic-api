<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int    $id
 * @property string $name
 * @property float  $price
 * @property int    $category_id
 *
 * @method static self create(array<string, mixed> $attributes)
 */
final class TestProduct extends Model
{
    protected $table = 'products';

    protected $guarded = [];

    public $timestamps = true;

    /** @return BelongsTo<TestCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TestCategory::class, 'category_id');
    }
}
