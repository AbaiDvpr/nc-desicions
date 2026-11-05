<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Cabinet;

use App\DI;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends BaseController
{
    /**
     * Возвращает список модулей
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getModules(): JsonResponse
    {
        $modules = DI::getRepository()->getCourses()->getModule()->getAll();
        $data    = collect($modules)->map(function ($module) {
            /** @var \App\Models\Courses\Module $module */
            return array_merge($module->getPresenter()->getPublicData(), [
                'rating'  => DI::getRepository()->getCourses()->getModule()->getRating($module),
                'lessons' => [
                    'total'     => $module->getLessons()->count(),
                    'completed' => DI::getRepository()->getCourses()->getLesson()
                        ->getUserCompleted($this->client, $module)->count(),
                ],
            ]);
        });

        return response()->json($data);
    }

    /**
     * Возвращает модуль
     *
     * @param  string                       $moduleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOneModule(string $moduleId): JsonResponse
    {
        $module = DI::getRepository()->getCourses()->getModule()->getOne($moduleId);

        if (!$module) {
            return response()->json([
                'message' => 'Модуль не найден',
            ], 500);
        }

        return response()->json($module->getPresenter()->getPublicData());
    }

    /**
     * Возвращает список уроков у модуля
     *
     * @param  string                       $moduleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLessons(string $moduleId): JsonResponse
    {
        $lessons = DI::getRepository()->getCourses()->getLesson()->getByModule($moduleId);
        $data    = collect($lessons)->map(function ($lesson) {
            /** @var \App\Models\Courses\Lesson $lesson */
            return array_merge(
                $lesson->getPresenter()->getPublicData(),
                [
                    'completed' => DI::getRepository()->getCourses()->getLesson()
                        ->isCompletedLesson($this->client, $lesson->module(), $lesson),
                    'rating'    => DI::getRepository()->getCourses()->getLesson()->getRating($lesson),
                    'voted'     => DI::getRepository()->getCourses()->getLesson()->isVoted($this->client, $lesson),
                    'votes'     => DI::getRepository()->getCourses()->getLesson()->getVotes($lesson),
                ]
            );
        });

        return response()->json($data);
    }

    /**
     * Устанавливает оценку урока
     *
     * @param  \Illuminate\Http\Request      $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \ErrorException
     */
    public function setRateLesson(Request $request): JsonResponse
    {
        $lessonRepo = DI::getRepository()->getCourses()->getLesson();
        $module     = DI::getRepository()->getCourses()->getModule()->getOne(
            (string) $request->module
        );
        $lesson     = $lessonRepo->getOne(
            (string) $request->lesson
        );
        $rate       = (int) $request->rate;

        if ($lessonRepo->isVoted($this->client, $lesson)) {
            return response()->json([
                'message' => 'Вы уже голосовали за этот урок',
            ], 500);
        }

        return response()->json([
            'voted'  => $lessonRepo->setRate($this->client, $module, $lesson, $rate),
            'rating' => $lessonRepo->getRating($lesson),
            'votes'  => $lessonRepo->getVotes($lesson),
        ]);
    }

    /**
     * Устанавливает статус завершения урока
     *
     * @param  \Illuminate\Http\Request      $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \ErrorException
     */
    public function setCompleteLesson(Request $request): JsonResponse
    {
        $module = DI::getRepository()->getCourses()->getModule()->getOne(
            (string) $request->module
        );

        $lesson = DI::getRepository()->getCourses()->getLesson()->getOne(
            (string) $request->lesson
        );

        return response()->json(
            DI::getRepository()->getCourses()->getLesson()->setCompleted($this->client, $module, $lesson)
        );
    }
}
