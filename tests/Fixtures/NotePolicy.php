<?php

declare(strict_types=1);

namespace DanDoeTech\LaravelGenericApi\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;

final class NotePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, TestNote $note): bool
    {
        if ($user === null) {
            return false;
        }

        return $note->user_id === $this->userId($user);
    }

    public function create(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function update(?Authenticatable $user, TestNote $note): bool
    {
        if ($user === null) {
            return false;
        }

        return $note->user_id === $this->userId($user);
    }

    public function delete(?Authenticatable $user, TestNote $note): bool
    {
        if ($user === null) {
            return false;
        }

        return $note->user_id === $this->userId($user);
    }

    private function userId(Authenticatable $user): int
    {
        /** @var int|string $id */
        $id = $user->getAuthIdentifier();

        return (int) $id;
    }
}
