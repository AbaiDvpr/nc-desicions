<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Handbook;

use App\DI;
use App\Http\Controllers\Backend\BaseController;
use App\Http\Requests\Backend\Handbook\Bank\SaveRequest;
use App\Models\Handbook\Bank as BankModel;
use App\Models\Handbook\Bank\Filters\PagerParams;
use App\Models\Handbook\Forms\Bank as BankForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Контроллер для работы со справочниками банков
 */
class Bank extends BaseController
{
    /**
     * Выводит список банков
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request): View
    {
        $params = PagerParams::createFromRequest($request);

        return view('backend.handbook.bank.index', [
            'banks' => DI::getRepository()->getHandbook()->getBank()->getWithPaginate($params->toArray())
        ]);
    }

    /**
     * Форма добавления банка в справочник
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('backend.handbook.bank.form', [
            'bank' => new BankModel(),
        ]);
    }

    /**
     * Форма редактирования банка и реквизитов
     *
     * @param  string                                                 $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(string $id): View|RedirectResponse
    {
        $bank = DI::getRepository()->getHandbook()->getBank()->getOne($id);

        if (!$bank) {
            return redirect()->route('backend.handbook.bank.index')->withErrors([
                'message' => 'Банк не найден в системе'
            ]);
        }

        return view('backend.handbook.bank.form', [
            'bank' => $bank
        ]);
    }

    /**
     * Обработчик сохранения банко в справочнике
     *
     * @param  \App\Http\Requests\Backend\Handbook\Bank\SaveRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(SaveRequest $request)
    {
        $bank = DI::getRepository()->getHandbook()->getBank()->save(
            $request->id,
            BankForm::createFromRequest($request)
        );

        if ($bank) {
            return redirect()->route('backend.handbook.bank.index')->with([
                'success' => 'Данные сохранены',
            ]);
        }

        return redirect()->route('backend.handbook.bank.create')
            ->withErrors(['Произошла ошибка во время сохранения']);
    }

    /**
     * Метод удаления банка из реквизитов
     *
     * @param  string                            $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(string $id): RedirectResponse
    {
        $bank = DI::getRepository()->getHandbook()->getBank()->getOne($id);

        if (!$bank) {
            return redirect()->route('backend.handbook.bank.index')->withErrors([
                'message' => 'Банк не найден в системе'
            ]);
        }

        if (! DI::getRepository()->getHandbook()->getBank()->delete($bank)) {
            return redirect()->route('backend.handbook.bank.index')
                ->withErrors(['Не удалось удалить банк, попробуйте позднее']);
        }

        return redirect()->route('backend.handbook.bank.index')->with([
            'success' => 'Банк удалён из системы',
        ]);
    }
}
