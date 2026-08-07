<?php

use App\Http\Middleware\AuditLog;
use App\Http\Middleware\EnforcePlanLimit;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\ResolveTenant;
use App\Support\ApiResponse;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.resolve' => ResolveTenant::class,
            'audit.log' => AuditLog::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'plan.limit' => EnforcePlanLimit::class,
        ]);

        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        $middleware->appendToGroup('api', [
            ResolveTenant::class,
            AuditLog::class,
        ]);

        // Declaration order in a group is NOT execution order - Laravel
        // resorts every request's middleware per $middlewarePriority, and
        // framework middleware not declared here (auth, SubstituteBindings)
        // still gets sorted according to that list regardless of where
        // ResolveTenant sits in the group above. Pin ResolveTenant's actual
        // execution position explicitly: it must run after auth resolves
        // the user (isSuperAdmin()/tenant lookup both need $request->user())
        // but before SubstituteBindings resolves {order}/{product}/etc. -
        // otherwise those implicit route-bound tenant-scoped models get
        // looked up before RLS/the Eloquent scope even knows the tenant.
        $middleware->appendToPriorityList(
            after: AuthenticatesRequests::class,
            append: ResolveTenant::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::fromException($e);
            }
        });
    })->create();
