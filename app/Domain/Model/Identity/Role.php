<?php


namespace App\Domain\Model\Identity;
use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;


class Role extends Model
{

    protected $table = 'roles';
    protected $fillable = ['roleid', 'tenantid', 'name', 'description', 'groupsplayingrole', 'usersplayingrole', 'scopes'];

    public function __construct($roleid = null, $tenantid = null,  $name = null, $description = null, $groupsplayingrole='[]', $usersplayingrole='[]', $scopes = '[]', $attributes = array()){

        parent::__construct($attributes);
        $this->roleid = $roleid;
        $this->tenantid = $tenantid;
        $this->name = $name;
        $this->description = $description;
        $this->groupsplayingrole = $groupsplayingrole;
        $this->usersplayingrole = $usersplayingrole;
        $this->scopes = $scopes;

    }

}
