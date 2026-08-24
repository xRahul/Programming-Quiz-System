<?php

declare(strict_types=1);

/**
 * Central access point for tests that need elevated database privileges
 * (scratch-db provisioning, grants, SET GLOBAL).
 *
 * Admin credential resolution order:
 *   1. DB_ADMIN_USER / DB_ADMIN_PASS / DB_ADMIN_HOST   (explicit override;
 *      a set-but-wrong value fails loud rather than silently skipping)
 *   2. DB_HOST explicitly exported                      (CI contract: the app
 *      user was elevated to ALL PRIVILEGES ON *.* WITH GRANT OPTION)
 *   3. OS-account unix_socket                           (local zero-config:
 *      `mysql -e CREATE DATABASE` works passwordless as the login user)
 *   4. App credentials against DB_HOST                  (last resort)
 *
 * Grant principal follows how the APP connects (socket -> user@localhost,
 * TCP -> user@%), mirroring MariaDB's separate accounts per host.
 */
final class TestEnv
{
    /** @var array{user:string,pass:string,host:string,tier:int}|null */
    private static ?array $resolved = null;

    /** @return array{user:string,pass:string,host:string,tier:int} */
    private static function resolveAdmin(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }
        $adminUser = getenv('DB_ADMIN_USER');
        if ($adminUser !== false && $adminUser !== '') {
            return self::$resolved = [
                'user' => $adminUser,
                'pass' => getenv('DB_ADMIN_PASS') !== false ? getenv('DB_ADMIN_PASS') : '',
                'host' => getenv('DB_ADMIN_HOST') ?: (getenv('DB_HOST') ?: '127.0.0.1'),
                'tier' => 1,
            ];
        }
        $dbHost = getenv('DB_HOST');
        if ($dbHost !== false && $dbHost !== '') {
            return self::$resolved = [
                'user' => getenv('DB_USER') ?: 'quiz',
                'pass' => getenv('DB_PASS') ?: '',
                'host' => $dbHost,
                'tier' => 2,
            ];
        }
        $osUser = getenv('USER');
        if ($osUser !== false && $osUser !== '') {
            return self::$resolved = ['user' => $osUser, 'pass' => '', 'host' => 'localhost', 'tier' => 3];
        }

        return self::$resolved = [
            'user' => getenv('DB_USER') ?: 'quiz',
            'pass' => getenv('DB_PASS') ?: '',
            'host' => '127.0.0.1',
            'tier' => 4,
        ];
    }

    public static function adminHost(): string
    {
        return self::resolveAdmin()['host'];
    }

    public static function adminUser(): string
    {
        return self::resolveAdmin()['user'];
    }

    public static function adminPass(): string
    {
        return self::resolveAdmin()['pass'];
    }

    public static function appUser(): string
    {
        return getenv('DB_USER') ?: 'quiz';
    }

    /** Elevated PDO connection, or null when credentials don't work. */
    public static function adminPdo(): ?PDO
    {
        try {
            return new PDO(
                'mysql:host=' . self::adminHost() . ';charset=utf8mb4',
                self::adminUser(),
                self::adminPass(),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException) {
            return null;
        }
    }

    /** Principal the app connects as — grants must target this account. */
    public static function grantPrincipal(): string
    {
        // Socket connections match user@localhost; explicit TCP host
        // (CI exports DB_HOST=127.0.0.1) matches the wildcard account.
        $host = getenv('DB_HOST');
        $tcp = $host !== false && $host !== '' && strcasecmp($host, 'localhost') !== 0;

        return "'" . self::appUser() . "'@'" . ($tcp ? '%' : 'localhost') . "'";
    }

    /** Credential flags for mysql CLI invocations (shell-escaped). */
    public static function cliFlags(): string
    {
        return ' --host=' . escapeshellarg(self::adminHost())
            . ' --user=' . escapeshellarg(self::adminUser())
            . ' --password=' . escapeshellarg(self::adminPass());
    }
}
