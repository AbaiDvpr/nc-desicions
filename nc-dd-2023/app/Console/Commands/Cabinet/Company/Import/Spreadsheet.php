<?php declare(strict_types=1);

namespace App\Console\Commands\Cabinet\Company\Import;

use App\DI;
use App\Models\Company;
use App\Models\Company\Import;
use App\Notifications\Cabinet\Import\Error as NotifyError;
use App\Notifications\Cabinet\Import\Success as NotifySuccess;
use App\Repositories\User\Exceptions\NotFoundException;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Console\Command\Command;
use Throwable;

/**
 * Основной класс для работы с импортом
 */
class Spreadsheet extends Base
{
    /**
     * Обработчик
     *
     * @param  \App\Models\Company\Import $import
     * @return int
     */
    public function handle(Import $import): int
    {
        $this->import = $import;
        $companyRepo  = DI::getRepository()->getCompany();
        $company      = $companyRepo->getByUuid($import->getCompanyUuid());
        $user         = $company ? DI::getRepository()->getUser()->findById($company->getUserId()) : null;

        try {
            if (!$user) {
                throw new NotFoundException('Пользователь не найден');
            }

            $this->initialize();

            if (!$this->save()) {
                throw new Exception('[Import] Ошибка при получении данных из файла');
            }

            $this->removeFileInLocalStorage();

            $companyRepo->getImport()->setCompleted($import->getId());

            Notification::send($user, new NotifySuccess());

            return Command::SUCCESS;
        } catch (NotFoundException $exception) {
            Log::error('Пользователь компании не найден', array_merge(
                ['exception' => $exception],
                $company->getLogData()
            ));
        } catch (Throwable $throwable) {
            Log::error('[Import] Ошибка запуска обработчика импорта', [
                'exception' => $throwable
            ]);

            Notification::send($user, new NotifyError());
        }

        $companyRepo->getImport()->delete($import);

        return Command::FAILURE;
    }

    /**
     * Получение и сохранение данных из файла и формирование отчета
     *
     * @return bool
     */
    protected function save(): bool
    {
        $companyRepo = DI::getRepository()->getCompany();

        try {
            $opu     = $this->getDataFromSheet(config('company.importSheet.index.opu'), Company::TYPE_OPU);
            $dds     = $this->getDataFromSheet(config('company.importSheet.index.dds'), Company::TYPE_DDS);
            $balance = $this->getDataFromSheet(config('company.importSheet.index.balance'), Company::TYPE_BALANCE);

            $companyRepo->getOpu()->storeFromArray($opu);
            $companyRepo->getDds()->storeFromArray($dds);
            $companyRepo->getBalance()->storeFromArray($balance);

            Artisan::call('company:report', [
                '--uuid' => $this->import->getCompanyUuid(),
                '--type' => Company::TYPE_OPU
            ]);
            Artisan::call('company:report', [
                '--uuid' => $this->import->getCompanyUuid(),
                '--type' => Company::TYPE_DDS
            ]);
            Artisan::call('company:report', [
                '--uuid' => $this->import->getCompanyUuid(),
                '--type' => Company::TYPE_BALANCE
            ]);

            return true;
        } catch (Throwable $throwable) {
            Log::error(
                '[Import] Ошибка при получении определенного листа из файла',
                array_merge($this->import->getLogData(), [
                    'exception'  => $throwable,
                    'tmpPath'    => $this->getTmpPath(),
                ])
            );
        }

        return false;
    }
}
