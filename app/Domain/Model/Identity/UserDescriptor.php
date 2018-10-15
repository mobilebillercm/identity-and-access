<?php

namespace App\Domain\Model\Identity;


class UserDescriptor
{

    public $tenantid;
    public $username;
    public $email;
    public $name;



    public function __construct($tenantid, $username, $email, $name)
    {

       $this->tenantid = $tenantid;
       $this->username = $username;
       $this->email = $email;
       $this->name = $name;
    }


}
