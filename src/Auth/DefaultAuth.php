<?php

namespace Lylink\Auth;

use Exception;
use Lylink\DoctrineRegistry;
use Lylink\Interfaces\Auth\AccountHandler;
use Lylink\Interfaces\Auth\Authorizator;
use Lylink\Models\Settings;
use Lylink\Models\User;
use Lylink\Traits\Authorizable;
use SensitiveParameter;

class DefaultAuth implements Authorizator, AccountHandler
{
    use Authorizable;

    /**
     * @return array{errors: list<string>, success: bool, usermail: string}
     */
    public function login(string $usernamemail, #[SensitiveParameter] string $password): array
    {

        $data = ['success' => false,
            'usermail' => $usernamemail,
            'errors' => []
        ];
        $em = DoctrineRegistry::get();

        if (filter_var($usernamemail, FILTER_VALIDATE_EMAIL)) {
            /**
             * @var User|null
             */
            $user = $em->getRepository(User::class)->findOneBy(['email' => $usernamemail]);

        } else {
            /**
             * @var User|null
             */
            $user = $em->getRepository(User::class)->findOneBy(['username' => $usernamemail]);
        }

        if ($user == null) {
            $data["errors"][] = 'User not found';
        } else {
            if (!$user->checkPassword($password)) {
                $data["errors"][] = 'Invalid password';
            }
            $this->uid = $user->getId() ?? 0;

            if (!$user->isEmailVerified()) {
                $data["errors"][] = "User email has not been verified yet";
            }
        }

        if ($data["errors"] === []) {
            $data["success"] = true;
            $this->authorized = true;
        }
        return $data;
    }

    /**
     * @return array{errors: list<string>, success: bool, old: array{email: string, username: string}}
     */
    public function register(string $email, string $username, #[SensitiveParameter] string $pass, #[SensitiveParameter] string $passCheck): array
    {
        $errors = [];
        if ($email === '' || $username === '' || $pass === '' || $passCheck === '') {
            $errors[] = 'All fields are required';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address';
        }

        if ($pass !== $passCheck) {
            $errors[] = 'Passwords do not match';
        }

        if (strlen($pass) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        if (!preg_match('/[A-Z]/', $pass) || !preg_match('/[a-z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
            $errors[] = 'Password must contain at least one uppercase letter one lowercase letter and one digit';
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $em = DoctrineRegistry::get();
        $userRepo = $em->getRepository(User::class);

        if (empty($errors)) {
            $existingByEmail = $userRepo->findOneBy(['email' => $email]);
            if ($existingByEmail) {
                $errors[] = 'Email is already registered';
            }

            $existingByUsername = $userRepo->findOneBy(['username' => $username]);
            if ($existingByUsername) {
                $errors[] = 'Username is already taken';
            }
        }

        if (empty($errors)) {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $user = new User($email, $username, $hash);

            try {
                $em->persist($user);
                $em->flush();
                $id = $user->getId();
                if ($id === null) {
                    throw new Exception("user did not save to db");
                }
                $settings = new Settings($id);
                $em->persist($settings);
                $em->flush();
                return [
                    'errors' => $errors,
                    'success' => true,
                    'old' => [
                        'email' => $email,
                        'username' => $username
                    ]];
            } catch (\Exception $e) {
                $errors[] = 'Failed to create account';
            }
        }
        return [
            'errors' => $errors,
            'success' => false,
            'old' => [
                'email' => $email,
                'username' => $username
            ]
        ];
    }

    public function getUser(): ?User
    {
        $em = DoctrineRegistry::get();
        return $em->getRepository(User::class)->find($this->getUid());
    }
}
