<?php

namespace App\Domain\Model\Identity;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{

    protected $table = 'groups';
    protected $fillable = ['groupid', 'tenantid', 'name', 'description', 'members'];



    public function __construct($groupid = null, $tenantid = null, $name =null, $description = null, $members = null, $attributes = array())
    {
        parent::__construct($attributes);
        $this->groupid = $groupid;
        $this->tenantid = $tenantid;
        $this->description = $description;
        $this->name = $name;
        $this->members = $members;

    }

}
