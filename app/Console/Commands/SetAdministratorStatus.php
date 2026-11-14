<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetAdministratorStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:admin:set
                            {email : The email address of the existing user}
                            {status : Either grant or revoke administrator access}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant or revoke administrator access for an existing user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $status = (string) $this->argument('status');

        if (! in_array($status, ['grant', 'revoke'], true)) {
            $this->error('The status must be either "grant" or "revoke".');

            return self::INVALID;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("No user was found with the email [{$email}].");

            return self::FAILURE;
        }

        $shouldBeAdmin = $status === 'grant';

        if ($user->isAdmin() === $shouldBeAdmin) {
            $this->info($shouldBeAdmin
                ? 'This user is already an administrator.'
                : 'This user is already a regular customer.');

            return self::SUCCESS;
        }

        if (! $shouldBeAdmin && User::query()->where('is_admin', true)->count() <= 1) {
            $this->error('The last administrator cannot be revoked.');

            return self::FAILURE;
        }

        $action = $shouldBeAdmin ? 'grant administrator access to' : 'revoke administrator access from';

        if (! $this->confirm("Are you sure you want to {$action} [{$user->email}]?", false)) {
            $this->warn('No changes were made.');

            return self::SUCCESS;
        }

        $updated = DB::transaction(function () use ($user, $shouldBeAdmin): bool {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if (! $shouldBeAdmin) {
                $administratorIds = User::query()
                    ->where('is_admin', true)
                    ->lockForUpdate()
                    ->pluck('id');

                if ($administratorIds->count() <= 1 && $administratorIds->contains($lockedUser->getKey())) {
                    return false;
                }
            }

            $lockedUser->forceFill(['is_admin' => $shouldBeAdmin])->save();

            return true;
        });

        if (! $updated) {
            $this->error('The last administrator cannot be revoked.');

            return self::FAILURE;
        }

        $this->info($shouldBeAdmin
            ? "Administrator access granted to [{$user->email}]."
            : "Administrator access revoked from [{$user->email}].");

        return self::SUCCESS;
    }
}
