<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Tariff;

use App\Http\Controllers\Backend\BaseController;
use Illuminate\Http\RedirectResponse;
use App\Models\Tariff;

class CreateController extends BaseController
{
    /**
     * @return RedirectResponse
     */
    public function __invoke(): RedirectResponse
    {
        try {
            $tariff = Tariff::create([
                'created_id' => $this->user->id,
                'updated_id' => $this->user->id
            ]);

            return redirect()->route('backend.modules.tariff.edit', ['tariff' => $tariff->id]);
        } catch (\Exception $exception) {
            return redirect()->route('backend.modules.tariff.index')
                ->withErrors(['Произошла ошибка. Обратитесь к администратору']);
        }
    }
}
