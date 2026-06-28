<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // First account to register bootstraps the Owner; everyone else is a Viewer.
        $role = $this->users->isEmpty() ? Role::Owner : Role::Viewer;

        $user = $this->users->create([
            'name' => $request->string('name')->value(),
            'email' => $request->string('email')->lower()->value(),
            'password' => Hash::make($request->string('password')->value()),
        ]);

        $user->assignRole($role->value);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
