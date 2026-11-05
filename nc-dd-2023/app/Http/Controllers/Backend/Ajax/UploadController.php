<?php declare(strict_types=1);

namespace App\Http\Controllers\Backend\Ajax;

use App\Http\Controllers\Backend\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Общий класс для загрузки медиа содержимого
 */
class UploadController extends BaseController
{
    /**
     * @param Request $request
     * @return string[]
     */
    public function __invoke(Request $request): array
    {
        try {
            $validate = $this->validateRequestParams($request);

            if ($validate) {
                return ['errors' => 'Произошла ошибка во время загрузки, не корректно указаны параметры.'];
            }

            $id         = $request->input('id', 'null');
            $model      = $request->input('model', 'null');
            $collection = $request->input('collection', 'null');

            if (class_exists($model)) {
                $model = (new $model())->whereId($id)->first();

                if (!is_null($model)) {
                    $file = $request->file('file');

                    if ($request->input('clearCollect', false) === "1") {
                        $model->clearMediaCollection($collection);
                    }

                    $model->addMedia($file)
                        ->usingFileName($this->generateFileName($file->extension()))
                        ->toMediaCollection($collection);

                    return ['url' => $model->getFirstMedia($collection)->getUrl()];
                }
            }

            return ['errors' => 'Произошла ошибка во время загрузки, не корректно указаны параметры.'];
        } catch (\Exception $exception) {
            return [$exception->getMessage()];
        }
    }

    /**
     * Валидирует входящий запрос
     *
     * @param  \Illuminate\Http\Request $request
     * @return false|string
     */
    protected function validateRequestParams(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'model' => 'required',
            'collection' => 'required',
            'file' => 'required',
        ], [
            'id.required' => 'ID не указан.',
            'model.required' => 'Модель для загрузки не указана.',
            'collection.required' => 'Коллекция не указана',
            'file.required' => 'Файл не выбран.'
        ]);

        return ($validator->fails()) ? $validator->errors()->first() : false;
    }

    /**
     * Формирует имя для загружаемого файла
     *
     * @param  string $extension
     * @return string
     */
    protected function generateFileName(string $extension): string
    {
        return md5(Str::random(20) . time()) . '.' . $extension;
    }
}
