<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:token {name=app-demo : label for the token}')]
#[Description('Issue a Sanctum API token for the seeded demo user')]
class CreateDemoToken extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::where('email', 'johndoe@example.com')->first();

        if ($user === null) {
            $this->error('Demo user not found. Run php artisan db:seed first.');

            return self::FAILURE;
        }

        $token = $user->createToken((string) $this->argument('name'), ['read-user', 'manage-transactions', 'manage-budgets', 'manage-pots']);

        $this->info('Generated token:');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
