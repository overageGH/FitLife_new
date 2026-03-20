<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index()
    {
        $tab = request('tab', 'calories');

        $topCalories = User::select('users.*')
            ->leftJoin('meal_logs', 'users.id', '=', 'meal_logs.user_id')
            ->groupBy('users.id')
            ->orderByDesc(DB::raw('COALESCE(SUM(meal_logs.calories), 0)'))
            ->limit(20)
            ->get()
            ->map(fn ($u) => (object) [
                'user' => $u,
                'value' => (int) ($u->mealLogs()->sum('calories') ?? 0),
            ]);

        $topWater = User::select('users.*')
            ->leftJoin('water_logs', 'users.id', '=', 'water_logs.user_id')
            ->groupBy('users.id')
            ->orderByDesc(DB::raw('COALESCE(SUM(water_logs.amount), 0)'))
            ->limit(20)
            ->get()
            ->map(fn ($u) => (object) [
                'user' => $u,
                'value' => (int) ($u->waterLogs()->sum('amount') ?? 0),
            ]);

        $topProgress = User::withCount('progress')
            ->orderByDesc('progress_count')
            ->limit(20)
            ->get()
            ->map(fn ($u) => (object) [
                'user' => $u,
                'value' => $u->progress_count,
            ]);

        $topActivity = User::withCount(['posts', 'comments', 'likes'])
            ->get()
            ->sortByDesc(fn ($u) => $u->posts_count + $u->comments_count + $u->likes_count)
            ->take(20)
            ->values()
            ->map(fn ($u) => (object) [
                'user' => $u,
                'value' => $u->posts_count + $u->comments_count + $u->likes_count,
            ]);

        return view('leaderboard.index', compact('tab', 'topCalories', 'topWater', 'topProgress', 'topActivity'));
    }
}
