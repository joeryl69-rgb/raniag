<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

/**
 * One-time setup helper for Web Push. Run:
 *   php artisan raniag:generate-vapid-keys
 * then paste the printed VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY into .env.
 * Requires `composer require minishlink/web-push` to have been run first.
 */
class GenerateVapidKeys extends Command
{
    protected $signature = 'raniag:generate-vapid-keys';

    protected $description = 'Generate a VAPID key pair for Web Push notifications and print .env-ready lines';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->newLine();
        $this->info('Add these to your .env file, then re-run `php artisan config:clear`:');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->line('VAPID_SUBJECT=mailto:admin@example.com');
        $this->newLine();

        return self::SUCCESS;
    }
}
