<?php

namespace App\Http\Controllers;

use App\Domain\Model\Identity\Scope;
use Illuminate\Http\Request;


class ScopeController extends Controller
{

    public function retrieveAllScopes(Request $request){
      /*  $scopes = Scope::all();
        $keyvals = array();
        foreach ($scopes as $scope){
            //$keyvals[$scope->s_key] = $scope->description;
           array_push($keyvals, array("$scope->s_key"=>"$scope->description"));
        }*/

       /* return [
            //Scopes User Resource
            'POST-users-invitations' => 'Invite User Scope',
            'POST-users-inviteduserid-registration-invitations' => 'Register User Scope',
            'GET-users-inviteduserid-registration-invitations' => 'Retrieve Invited User',
            'POST-users-username-change-password' => 'Change User Password Scope',

            //Scopes Role Resource
            'POST-roles' => 'Create Role Scope',
            'GET-roles' => 'Retrieve Role Scope',
            'POST-roles-roleid-groups-playing-role' => 'Add Group To Role Scope',
            'GET-roles-roleid-groups-playing-role' => 'Retrieve Groups Playing Role Scope',
            'GET-roles-roleid-groups-not-playing-role' => 'Retrieve Groups Not Playing Role Scope',
            'POST-roles-roleid-users-playing-role' => 'Add User To Role Scope',
            'GET-roles-roleid-users-not-playing-role' => 'Retrieve Users not playing Role Scope',
            'GET-roles-roleid-is-user-in-role-userid' => 'Is User In Role Scope',

            //Scopes Group Resource
            'POST-groups'=>'Create Group',
            'GET-groups'=>'RetrieveGroup',
            'POST-groups-groupid-members'=>'Create Group Members',
            'GET-groups-groupid-members'=>'Retrieve Group Members',
            'GET-groups-groupid-not-members'=>'Retrieve Not Group Members',

        ];*/

        //return $keyvals;

        return Scope::all();
    }

}
