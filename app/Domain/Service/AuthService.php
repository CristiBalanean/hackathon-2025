<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function register(string $username, string $password): User
    {
        // TODO: check that a user with same username does not exist, create new user and persist

        $existingUser = $this->users->findByUsername($username);
        if($existingUser != null)
        {
            throw new \Exception('Username already taken');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // TODO: make sure password is not stored in plain, and proper PHP functions are used for that

        // TODO: here is a sample code to start with
        $user = new User(null, $username, $passwordHash, new \DateTimeImmutable());
        $this->users->save($user);

        return $user;
    }

    public function attempt(string $username, string $password): bool
    {
        // TODO: implement this for authenticating the user
        // TODO: make sure the user exists and the password matches
        // TODO: don't forget to store in session user data needed afterwards

        $user = $this->users->findByUsername($username);
        if($user === null)
        {
            return false;
        }

        if(!password_verify($password, $user->passwordHash))
        {
            return false;
        }

        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;

        return true;
    }
}
