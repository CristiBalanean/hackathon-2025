<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use NumberFormatter;

class AlertGenerator
{
    // TODO: refactor the array below and make categories and their budgets configurable in .env
    // Hint: store them as JSON encoded in .env variable, inject them manually in a dedicated service,
    // then inject and use use that service wherever you need category/budgets information.
    private array $categoryBudgets;

    private MonthlySummaryService $monthlySummary;

    public function __construct(MonthlySummaryService $monthlySummary)
    {
        $this->monthlySummary = $monthlySummary;

        $json = $_ENV['CATEGORY_BUDGETS'] ?? null;
        if(!$json)
        {
            throw new \RuntimeException('CATEGORY_BUDGETS env variable not set');
        }

        $decoded = json_decode($json, true);
        if(!is_array($decoded))
        {
            throw new \RuntimeException('Invalid CATEGORY_BUDGETS JSON format');
        }

        $this->categoryBudgets = array_map('floatval', $decoded);
    }

    public function generate(User $user, int $year, int $month): array
    {
        // TODO: implement this to generate alerts for overspending by category

        $alerts = [];

        $expensesPerCategory = $this->monthlySummary->computePerCategoryTotals($user, $year, $month);

        foreach($expensesPerCategory as $category => $data)
        {
            $total = $data['value'];
            $budget = $this->categoryBudgets[$category] ?? null;

            if($budget !== null && $total > $budget)
            {
                $over = number_format($total - $budget, 2);
                $alerts[] = "{$category} budget exceeded by {$over} €";
            }
        }

        return $alerts;
    }
}
