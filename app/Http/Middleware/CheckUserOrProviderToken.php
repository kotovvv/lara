<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\Provider;
use App\Models\User;

class CheckUserOrProviderToken
{
  public function handle(Request $request, Closure $next)
  {
    // Clear any existing authentication state
    Auth::logout();

    // Check if the token belongs to a user
    $token = PersonalAccessToken::findToken($request->bearerToken());
    if ($token) {
      $tokenable = $token->tokenable;
      if ($tokenable instanceof User) {
        Auth::setUser($tokenable);
        return $next($request);
      } elseif ($tokenable instanceof Provider) {
        Auth::setUser($tokenable);
        return $next($request);
      }
    }

    return response()->json(['message' => 'Unauthorized'], 401);
  }
}
