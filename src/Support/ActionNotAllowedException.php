<?php

namespace ArtflowStudio\Table\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thrown when a browser tries to invoke a component method that is not part of
 * the table's declared action surface.
 *
 * Deliberately a plain RuntimeException rather than an HttpException: Laravel's
 * exception handler *renders* HttpExceptions into responses, which meant the
 * refusal was swallowed rather than surfacing during testing. The `render()`
 * method below still produces a 403 over HTTP — a refused request, not a broken
 * one — while the exception itself propagates everywhere else.
 */
class ActionNotAllowedException extends \RuntimeException
{
    public function __construct(public readonly string $method)
    {
        parent::__construct(sprintf(
            'The [%s] method is not a callable AFTable action. If your application needs to '.
            'call it from the browser, add it to `aftable.security.extra_actions`.',
            $method
        ));
    }

    /** Rendered by Laravel's exception handler for real HTTP requests. */
    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'This action is not available.',
                'action' => $this->method,
            ], 403);
        }

        return response('This action is not available.', 403);
    }
}
