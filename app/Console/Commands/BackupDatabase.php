<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Throwable;

#[Signature('database:backup {--path= : Absolute or project-relative destination .sql path}')]
#[Description('Create and verify a transaction-consistent MySQL database backup')]
class BackupDatabase extends Command
{
    public function handle(): int
    {
        if (config('database.default') !== 'mysql') {
            $this->error('The production backup command currently supports MySQL only.');

            return self::FAILURE;
        }

        $connection = config('database.connections.mysql');
        $database = (string) ($connection['database'] ?? '');
        $username = (string) ($connection['username'] ?? '');

        if ($database === '' || $username === '') {
            $this->error('MySQL database name and username must be configured.');

            return self::FAILURE;
        }

        $path = $this->backupPath();
        File::ensureDirectoryExists(dirname($path), 0700, true);

        $arguments = [
            'mysqldump', '--single-transaction', '--quick', '--skip-lock-tables',
            '--routines', '--triggers', '--events', '--hex-blob', '--no-tablespaces',
            '--set-gtid-purged=OFF',
            '--host='.(string) ($connection['host'] ?? '127.0.0.1'),
            '--port='.(string) ($connection['port'] ?? 3306),
            '--user='.$username, '--result-file='.$path, $database,
        ];

        try {
            $process = new Process($arguments, base_path(), [
                'MYSQL_PWD' => (string) ($connection['password'] ?? ''),
            ]);
            $process->setTimeout(null);
            $process->mustRun();

            clearstatcache(true, $path);
            if (! File::exists($path) || File::size($path) < 100) {
                throw new \RuntimeException('The backup file is empty or incomplete.');
            }

            chmod($path, 0600);
            $checksumPath = $path.'.sha256';
            File::put($checksumPath, hash_file('sha256', $path).'  '.basename($path).PHP_EOL);
            chmod($checksumPath, 0600);
        } catch (Throwable $exception) {
            if (File::exists($path)) {
                File::delete($path);
            }

            $this->error('Database backup failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Verified database backup created: '.$path);
        $this->line('SHA-256: '.hash_file('sha256', $path));

        return self::SUCCESS;
    }

    private function backupPath(): string
    {
        $requested = trim((string) $this->option('path'));
        if ($requested === '') {
            return storage_path('app/backups/'.now()->format('Ymd-His').'-'.config('database.connections.mysql.database').'.sql');
        }

        $path = str_starts_with($requested, DIRECTORY_SEPARATOR) ? $requested : base_path($requested);

        return str_ends_with(strtolower($path), '.sql') ? $path : $path.'.sql';
    }
}
