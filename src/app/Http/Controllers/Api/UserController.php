<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * GET /api/users
     */
    public function index(Request $request): JsonResponse
    {
        $qb = User::query();

        if ($request->filled('search')) {
            $s = $request->input('search');
            $qb->where(function($q) use ($s) {
                $q->where('name',  'ILIKE', "%{$s}%")
                    ->orWhere('email', 'ILIKE', "%{$s}%");
            });
        }

        $sort      = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');
        $allowed   = ['id','name','email','created_at'];
        if (in_array($sort, $allowed)) {
            $qb->orderBy($sort, $direction);
        }

        $perPage = (int) $request->input('per_page', 20);
        $users   = $qb->paginate($perPage);

        return response()->json([
            'message'   => 'Успешно',
            'data'      => $users,
            'timestamp' => now()->toIso8601ZuluString(),
            'success'   => true,
        ]);
    }
}
