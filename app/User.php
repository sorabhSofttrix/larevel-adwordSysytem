<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\Models\Media;

class User extends Authenticatable implements JWTSubject, HasMedia
{
    use Notifiable;
    use HasRoles, HasMediaTrait;

    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'about', 'tag_line', 'parent_id', 'add_by'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function registerMediaConversions(Media $media = null)
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200);
    }

    public function children() {
        return $this->hasMany('App\User', 'parent_id');
    }

    public function parent() {
        return $this->belongsTo('App\User', 'parent_id')->with('parent');
    }

    public function dashboardData(){
        $role = $this->Roles()->pluck('id')->first();
        $accountsAray = array( 'all'=>0, 'closed'=>0, 'active'=>0, 'paused'=>0);
        $allAccount = [];
        $accQuery = AdwordsAccount::
                    select('id','g_acc_id','acc_status')
                    ->where('acc_status','!=','requiredSetup');
        switch ($role) {
            case 1:
                $accounts = $accQuery->get();
                break;
            case 2:
                $id= [];
                $ids = User::select('id')->where('parent_id', '=', $this->id)->get()->toArray();
                foreach ($ids as $key => $value) { $id[] = $value['id']; }
                $accounts = $accQuery->whereIn('account_director', $id)
                            ->get(); 
                break;
            case 3:
                $accounts = $accQuery->where('account_director', '=', $this->id)
                            ->get(); 
                break;
            case 4:
                $accounts = $accQuery->where('account_manager', '=', $this->id)
                            ->get();
                break;
        }
        $accountsAray['all'] =  count($accounts);
        $accountsAray['closed'] = count(array_filter($accounts->toArray(), function ($var) {
                                    return ($var['acc_status'] == 'closed');
                                }));
        $accountsAray['active'] = count(array_filter($accounts->toArray(), function ($var) {
            return ($var['acc_status'] == 'active');
        }));
        $accountsAray['paused'] = count(array_filter($accounts->toArray(), function ($var) {
            return ($var['acc_status'] == 'paused');
        }));
        return array('accounts' => $accountsAray);
    }
}