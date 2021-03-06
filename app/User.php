<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;
use Thomaswelton\LaravelGravatar\Facades\Gravatar;

class User extends Authenticatable
{
    const ADMIN_ROLE = 1;
    const PROFILE_PICTURE_THUMB_PATH = 'profile_pictures/thumbs/';
    const PROFILE_PICTURE_HIRES_PATH = 'profile_pictures/hires/';

    protected $hidden = ['password', 'remember_token'];

    protected $fillable = ['email', 'password'];

    public function isAdmin()
    {
        return $this->role == self::ADMIN_ROLE;
    }

    public function talks()
    {
        return $this->hasMany('Talk', 'author_id');
    }

    public function getTalksAttribute()
    {
        return $this->talks()->get()->sortBy(function ($talk) {
            return strtolower($talk->current()->title);
        });
    }

    public function bios()
    {
        return $this->hasMany('Bio')->orderBy('nickname');
    }

    public function conferences()
    {
        return $this->hasMany('conference', 'author_id');
    }

    public function favoritedConferences()
    {
        return $this->belongstoMany('Conference', 'favorites')->withTimestamps();
    }

    public function updateProfilePicture($filename)
    {
        $this->profile_picture = $filename;
        return $this->save();
    }

    public function getProfilePictureThumbAttribute()
    {
        if (! $this->profile_picture) {
            return Gravatar::src($this->email, 50);
        }

        return asset('/storage/' . self::PROFILE_PICTURE_THUMB_PATH . $this->profile_picture);
    }

    public function getProfilePictureHiresAttribute()
    {
        if (! $this->profile_picture) {
            return Gravatar::src($this->email, 500);
        }

        return asset('/storage/' . self::PROFILE_PICTURE_HIRES_PATH . $this->profile_picture);
    }

    protected static function boot()
    {
        parent::boot();

        // Cascade deletes
        static::deleting(function ($user) {
            $user->talks()->delete();
            // $user->conferences()->delete(); // Not sure if we want to do this.
            $user->bios()->delete();

            if ($user->profile_picture && strpos($user->profile_picture, '/') === false) {
                Storage::delete(User::PROFILE_PICTURE_THUMB_PATH . $user->profile_picture);
                Storage::delete(User::PROFILE_PICTURE_HIRES_PATH . $user->profile_picture);
            }

            \DB::table('favorites')->where('user_id', $user->id)->delete();
        });
    }
}
