<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Sign-up is open only to company emails. The very first account becomes
 * management; everyone after that has no role (and no access) until
 * management assigns one.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        $domain = config('crm.signup_domain');
        $input['email'] = mb_strtolower(trim((string) ($input['email'] ?? '')));

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class), 'ends_with:@'.$domain],
            'password' => $this->passwordRules(),
        ], ['email.ends_with' => __('auth.domain_only', ['domain' => $domain])])->validate();

        return DB::transaction(function () use ($input) {
            $first = ! User::query()->lockForUpdate()->exists();
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'locale' => in_array($input['locale'] ?? null, ['ar', 'en'], true) ? $input['locale'] : 'ar',
            ]);
            if ($first) {
                \Spatie\Permission\Models\Role::findOrCreate('admin', 'web');
                $user->assignRole('admin');
            }
            return $user;
        });
    }
}
