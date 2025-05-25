<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\ExpenseService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 20;

    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ExpenseRepositoryInterface $expenseRepository,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the expenses page

        // Hints:
        // - use the session to get the current user ID
        // - use the request query parameters to determine the page number and page size
        // - use the expense service to fetch expenses for the current user

        // parse request parameters
        $userName = $_SESSION['username'] ?? null; // TODO: obtain logged-in user ID from session

        $params = $request->getQueryParams();
        $year = isset($params['year']) ? (int)$params['year'] : (int)date('Y');
        $month = isset($params['month']) ? (int)$params['month'] : (int)date('m');

        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $pageSize = (int)($request->getQueryParams()['pageSize'] ?? self::PAGE_SIZE);

        $user = $this->userRepository->findByUsername($userName);

        $result = $this->expenseService->list($user, $year, $month, $page, $pageSize);

        $yearWithExpenses = $this->expenseRepository->listExpenditureYears($user);

        return $this->render($response, 'expenses/index.twig', [
            'expenses' => $result['items'],
            'totalCount' => $result['totalCount'],
            'page'     => $page,
            'pageSize' => $pageSize,
            'year' => $year,
            'month' => $month, 
            'yearsWithExpenses' => $yearWithExpenses,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the create expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view

        $categories = ['Groceries', 'Transport', 'Entertainment', 'Utilities', 'Health'];

        return $this->render($response, 'expenses/create.twig', ['categories' => $categories]);
    }

    public function store(Request $request, Response $response): Response
    {
        // TODO: implement this action method to create a new expense

        // Hints:
        // - use the session to get the current user ID
        // - use the expense service to create and persist the expense entity
        // - rerender the "expenses.create" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        $userName = $_SESSION['username'] ?? null;
        if (!$userName) {
            return $response->withStatus(401);
        }

        $user = $this->userRepository->findByUsername($userName);
        $data = (array)$request->getParsedBody();
        $validation = $this->validateExpenseForm($data);

        if (!empty($validation['errors'])) {
            $categories = ['Groceries', 'Transport', 'Entertainment', 'Utilities', 'Health'];
            return $this->render($response, 'expenses/create.twig', [
                'errors' => $validation['errors'],
                'categories' => $categories,
                'formData' => $validation['formData'],
            ]);
        }

        $this->expenseService->create(
            $user,
            $validation['parsed']['amount'],
            $validation['parsed']['description'],
            $validation['parsed']['date'],
            $validation['parsed']['category'],
        );

        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to display the edit expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not

        $expenseId = (int) $routeParams['id'] ?? null;

        $expense = $this->expenseRepository->find($expenseId);

        if (!$expense) 
        {
            return $response->withStatus(404);
        }

        $loggedInUserId = $_SESSION['user_id'];

        if ($expense->userId !== $loggedInUserId) 
        {
            return $response->withStatus(403);
        }

        $categories = ['Groceries', 'Transport', 'Entertainment', 'Utilities', 'Health'];

        return $this->render($response, 'expenses/edit.twig', [
            'expense' => $expense, 
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to update an existing expense

        // Hints:
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - get the new values from the request and prepare for update
        // - update the expense entity with the new values
        // - rerender the "expenses.edit" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        $expenseId = (int) $routeParams['id'];
        $userName = $_SESSION['username'] ?? null;

        if (!$userName) {
            return $response->withStatus(401);
        }

        $user = $this->userRepository->findByUsername($userName);
        $expense = $this->expenseRepository->find($expenseId);

        if (!$expense) {
            return $response->withStatus(404);
        }

        if ($expense->userId !== $user->id) {
            return $response->withStatus(403);
        }

        $data = (array)$request->getParsedBody();
        $validation = $this->validateExpenseForm($data);

        if (!empty($validation['errors'])) {
            $categories = ['Groceries', 'Transport', 'Entertainment', 'Utilities', 'Health'];
            return $this->render($response, 'expenses/edit.twig', [
                'errors' => $validation['errors'],
                'categories' => $categories,
                'expense' => $expense,
                'formData' => $validation['formData'],
            ]);
        }

        $this->expenseService->update(
            $expense,
            $validation['parsed']['amount'],
            $validation['parsed']['description'],
            $validation['parsed']['date'],
            $validation['parsed']['category'],
        );

        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to delete an existing expense

        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - call the repository method to delete the expense
        // - redirect to the "expenses.index" page

        $expenseId = (int) $routeParams['id'];
        $userName = $_SESSION['username'] ?? null;

        if(!$userName)
        {
            return $response->withStatus(401);
        }

        $user = $this->userRepository->findByUsername($userName);
        $expense = $this->expenseRepository->find($expenseId);

        if(!$expense)
        {
            return $response->withStatus(404);
        }

        if($expense->userId !== $user->id)
        {
            return $response->withStatus(403);
        }

        $this->expenseRepository->delete($expenseId);

        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }

    private function validateExpenseForm(array $data): array
    {
        $errors = [];

        $dateInput = trim($data['date'] ?? '');
        $category = trim($data['category'] ?? '');
        $amount = floatval($data['amount'] ?? 0);
        $description = trim($data['description'] ?? '');

        try {
            $date = new \DateTimeImmutable($dateInput);
            $today = new \DateTimeImmutable('today');
            if ($date > $today) {
                $errors['date'] = 'Date cannot be in the future';
            }
        } catch (\Exception $e) {
            $errors['date'] = 'Invalid date.';
            $date = null;
        }

        if ($amount <= 0) {
            $errors['amount'] = 'Amount must be greater than 0.';
        }

        if ($category === '') {
            $errors['category'] = 'Please select a category.';
        }

        if ($description === '') {
            $errors['description'] = 'Description cannot be empty.';
        }

        return [
            'errors' => $errors,
            'formData' => [
                'dateInput' => $dateInput,
                'category' => $category,
                'amount' => $amount,
                'description' => $description,
            ],
            'parsed' => [
                'date' => $date ?? null,
                'category' => $category,
                'amount' => $amount,
                'description' => $description,
            ],
        ];
    }

    public function importCsv(Request $request, Response $response): Response
    {
            $userName = $_SESSION['username'] ?? null;

        if (!$userName) {
            return $response->withStatus(401);
        }

        $user = $this->userRepository->findByUsername($userName);

        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['csv'] ?? null;

        if ($uploadedFile === null || $uploadedFile->getError() !== UPLOAD_ERR_OK) {
            return $response->withStatus(400);
        }

        $count = $this->expenseService->importFromCsv($user, $uploadedFile);

        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }
}
