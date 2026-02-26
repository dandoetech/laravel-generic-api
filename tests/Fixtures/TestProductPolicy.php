<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;

final class TestProductPolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, TestProduct $product): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return true;
    }

    public function update(?Authenticatable $user, TestProduct $product): bool
    {
        return true;
    }

    public function delete(?Authenticatable $user, TestProduct $product): bool
    {
        return false; // deny by default for testing
    }
}
