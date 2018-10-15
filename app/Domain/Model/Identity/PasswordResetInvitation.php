<?php

namespace App\Domain\Model\Identity;

use Illuminate\Database\Eloquent\Model;

class PasswordResetInvitation extends Model
{

    protected $table = 'password_reset_invitations';
    protected $fillable = ['invitationid', 'tenantid', 'firstname', 'lastname', 'email', 'used'];



    public function __construct($invitationid = null, $tenantid = null, $firstname =null, $lastname =null, $email = null, $attributes = array())
    {
        parent::__construct($attributes);
        $this->invitationid = $invitationid;
        $this->tenantid = $tenantid;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->used = false;

    }

}
