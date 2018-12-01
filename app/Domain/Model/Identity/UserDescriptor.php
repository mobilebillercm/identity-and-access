<?php

namespace App\Domain\Model\Identity;


class UserDescriptor
{

    public $tenantid;
    public $username;
    public $email;
    public $tenant_name;
    public $tenant_description;
    public $userid;
    public $name;
    public $phone;
    public $numcontribuable;
    public $numregistrecommerce;




    public function __construct($tenantid, $username, $email, $name, $tenant_name, $tenant_description, $userid,  $phone, $numcontribuable, $numregistrecommerce)
    {


       $this->tenantid = $tenantid;
       $this->username = $username;
       $this->email = $email;
       $this->name = $name;

       $this->tenant_name = $tenant_name;
       $this->tenant_description = $tenant_description;
       $this->userid = $userid;

       $this->phone = $phone;
       $this->numcontribuable = $numcontribuable;
       $this->numregistrecommerce = $numregistrecommerce;
    }


}
