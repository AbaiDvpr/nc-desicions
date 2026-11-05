<?php declare(strict_types=1);

namespace App\Components\Report\Helpers\EfficiencyAnalysis;

use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

trait Results
{


    public function getResultsTableDiscount()
    {
        $headers = [[1], [1]];

        foreach ($this->yearsPlaning as $yearPlan) {
            $headers[1][]    = $yearPlan['year'];
        }

        dump($headers); exit(1);
    }

}
