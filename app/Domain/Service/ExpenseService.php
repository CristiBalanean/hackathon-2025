<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Psr\Http\Message\UploadedFileInterface;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function list(User $user, int $year, int $month, int $pageNumber, int $pageSize): array
    {
        // TODO: implement this and call from controller to obtain paginated list of expenses

        $startDate = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59);

        $offset = ($pageNumber - 1) * $pageSize;

        $criteria = [
            'user_id' => $user-> id,
            'date_from' => $startDate,
            'date_to' => $endDate,
        ];

        $items = $this->expenses->findBy($criteria, $offset, $pageSize);
        $totalCount = $this->expenses->countBy($criteria);

        return [
            'items' => $items,
            'totalCount' => $totalCount,
        ];
    }

    public function create(
        User $user,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to create a new expense entity, perform validation, and persist

        // TODO: here is a code sample to start with
        $expense = new Expense(null, $user->id, $date, $category, (int)($amount * 100), $description);
        $this->expenses->save($expense);
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to update expense entity, perform validation, and persist
        $expense->amountCents = (int)($amount * 100);
        $expense->description = $description;
        $expense->date = $date;
        $expense->category = $category;

        $this->expenses->save($expense);
    }

    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails

        $stream = $csvFile->getStream()->detach();
        $importedCount = 0;
        $logger = new \Monolog\Logger('csv_import');
        $logPath = __DIR__ . '/../../../var/import.log';
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($logPath, \Monolog\Logger::INFO));

        $validCategories = ['Groceries', 'Transport', 'Entertainment', 'Utilities', 'Health'];
        $validCategories = array_map('strtolower', $validCategories);

        $seen = [];

        while (($line = fgets($stream)) !== false)
        {
            $parts = str_getcsv(trim($line));
            if(count($parts) !== 4)
            {
                $logger->warning("Invalid row format: $line");
                continue;
            }

            [$dateStr, $amountStr, $description, $categoryOriginal] = $parts;

            $description = trim($description);
            $categoryOriginal = trim($categoryOriginal);
            $categoryLower = strtolower($categoryOriginal);

            if ($description === '') 
            {
                $logger->warning("Empty description skipped: $line");
                continue;
            }

            $key = md5($dateStr . $description . $amountStr . $categoryLower);

            if(in_array($categoryLower, $validCategories, true) === false)
            {
                $logger->warning("Unknown category skipped: $line");
                continue;
            }

            try
            {
                $date = new \DateTimeImmutable($dateStr);
                $amount = (float)$amountStr;
            }
            catch (\Exception $e)
            {
                $logger->warning("Invalid data in row: $line");
                continue;
            }

            if(isset($seen[$key]))
            {
                $logger->info("Duplicate row skipped: $line");
                continue;
            }

            $seen[$key] = true;

            $expense = new Expense(
                null,
                $user->id,
                $date,
                $categoryOriginal,
                (int)($amount * 100),
                $description
            );

            $this->expenses->save($expense);
            $importedCount++;
        }

        fclose($stream);

        $logger->info("CSV import finished for user {$user->username}. Imported $importedCount rows.");

        return $importedCount; // number of imported rows
    }
}
