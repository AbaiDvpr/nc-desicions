<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Modules;

use App\DI;
use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\Feedback\StoreRequest;
use App\Models\Feedback\Form\Feedback as FeedbackForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Контролер для формы "Обратная связь" на сайте
 */
class FeedbacksController extends BaseController
{
    /**
     * Сохраняет запись
     *
     * @param  StoreRequest                  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeAction(StoreRequest $request): JsonResponse
    {
        $form    = FeedbackForm::createFromRequest($request);
        $message = 'Произошла ошибка во время сохранения, попробуйте позднее';

        try {
            $isSaved = DI::getRepository()->getModelsFeedback()->storeFromArray($form);

            return response()->json([
                'success' => $isSaved,
                'message' => $isSaved ? 'Спасибо, ваша заявка отправлена!' : $message
            ]);
        } catch (\Throwable $throwable) {
            Log::error('[Feedback] Ошибка при сохранении новой заявки на обратный звонок', [
                'exception' => $throwable,
                'data'      => $form->toArray()
            ]);
        }

        return response()->json(['message' => $message])->setStatusCode(500);
    }
}
