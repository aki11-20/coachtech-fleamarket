<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit () {
        $profile = Auth::user()->profile;
        return view('mypage.profile', compact('profile'));
    }

    public function update (ProfileRequest $request) {
        $user = Auth::user();
        $profile = $user->profile()->firstOrCreate([]);

        if ($request->hasFile('image')) {
            if ($profile->image && str_starts_with($profile->image, 'storage/')) {
                Storage::delete(str_replace('storage/', 'public/', $profile->image));
            }
            $path = $request->file('image')->store('public/profile');
            $profile->image = str_replace('public/', 'storage/', $path);
        }
        $profile->nickname = $request->nickname;
        $profile->postal_code = $request->postal_code;
        $profile->address = $request->address;
        $profile->building = $request->building;
        $profile->save();
        
        return redirect()->route('items.index')->with('status', 'プロフィールを更新しました');
    }
}
