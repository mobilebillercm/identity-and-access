<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    /*    protected $fillable = [
            'name', 'email', 'password',
        ];*/

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];




    //protected $table = 'confirmed_users';
    protected $fillable = ['userid', 'tenantid', 'parent', 'firstname', 'lastname', 'enablement', 'username', 'password', 'email', 'phone', 'remember_token',];


    public function __construct($userid = null, $tenantid = null, $parent = null, $firstname = null, $lastname = null, $enablement = null, $email = null, $phone = null,  $username = null, $password = null, $attributes = array())
    {
        parent::__construct($attributes);

        $this->userid = $userid;
        $this->tenantid = $tenantid;
        $this->parent = $parent;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->enablement = $enablement;
        $this->username = $username;
        $this->password = $password;
        $this->email = $email;
        $this->phone = $phone;
    }


}
