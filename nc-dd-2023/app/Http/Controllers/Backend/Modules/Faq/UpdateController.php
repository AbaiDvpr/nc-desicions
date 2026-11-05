<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Modules\Faq;

use App\Http\Controllers\Backend\BaseController;
use App\Jobs\Backend\Modules\Faq\FaqUserReplySend;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\DI;

class UpdateController extends BaseController
{
    /**
     * Сохранение вопроса
     *
     * @param  \Illuminate\Http\Request          $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        try {
            $faq = DI::getRepository()->getModelsFaq()->getOne((int) $request->faq);

            if (is_null($faq)) {
                return abort(404);
            }

            $post = Arr::collapse([
                $request->only(['published', 'main', 'name', 'surname', 'email', 'phone', 'question', 'reply']), [
                    'updated_id'   => $this->user->id,
                    'published_at' => Carbon::now(),
                ],
            ]);

            $save = DI::getRepository()->getModelsFaq()->save($faq->id, $post);

            if ($save && $request->input('send_notification')) {
                FaqUserReplySend::dispatch($faq->id)->delay(now()->addSeconds(10));
            }

            return redirect()->route('backend.modules.faq.index')->with([
                'success' => 'Запись сохранена.',
            ]);
        } catch (\Exception $exception) {
            return redirect()->back()->withErrors([
                'Произошла непредвиденная ошибка. Ошибка: ' . $exception->getMessage()
            ]);
        }
    }
}
