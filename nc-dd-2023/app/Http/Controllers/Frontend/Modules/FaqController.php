<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Modules;

use App\DI;
use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\Faq\StoreRequest;
use App\Models\Faq\Form\Faq as FaqForm;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Контролер отвечающий за работу с FAQ на сайте
 */
class FaqController extends BaseController
{
    /**
     * Страница со списком записей
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        return view('frontend.modules.faq.index', [
            'faq' => DI::getRepository()->getModelsFaq()->getPublished()->paginate(10)
        ]);
    }

    /**
     * Сохраняет запись
     *
     * @param  StoreRequest                  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeAction(StoreRequest $request): JsonResponse
    {
        $form    = FaqForm::createFromRequest($request);
        $message = 'Произошла ошибка во время сохранения, попробуйте позднее';

        try {
            $isSaved = DI::getRepository()->getModelsFaq()->storeFromArray($form->toArray());

            return response()->json([
                'success' => $isSaved,
                'message' => $isSaved ? 'Ваше сообщение успешно отправлено' : $message
            ]);
        } catch (\Throwable $throwable) {
            Log::error('[FAQ] Ошибка при сохранении нового вопроса', [
                'exception' => $throwable,
                'data'      => $form->toArray()
            ]);
        }

        return response()->json(['message' => $message])->setStatusCode(500);
    }
}
