<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * @throws Exception
     */
    public function find(int $id): ?Expense
    {
        $query = 'SELECT * FROM expenses WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return $this->createExpenseFromData($data);
    }

    public function save(Expense $expense): void
    {
        // TODO: Implement save() method.

        if($expense->id === null)
        {
            $sql = 'INSERT INTO expenses (user_id, date, category, amount_cents, description)
                    VALUES (:user_id, :date, :category, :amount_cents, :description)';
        }
        else
        {
            $sql = 'UPDATE expenses
                    SET date = :date, category = :category, amount_cents = :amount_cents, description = :description
                    WHERE id = :id AND user_id = :user_id';
        }

        $stmt = $this->pdo->prepare($sql);

        $params =[
            'user_id' => $expense->userId,
            'date' => $expense->date->format('Y-m-d H:i:s'),
            'category' => $expense->category,
            'amount_cents' => $expense->amountCents,
            'description' => $expense->description,
        ];

        if($expense->id !== null)
        {
            $params['id'] = $expense->id;
        }

        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM expenses WHERE id=?');
        $statement->execute([$id]);
    }

    public function findBy(array $criteria, int $from, int $limit): array
    {
        // TODO: Implement findBy() method.

        $query = 'SELECT * FROM expenses WHERE 1=1';
        $params = [];

        if(isset($criteria['user_id']))
        {
            $query .= ' AND user_id = :user_id';
            $params['user_id'] = $criteria['user_id'];
        }

        if(isset($criteria['date_from']))
        {
            $query .= ' AND date >= :date_from';
            $params['date_from'] = $criteria['date_from']->format('Y-m-d H:i:s');
        }

        if(isset($criteria['date_to']))
        {
            $query .= ' AND date <= :date_to';
            $params['date_to'] = $criteria['date_to']->format('Y-m-d H:i:s');
        }

        $query .= ' ORDER BY date DESC LIMIT :limit OFFSET :offset';
        $statement = $this->pdo->prepare($query);

        foreach ($params as $key => $value)
        {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $from, PDO::PARAM_INT);

        $statement->execute();
        $rows = $statement->fetchAll();

        $expenses = [];
        foreach ($rows as $row)
        {
            $expenses[] = $this->createExpenseFromData($row);
        }

        return $expenses;
    }


    public function countBy(array $criteria): int
    {
        // TODO: Implement countBy() method.

        $query = 'SELECT COUNT(*) FROM expenses WHERE 1=1';
        $params = [];

        if(isset($criteria['user_id']))
        {
            $query .= ' AND user_id = :user_id';
            $params['user_id'] = $criteria['user_id'];
        }

        if (isset($criteria['date_from'])) 
        {
            $query .= ' AND date >= :date_from';
            $params['date_from'] = $criteria['date_from']->format('Y-m-d H:i:s');
        }

        if (isset($criteria['date_to'])) 
        {
            $query .= ' AND date <= :date_to';
            $params['date_to'] = $criteria['date_to']->format('Y-m-d H:i:s');
        }

        $statement = $this->pdo->prepare($query);
        foreach ($params as $key => $value) 
        {
            $statement->bindValue(':' . $key, $value);
        }

        $statement->execute();
        return (int)$statement->fetchColumn();
    }

    public function listExpenditureYears(User $user): array
    {
        // TODO: Implement listExpenditureYears() method.

        $sql = "SELECT DISTINCT strftime('%Y', date) AS year
                FROM expenses
                WHERE user_id = :userId
                ORDER BY year DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['userId' => $user->id]);

        $years = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        return array_map('intval', $years);
    }

    public function sumAmountsByCategory(array $criteria): array
    {
        // TODO: Implement sumAmountsByCategory() method.

        $query = 'SELECT category, SUM(amount_cents) as total FROM expenses WHERE 1=1';
        $params = [];

        if (isset($criteria['user_id'])) {
            $query .= ' AND user_id = :user_id';
            $params['user_id'] = $criteria['user_id'];
        }

        if (isset($criteria['date_from'])) {
            $query .= ' AND date >= :date_from';
            $params['date_from'] = $criteria['date_from']->format('Y-m-d H:i:s');
        }

        if (isset($criteria['date_to'])) {
            $query .= ' AND date <= :date_to';
            $params['date_to'] = $criteria['date_to']->format('Y-m-d H:i:s');
        }

        $query .= ' GROUP BY category';

        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['category']] = (float)$row['total'];
        }

        return $result;
    }

    public function averageAmountsByCategory(array $criteria): array
    {
        // TODO: Implement averageAmountsByCategory() method.

        $query = 'SELECT category, AVG(amount_cents) as average FROM expenses WHERE 1=1';
        $params = [];

        if (isset($criteria['user_id'])) {
            $query .= ' AND user_id = :user_id';
            $params['user_id'] = $criteria['user_id'];
        }

        if (isset($criteria['date_from'])) {
            $query .= ' AND date >= :date_from';
            $params['date_from'] = $criteria['date_from']->format('Y-m-d H:i:s');
        }

        if (isset($criteria['date_to'])) {
            $query .= ' AND date <= :date_to';
            $params['date_to'] = $criteria['date_to']->format('Y-m-d H:i:s');
        }

        $query .= ' GROUP BY category';

        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['category']] = (float)$row['average'];
        }

        return $result;
    }

    public function sumAmounts(array $criteria): float
    {
        // TODO: Implement sumAmounts() method.

        $query = 'SELECT SUM(amount_cents) as total FROM expenses WHERE 1=1';
        $params = [];

        if (isset($criteria['user_id'])) {
            $query .= ' AND user_id = :user_id';
            $params['user_id'] = $criteria['user_id'];
        }

        if (isset($criteria['date_from'])) {
            $query .= ' AND date >= :date_from';
            $params['date_from'] = $criteria['date_from']->format('Y-m-d H:i:s');
        }

        if (isset($criteria['date_to'])) {
            $query .= ' AND date <= :date_to';
            $params['date_to'] = $criteria['date_to']->format('Y-m-d H:i:s');
        }

        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->execute();
        $total = $stmt->fetchColumn();

        return $total === null ? 0.0 : (float)$total;
    }

    /**
     * @throws Exception
     */
    private function createExpenseFromData(mixed $data): Expense
    {
        return new Expense(
            $data['id'],
            $data['user_id'],
            new DateTimeImmutable($data['date']),
            $data['category'],
            $data['amount_cents'],
            $data['description'],
        );
    }

    public function findDistinctCategories(): array
    {
        $stmt = $this->pdo->query('SELECT DISTINCT category FROM expenses ORDER BY category');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
