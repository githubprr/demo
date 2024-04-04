<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Haruncpi\LaravelUserActivity\Traits\Loggable;
class User extends Authenticatable
{
    use Notifiable, HasApiTokens, SoftDeletes;
    use Loggable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'privileges',            
        'company_id',
        'designation',
        'mobile',
        'total_experience',
        'experience_in',
        'address',
        'country_id',
        'state_id',
        'district_id',
        'taluka_id',
        'village_id',
        'wallet_points',
        'referral_code',
        'referred_by',
        'verified',
        'lat',
        'lng',
        'photo',
        'category_group_id'
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

    public function OauthAcessToken(){
        return $this->hasMany('\App\Models\OauthAccessToken');
    }

    public function privilege() {
        return $this->hasOne(Privilege::class, 'id', 'privileges');
    }

    public function requisitions()
    {
        return $this->hasMany(Requisitions::class, 'user_id', 'id');
    }

    public function enquiries()
    {
        return $this->hasMany(Enquiries::class, 'user_id', 'id');
    }

    public function purchase_orders()
    {
        return $this->hasMany(PurchaseOrder::class, 'user_id', 'id');
    }

    public function sales_orders()
    {
        return $this->hasMany(SalesOrder::class, 'user_id', 'id');
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'user_id', 'id');
    }

    public function country() {
        return $this->belongsTo(CountryMaster::class, 'country_id', 'id')->withTrashed();
    }

    public function state() {
        return $this->belongsTo(StateMaster::class, 'state_id', 'id')->withTrashed();
    }

    public function district() {
        return $this->belongsTo(DistrictMaster::class, 'district_id', 'id')->withTrashed();
    }

    public function taluka() {
        return $this->belongsTo(TalukaMaster::class, 'taluka_id', 'id')->withTrashed();
    }

    public function village() {
        return $this->belongsTo(VillageMaster::class, 'village_id', 'id')->withTrashed();
    }

    public function company() {
        return $this->belongsTo(CompanyMaster::class, 'company_id', 'id');
    }

    public function items() {
        return $this->hasMany(ItemMaster::class, 'companies_id', 'id');
    }

    public function added_by_company() {
        return $this->belongsTo(User::class, 'added_by', 'id')->withTrashed();
    }

    public function child_company() {
        return $this->hasMany(User::class, 'added_by', 'id');
    }

    public function subscription() {
        return $this->hasOne(Subscription::class, 'id', 'user_id');
    }

    public function category_group() {
        return $this->belongsTo(CategoryGroupMaster::class, 'category_group_id', 'id')->withTrashed();
    }
}
