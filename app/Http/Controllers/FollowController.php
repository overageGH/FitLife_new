<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class FollowController extends Controller
{
    public function toggle(User $user)
    {
        if ($user->id === Auth::id()) {
            abort(403);
        }

        $me = Auth::user();

        if ($me->isFollowing($user)) {
            $me->followings()->detach($user->id);
        } else {
            $me->followings()->attach($user->id);
        }

        return back();
    }

    public function followers(User $user)
    {
        $users = $user->followers()->paginate(20);
        $title = __('profile.followers_list');

        return view('profile.follow-list', compact('user', 'users', 'title'));
    }

    public function following(User $user)
    {
        $users = $user->followings()->paginate(20);
        $title = __('profile.following_list');

        return view('profile.follow-list', compact('user', 'users', 'title'));
    }
}
