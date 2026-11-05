<?php declare(strict_types=1);

namespace App\Repositories\Cabinet;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProjectRepository
{
    public function create(array $attributes)
    {
        return Project::create($attributes);
    }

    /**
     * @param array $findAttributes
     * @param array $otherAttributes
     * @return mixed
     */
    public function firstOrCreate(array $findAttributes, array $otherAttributes)
    {
        return Project::firstOrCreate(
            $findAttributes,
            $otherAttributes
        );
    }

    /**
     * @param int $uuid
     * @return Model|object|null
     */
    public function findByUuid(int $uuid)
    {
        return Project::query()->where('uuid', $uuid)->first();
    }

    /**
     * Получить список проектов пользователя
     *
     * @param \App\Repositories\User $user
     * @return Builder
     */
    public function getByUser(User $user): Builder
    {
        return Project::where('user_id', $user->getAuthIdentifier());
    }
}
