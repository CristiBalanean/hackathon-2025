<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;

class MonthlySummaryService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function computeTotalExpenditure(User $user, int $year, int $month): float
    {
        // TODO: compute expenses total for year-month for a given user

        $dateFrom = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));

        $dateTo = $dateFrom->modify('last day of this month')->setTime(23, 59, 59);

        $criteria = [
            'user_id' => $user->id,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        $totalCents = $this->expenses->sumAmounts($criteria);

        return $totalCents / 100;
    }

    public function computePerCategoryTotals(User $user, int $year, int $month): array
    {
        // TODO: compute totals for year-month for a given user

        $dateFrom = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $dateTo = $dateFrom->modify('last day of this month')->setTime(23, 59, 59);

        $criteria = [
            'user_id' => $user->id,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        $categoryTotalsCents = $this->expenses->sumAmountsByCategory($criteria);

        $categoryTotals = [];
        $totalForMonth = 0;

        foreach ($categoryTotalsCents as $category => $cents) {
            $euros = $cents / 100;
            $categoryTotals[$category] = $euros;
            $totalForMonth += $euros;
        }

        $result = [];
        foreach ($categoryTotals as $category => $value) {
            $percentage = $totalForMonth > 0 ? ($value / $totalForMonth) * 100 : 0;
            $result[$category] = [
                'value' => $value,
                'percentage' => $percentage,
            ];
        }

        return $result;
    }

    public function computePerCategoryAverages(User $user, int $year, int $month): array
    {
        // TODO: compute averages for year-month for a given user

        $dateFrom = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $dateTo = $dateFrom->modify('last day of this month')->setTime(23, 59, 59);

        $criteria = [
            'user_id' => $user->id,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        $categoryAveragesCents = $this->expenses->averageAmountsByCategory($criteria);

        $averages = [];
        foreach ($categoryAveragesCents as $category => $cents) {
            $averages[$category] = $cents / 100;
        }

        $maxAverage = !empty($averages) ? max($averages) : 0;

        $result = [];
        foreach ($averages as $category => $value) {
            $percentage = $maxAverage > 0 ? ($value / $maxAverage) * 100 : 0;
            $result[$category] = [
                'value' => $value,
                'percentage' => $percentage,
            ];
        }

        return $result;
    }
}
