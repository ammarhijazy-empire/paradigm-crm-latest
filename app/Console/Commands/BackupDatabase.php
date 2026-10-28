<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/** mysqldump to storage/app/backups, keeping 14 days. cPanel's own daily backup is the second copy. */
class BackupDatabase extends Command
{
    protected $signature = 'crm:backup {--keep=14}';

    protected $description = 'Dump the MySQL database to storage/app/backups';

    public function handle(): int
    {
        $c = config('database.connections.'.config('database.default'));
        if (($c['driver'] ?? '') !== 'mysql' && ($c['driver'] ?? '') !== 'mariadb') {
            $this->warn('Backups run on MySQL only.');
            return self::SUCCESS;
        }
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir, 0750);
        $file = $dir.'/db-'.now()->format('Ymd-His').'.sql.gz';
        $cnf = tempnam(sys_get_temp_dir(), 'my');
        file_put_contents($cnf, "[client]\nuser=\"{$c['username']}\"\npassword=\"{$c['password']}\"\nhost=\"{$c['host']}\"\nport=\"{$c['port']}\"\n");
        chmod($cnf, 0600);
        $p = Process::fromShellCommandline('mysqldump --defaults-extra-file='.escapeshellarg($cnf).' --single-transaction --routines --no-tablespaces '.escapeshellarg($c['database']).' | gzip > '.escapeshellarg($file));
        $p->setTimeout(600)->run();
        @unlink($cnf);
        if (! $p->isSuccessful() || filesize($file) < 100) {
            $this->error('Backup failed: '.$p->getErrorOutput());
            return self::FAILURE;
        }
        foreach (File::files($dir) as $old) {
            if ($old->getMTime() < now()->subDays((int) $this->option('keep'))->timestamp) {
                File::delete($old->getPathname());
            }
        }
        $this->info('Saved '.basename($file));
        return self::SUCCESS;
    }
}
