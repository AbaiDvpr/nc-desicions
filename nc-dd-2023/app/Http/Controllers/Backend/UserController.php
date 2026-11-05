<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Requests\Backend\Users\StoreRequest;
use App\Http\Requests\Backend\Users\UpdateRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

/**
 * Контроллер для работы с пользователями
 */
class UserController extends BaseController
{
    /**
     * Формирует страницу со списком пользователей
     *
     * @param  \App\Models\User                $users
     * @param  \Illuminate\Http\Request        $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(User $users, Request $request): View
    {
        $users = $this->getUsers($request, $users->newQuery());

        return view('backend.users.index', [
            'users' => $users->with(['roles'])->paginate(30)
        ]);
    }

    /**
     * Форма добавления нового пользователя
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create(): View
    {
        return view('backend.users.create', [
            'roles' => Role::pluck('name', 'name')->all()
        ]);
    }

    /**
     * Сохранение нового пользователя
     *
     * @param  \App\Http\Requests\Backend\Users\StoreRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $user = User::create(
                Arr::collapse([
                    $request->only(['name', 'admin', 'email', 'mobile', 'password']), [
                        'email_verified_at' => now()
                    ],
                ])
            );

            // Можно передать несколько ролей
            $user->assignRole($request->input('roles'));

            return redirect()->route('backend.users.index')->with([
                'success' => 'Пользователь успешно добавлен'
            ]);
        } catch (\Throwable $throwable) {
            Log::error('[Users] ошибка создания пользователя', [
                'exception' => $throwable
            ]);
        }

        return redirect()->back()->withErrors([
            'Произошла непредвиденная ошибка'
        ]);
    }

    /**
     * Страница редактирования пользователя
     *
     * @param  int                             $id
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(int $id): View
    {
        $user  = User::findOrFail($id);
        $roles = Role::pluck('name', 'name')->all();

        return view('backend.users.edit', compact('user', 'roles'));
    }

    /**
     * Обновление данных пользователя
     *
     * @param  \App\Http\Requests\Backend\Users\UpdateRequest $request
     * @param  int                                            $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        try {
            $post = $request->only(['name', 'admin', 'email', 'mobile', 'password']);

            if (is_null($post['password'])) {
                unset($post['password']);
            }

            // Можно передать несколько ролей
            $user->syncRoles($request->input('roles'));

            $user->update($post);

            Cache::forget('profile-avatar:' . $user->getKey());

            return redirect()->route('backend.users.index')->with([
                'success' => 'Пользователь успешно изменен'
            ]);
        } catch (\Throwable $throwable) {
            Log::error('[Users] ошибка создания пользователя', [
                'exception' => $throwable
            ]);
        }

        return redirect()->back()->withErrors([
            'Произошла непредвиденная ошибка'
        ]);
    }

    /**
     * Удаление пользователя
     *
     * @param  int                               $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        try {
            // Удаляем роли прикрепленные за пользователем
            $user->assignRole([]);

            $user->delete();

            return redirect()->back()->with([
                'success' => 'Пользователь успешно удален'
            ]);
        } catch (\Throwable $throwable) {
            Log::error('[Users] ошибка создания пользователя', [
                'exception' => $throwable
            ]);
        }

        return redirect()->back()->withErrors([
            'Произошла непредвиденная ошибка'
        ]);
    }

    /**
     * Возвращает запрос на получение списка пользователей
     *
     * @param  \Illuminate\Http\Request              $request
     * @param  \Illuminate\Database\Eloquent\Builder $users
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getUsers(Request $request, Builder $users): Builder
    {
        if ($request->input('name')) {
            $users = $users->where('name', 'LIKE', '%' . $request->input('name') . '%');
        }

        if ($request->input('email')) {
            $users = $users->where('email', $request->input('email'));
        }

        if (in_array($request->input('admin'), ["0", "1"])) {
            $users = $users->where('admin', $request->input('admin'));
        }

        if (in_array($request->input('activated'), ["0", "1"])) {
            if ($request->input('activated') === "1") {
                $users = $users->whereNotNull("email_verified_at");
            } else {
                $users = $users->whereNull("email_verified_at");
            }
        }

        if ($request->input('fromDate') || $request->input('toDate')) {
            if ($request->input('fromDate')) {
                $users = $users->where('created_at', '>=', Carbon::parse($request->input('fromDate'))->format('Y-m-d 00:00:00'));
            }

            if ($request->input('toDate')) {
                $users = $users->where('created_at', '<=', Carbon::parse($request->input('toDate'))->format('Y-m-d 23:59:59'));
            }
        }

        return $users->role(
            Role::where('name', '!=', 'client')->get()
        );
    }
}
