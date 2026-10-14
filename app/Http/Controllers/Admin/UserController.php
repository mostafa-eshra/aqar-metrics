<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionEnum;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;

final class UserController extends BaseController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            self::setPermissionToController(PermissionEnum::USER_LIST, ['index']),
            self::setPermissionToController(PermissionEnum::USER_CREATE, ['create']),
            self::setPermissionToController(PermissionEnum::USER_EDIT, ['edit']),
        ];
    }

    public function index(): View
    {
        $users = User::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('admin.users.index', ['users' => $users]);
    }

    public function create(UserRequest $request): RedirectResponse|View
    {
        if ($request->isMethod('post')) {
            UserService::create($request->validated());

            return redirect()
                ->route('admin.users.index')
                ->with('status', trans('app.users.created'));
        }

        return view('admin.users.create');
    }

    public function edit(UserRequest $request, User $user): RedirectResponse|View
    {
        if ($request->isMethod('post')) {
            UserService::update($user, $request->validated());

            return redirect()
                ->route('admin.users.index')
                ->with('status', trans('app.users.updated'));
        }

        return view('admin.users.edit', ['user' => $user]);
    }
}
