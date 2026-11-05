<?php declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Cabinet\Projects;

use App\DI;
use App\Http\Controllers\Frontend\BaseController;
use App\Models\Project\Filters\PagerParams;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер отвечающий за работу с проектами в кабинете клиента
 *
 * @package App\Http\Controllers\Frontend\Cabinet\Projects
 */
class ProjectController extends BaseController
{
    /**
     * Возвращает список проектов
     *
     * @param  \Illuminate\Http\Request      $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getListAction(Request $request): JsonResponse
    {
        $params = PagerParams::createFromRequest($request);
        $params->setUserId($this->client->getAuthIdentifier());

        return response()->json(
            DI::getRepository()->getProject()->getFromParams($params->toArray())
        );
    }

    /**
     * Проверяет на возможность создания проекта
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkCreateAction(): JsonResponse
    {
        $projectRepo   = DI::getRepository()->getProject();
        $projectsCount = $projectRepo->getHistory()->getItemsCount($this->client);

        return response()->json([
            'status' => $projectRepo->isPossibleToCreate($this->client),
            'left'   => max($this->client->tariff()->getMaxProjects() - $projectsCount, 0),
        ]);
    }

    /**
     * Создает проект
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createAction(): JsonResponse
    {
        $projectRepo = DI::getRepository()->getProject();

        if (!$projectRepo->isPossibleToCreate($this->client)) {
            return response()->json([
                'message' => 'По вашему тарифу вы исчерпали возможность рассчитывать финансовые модели',
            ], 500);
        }

        return response()->json([
            'uuid' => $projectRepo->create($this->client),
        ]);
    }

    /**
     * Проверяет на возможность редактирования проекта
     *
     * @param string                        $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkCorrectAction(string $uuid): JsonResponse
    {
        $project = DI::getRepository()->getProject()->getOneByUuid($uuid);
        $status  = $project->isPossibleToCorrect();
        $message = $status ? '' : 'Финансовая модель не подлежит корректировке';

        return response()->json(compact('status', 'message'));
    }
}
