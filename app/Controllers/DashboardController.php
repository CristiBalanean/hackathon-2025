<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\AlertGenerator;
use App\Domain\Service\MonthlySummaryService;
use App\Domain\Repository\ExpenseRepositoryInterface;

class DashboardController extends BaseController
{
    public function __construct(
        Twig $view,
        // TODO: add necessary services here and have them injected by the DI container
        private readonly UserRepositoryInterface $userRepository,
        private readonly AlertGenerator $alertGenerator,
        private readonly MonthlySummaryService $monthlySummaryService,
        private readonly ExpenseRepositoryInterface $expenseRepositoryInterface,
    )
    {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: parse the request parameters
        // TODO: load the currently logged-in user
        // TODO: get the list of available years for the year-month selector
        // TODO: call service to generate the overspending alerts for current month
        // TODO: call service to compute total expenditure per selected year/month
        // TODO: call service to compute category totals per selected year/month
        // TODO: call service to compute category averages per selected year/month

        $userId = $_SESSION['user_id'];
        $user = $this->userRepository->find($userId);

        if(!$user)
        {
            return $response->withStatus(403);
        }

        $queryParams = $request->getQueryParams();

        $year = isset($queryParams['year']) && ctype_digit($queryParams['year'])
            ? (int)$queryParams['year']
            : (int)date('Y');

        $month = isset($queryParams['month']) && ctype_digit($queryParams['month']) && (int)$queryParams['month'] >= 1 && (int)$queryParams['month'] <= 12
            ? (int)$queryParams['month']
            : (int)date('n');

        $yearsWithExpenses = $this->expenseRepositoryInterface->listExpenditureYears($user);
        $alerts = $this->alertGenerator->generate($user, $year, $month);
        $totalForMonth = $this->monthlySummaryService->computeTotalExpenditure($user, $year, $month);
        $totalsForCategories = $this->monthlySummaryService->computePerCategoryTotals($user, $year, $month);
        $averagesForCategories = $this->monthlySummaryService->computePerCategoryAverages($user, $year, $month);

        return $this->render($response, 'dashboard.twig', [
            'year' => $year,
            'month' => $month,
            'yearsWithExpenses' => $yearsWithExpenses,
            'alerts' => $alerts,
            'totalForMonth' => $totalForMonth,
            'totalsForCategories' => $totalsForCategories,
            'averagesForCategories' => $averagesForCategories,
        ]);
    }
}
