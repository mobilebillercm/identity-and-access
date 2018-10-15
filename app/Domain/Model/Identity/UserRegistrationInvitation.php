<?php

namespace App\Domain\Model\Identity;

use Illuminate\Database\Eloquent\Model;

class UserRegistrationInvitation extends Model
{

    protected $table = 'users_registration_invitations';
    protected $fillable = ['userid', 'tenantid', 'firstname', 'lastname', 'email', 'phone', 'invited_by', 'invited_at', 'url', 'active'];


    public function __construct($userid = null, $tenantid = null, $firstname = null, $lastname = null,$email = null,$phone = null,
                                $invited_by = null, $invited_at = null, $url = null, $active = null, $attributes = array())
    {

        parent::__construct($attributes);
        $this->userid = $userid;
        $this->tenantid = $tenantid;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->phone = $phone;
        $this->invited_by = $invited_by;
        $this->invited_at = $invited_at;
        $this->url = $url;
        $this->active = $active;
    }
}
