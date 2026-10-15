<?php

namespace App\Actions\Fortify;

use App\Actions\Workshops\CreateWorkshop;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private readonly CreateWorkshop $createWorkshop) {}

    /**
     * Validate and create a newly registered user, in a new workshop of their own (ADR-0013).
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = new User([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            // `workshop_id` is never mass-assignable: the user joins the workshop through the relation.
            $user->workshop()->associate($this->createWorkshop->handle());
            $user->save();

            return $user;
        });
    }
}
