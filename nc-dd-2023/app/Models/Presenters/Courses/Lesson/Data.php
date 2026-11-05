<?php declare(strict_types=1);

namespace App\Models\Presenters\Courses\Lesson;

use App\Models\Courses\Lesson;

/**
 * Презентер для обработки данных урока курсов
 */
class Data
{
    /**
     * @var \App\Models\Courses\Lesson
     */
    private Lesson $lesson;

    /**
     * Конструктор
     *
     * @param \App\Models\Courses\Lesson $lesson
     */
    public function __construct(Lesson $lesson)
    {
        $this->lesson = $lesson;
    }

    /**
     * Возвращает публичные данные
     *
     * @return array
     */
    public function getPublicData(): array
    {
        return [
            'id'             => $this->lesson->getId(),
            'title'          => $this->lesson->getTitle(),
            'description'    => $this->lesson->getDescription(),
            'videoID'        => getIDYoutubeVideo($this->lesson->getLinkVideo()),
            'linkAdditional' => $this->lesson->getLinkAdditional(),
        ];
    }
}
