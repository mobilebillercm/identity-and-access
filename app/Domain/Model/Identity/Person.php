<?php
namespace App\Domain\Model\Identity;
use Illuminate\Database\Eloquent\Model;


class Person extends Model
{

    protected $table = 'persons';
    protected $fillable = ['personid', 'firstname', 'lastname', 'name', 'email', 'phone'];


    public function __construct($personid = null, $firstname = null, $lastname = null, $name = null, $email = null, $phone = null,  $attributes = array())
    {
        parent::__construct($attributes);
        $this->personid = $personid;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
    }


}
