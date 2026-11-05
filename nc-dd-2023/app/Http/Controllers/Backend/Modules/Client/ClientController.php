<?php declare(strict_types=1);
namespace App\Http\Controllers\Backend\Modules\Client;

use App\DI;
use App\Http\Controllers\Backend\BaseController;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Backend\Modules\Client\ClientStoreRequest;
use App\Http\Requests\Backend\Modules\Client\ClientUpdateRequest;
use Illuminate\Support\Facades\URL;

/**
 * Контроллер для работы с клиентом
 */
class ClientController extends BaseController
{
    /**
     * Builder списка клиентов
     *
     * @var Builder
     */
    private Builder $users;

    /**
     * Объект запроса
     *
     * @var Request
     */
    private Request $request;

    /**
     * ClientController constructor.
     *
     * @param User    $users
     * @param Request $request
     */
    public function __construct(User $users, Request $request)
    {
        parent::__construct();

        $this->request = $request;
        $this->users   = $this->getUsers($users);
    }

    /**
     * Генерация страницы со списком пользователей
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        return view('backend.modules.client.index', [
            'users' => $this->users->with(['roles'])->latest()->paginate(30)
        ]);
    }
    /**
     * Получает пользователей с учетом выбранных фильтров
     *
     * @param  \App\Models\User                      $users
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getUsers(User $users): Builder
    {
        $users = $users->role('client');

        if ($this->request->input('name')) {
            $users = $users->where('name', 'LIKE', '%' . $this->request->input('name') . '%');
        }

        if ($this->request->input('email')) {
            $users = $users->where('email', $this->request->input('email'));
        }

        if (in_array($this->request->input('admin'), ["0", "1"])) {
            $users = $users->where('admin', $this->request->input('admin'));
        }

        if (in_array($this->request->input('activated'), ["0", "1"])) {
            if ($this->request->input('activated') === "1") {
                $users = $users->whereNotNull("email_verified_at");
            } else {
                $users = $users->whereNull("email_verified_at");
            }
        }

        if ($this->request->input('fromDate') || $this->request->input('toDate')) {
            if ($this->request->input('fromDate')) {
                $users = $users->where('created_at', '>=', Carbon::parse($this->request->input('fromDate'))->format('Y-m-d 00:00:00'));
            }

            if ($this->request->input('toDate')) {
                $users = $users->where('created_at', '<=', Carbon::parse($this->request->input('toDate'))->format('Y-m-d 23:59:59'));
            }
        }

        return $users;
    }

    /**
     * Страница создания клиента
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create(): View
    {
        return view('backend.modules.client.create', [
            'tariffs' => DI::getRepository()->getModelsTariff()->getLatestActiveForSelect()
        ]);
    }

    /**
     * Создает клиента
     *
     * @param  \App\Http\Requests\Backend\Modules\Client\ClientStoreRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ClientStoreRequest $request): RedirectResponse
    {
        $post     = $request->only(['name', 'email', 'mobile', 'birthday', 'password']);
        $post     = Arr::collapse([
            $post,
            [
                'driver'       => 'web',
                'fullAccess'   => (bool) $request->input('fullAccess'),
                'courseAccess' => (bool) $request->input('courseAccess'),
            ],
        ]);
        $userRepo = DI::getRepository()->getUser();

        try {
            $post['mobile'] = cleanPhone($post['mobile']);

            $user = $userRepo->create($post);

            $user->assignRole('client');

            $userRepo->update($user->getAuthIdentifier(), [
                'email_verified_at' => now(),
                'apiToken'          => $user->createToken('apiToken')->plainTextToken,
            ]);

            $user->applyTariff(DI::getRepository()->getModelsTariff()->getOne(
                (int) $request->input('tariff')
            ));

            DI::getRepository()->getUser()->clearCache($user);

            return redirect()->route('backend.modules.client.index')
                ->with(['success' => 'Пользователь успешно создан!']);
        } catch (\Throwable $throwable) {
            Log::error('[Users] Ошибка при создании нового пользователя', [
                'exception' => $throwable,
            ]);
        }

        return redirect()->back()->withErrors([
            'Произошла непредвиденная ошибка, пожалуйста попробуйте позднее'
        ]);
    }

    /**
     * Страница редактирования клиента
     *
     * @param  int                             $id
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(int $id): View
    {
        $user = DI::getRepository()->getUser()->findById($id);

        if (is_null($user)) {
           abort(404);
        }

        $tariffs = DI::getRepository()->getModelsTariff()->getLatestActiveForSelect();

        return view('backend.modules.client.edit', compact('user', 'tariffs'));
    }

    /**
     * Обновляет данные клиента
     *
     * @param  \App\Http\Requests\Backend\Modules\Client\ClientUpdateRequest $request
     * @param  int                                                           $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ClientUpdateRequest $request, int $id): RedirectResponse
    {
        try {
            $post = $request->only(['name', 'mobile', 'password', 'birthday']);
            $post = Arr::collapse([$post, [
                'fullAccess'   => (bool) $request->input('fullAccess'),
                'courseAccess' => (bool) $request->input('courseAccess'),
            ]]);

            if (is_null($post['password'])) {
                unset($post['password']);
            }

            $post['mobile'] = cleanPhone($post['mobile']);
            $user           = DI::getRepository()->getUser()->update($id, $post);

            if (is_null($user)) {
                return redirect()->back()->withErrors([
                    'Произошла непредвиденная ошибка',
                ]);
            }

            $tariffId = (int) $request->input('tariff');

            if ($tariffId && $user->tariff()?->getTariffId() !== $tariffId) {
                $user->applyTariff(DI::getRepository()->getModelsTariff()->getOne($tariffId));

                DI::getRepository()->getUser()->clearCache($user);
            }

            return redirect()->route('backend.modules.client.index')
                ->with(['success' => 'Данные клиента успешно обновлены!']);
        } catch (\Throwable $throwable) {
            Log::error('[Users] Ошибка обновления данных', [
                'exception' => $throwable,
            ]);
        }

        return redirect()->back()->withErrors([
            'Произошла непредвиденная ошибка, пожалуйста попробуйте позднее',
        ]);
    }

    /**
     * Очищает кеш клиента
     *
     * @param  int                              $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clearCache(int $id): RedirectResponse
    {
        $user = DI::getRepository()->getUser()->findById($id);

        if (is_null($user)) {
            return redirect()->back()->withErrors([
                'Произошла непредвиденная ошибка, пожалуйста попробуйте позднее',
            ]);
        }

        DI::getRepository()->getUser()->clearCache($user);

        return redirect()->route('backend.modules.client.index')
            ->with(['success' => 'Кеш клиента успешно очищен!']);
    }

    /**
     * Авторизация клиента в личном кабинете
     *
     * @param  int                              $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function auth(int $id): RedirectResponse
    {
        $url = URL::temporarySignedRoute(
            'frontend.auth.login.signature',
            5,
            ['user' => $id],
            false
        );

        return redirect(config('app.url') . $url);
    }
}
