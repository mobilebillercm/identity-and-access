<?php

namespace App\Http\Controllers;

use App\Domain\Model\Identity\Group;
use App\Domain\Model\Identity\Role;
use App\Providers\AuthServiceProvider;
use App\User;
use Illuminate\Http\Request;
use Webpatser\Uuid\Uuid;


class RoleController extends Controller
{

    public function createNewRole(){

        $roleToRegisterJsonString = file_get_contents('php://input');
        $roleToRegisterArray =  json_decode($roleToRegisterJsonString, true);


        $validator = Validator::make(

            $roleToRegisterArray->all(),
            [
                'name' => 'required|string|min:1|max:100',
                'description' => 'required|string|min:1|max:1000',
                'tenantid' => 'required|string|min:1|max:150',
            ]
        );

        if ($validator->fails()) {
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $validator->errors()->first()), 200);
        }


        $roleToRegister = new Role(
            Uuid::generate()->string,
            $roleToRegisterArray['tenantid'],
            $roleToRegisterArray['name'],
            $roleToRegisterArray['description'],
            "[]",
            "[]",
            $roleToRegisterArray['scopes']);

        $roleToRegister->save();

        return response(array('success' => 1, 'faillure' => 0, 'response' => 'Role creation successfull'), 200);
    }

    public function retrieveAllRoles(){

        return response(array('success'=>1, 'faillure'=>0, 'response'=>Role::all()));
    }

    public function addGroupToRole($roleid){

        $groupPlayingRoleJsonString = file_get_contents('php://input');
        $groupPlayingRoleArray =  json_decode($groupPlayingRoleJsonString, true);

        //Verify Role exists

        $roleArray = Role::where('roleid', '=', $roleid)->where('tenantid', '=',  $groupPlayingRoleArray['tenantid'])->get();

        $groupArray = Group::where('groupid', '=', $groupPlayingRoleArray['grouptoplayrole'])->where('tenantid', '=',  $groupPlayingRoleArray['tenantid'])->get();

        if(count($roleArray) === 1 && count($groupArray) === 1){

            //Get current groups
            $currentRoleGroups = json_decode($roleArray[0]->groupsplayingrole);

            //Verify group to add is not already in the list
            for($i=0; $i<count($currentRoleGroups);$i++){

                if($currentRoleGroups[$i] === $groupArray[0]->groupid){
                    return response(array('success'=>0, 'faillure'=>1, 'raison'=>"Group already playing role!"));
                    //return ;
                }
            }

            $newRoleGroups = [];

            //Add new group to the list

            array_push($newRoleGroups, $groupArray[0]->groupid);

            //Add previous elts
            for($i=0; $i<count($currentRoleGroups);$i++){

                array_push($newRoleGroups, $currentRoleGroups[$i]);

            }

            //Update Roles group list
            $roleArray[0]->groupsplayingrole = json_encode($newRoleGroups);


           $roleArray[0]->save();

            return response(array('success'=>1, 'faillure'=>0, 'response'=>'Group added successfully'));

        }
        else{

           // return "wrong results";
            return response(array('success'=>0, 'faillure'=>1, 'raison'=>'Something went wrong'));
        }

        //return $groupPlayingRoleArray['grouptoplayroleid'];

    }

    public function addUserToRole($roleid){

        $usersPlayingRoleJsonString = file_get_contents('php://input');
        $userPlayingRoleArray =  json_decode($usersPlayingRoleJsonString, true);

        //Verify User and Role exist

        $roleArray = Role::where('roleid', '=', $roleid)->where('tenantid', '=',  $userPlayingRoleArray['tenantid'])->get();
        $userArray = User::where('userid', '=', $userPlayingRoleArray['usertoplayrole'])->where('tenantid', '=',  $userPlayingRoleArray['tenantid'])->get();

        if(count($roleArray) === 1 && count($userArray) === 1){

            //Get current users playing this role
            $currentRoleUsers = json_decode($roleArray[0]->usersplayingrole);
            $currentRoleGroups = json_decode($roleArray[0]->groupsplayingrole);



            //Verify user to add is not already in the list of the users playing role
            for($i=0; $i<count($currentRoleUsers);$i++){

                if($currentRoleUsers[$i] === $userArray[0]->userid){
                    return response(array('success'=>0, 'faillure'=>1, 'raison'=>"User already playing role!"));
                    //return "User already playing role!";
                }
            }

            //Verify user to add is not already one of the groups playing the role
            for($j=0; $j<count($currentRoleGroups);$j++){


                $aGroupPlayingTheRole = Group::where('groupid','=',$currentRoleGroups[$j])->where('tenantid', '=',  $userPlayingRoleArray['tenantid'])->get();


                $aGroupPlayingTheRoleArray = json_decode($aGroupPlayingTheRole[0]->members);

                //return $aGroupPlayingTheRoleArray;

                for($k=0; count($aGroupPlayingTheRoleArray); $k++){

                    if($aGroupPlayingTheRoleArray[$k] === $userArray[0]->userid){

                        return response(array('success'=>0, 'faillure'=>1, 'raison'=>"User already in a group playing role!"));
                        //return "User already in a group playing role!";

                    }
                }
            }


            $newRoleUsers = [];

            //Add new group to the list

            array_push($newRoleUsers, $userArray[0]->userid);

            //Add previous elts
            for($i=0; $i<count($currentRoleUsers);$i++){

                array_push($newRoleUsers, $currentRoleUsers[$i]);

            }

            //Update Roles users list
            $roleArray[0]->usersplayingrole = json_encode($newRoleUsers);


            $roleArray[0]->save();

            return response(array('success'=>1, 'faillure'=>0, 'response'=>"User Added Successfully"));
            //return $roleArray[0]->usersplayingrole;

        }
        else{

            return response(array('success'=>0, 'faillure'=>1, 'raison'=>"Something went wrong"));
            //return "wrong results";
        }

        //return $groupPlayingRoleArray['grouptoplayroleid'];
    }

    public function isUserInRole($roleid, $userid, $tenantid){


        //Verify User and Role exist

        $roleArray = Role::where('roleid', '=', $roleid)->where('tenantid', '=',  $tenantid)->get();
        $userArray = User::where('userid', '=', $userid)->where('tenantid', '=',  $tenantid)->get();

        if(count($roleArray) === 1 && count($userArray) === 1){

            //Get current users playing this role
            $currentRoleUsers = json_decode($roleArray[0]->usersplayingrole);
            $currentRoleGroups = json_decode($roleArray[0]->groupsplayingrole);


            //Verify user is present in users playing role
            for($i=0; $i<count($currentRoleUsers);$i++){

                if($currentRoleUsers[$i] === $userArray[0]->userid){

                    return true;
                }
            }


            //var_dump($currentRoleGroups);

            //return count($currentRoleGroups);

            //Verify user to add is not already one of the groups playing the role
            for($j=0; $j<count($currentRoleGroups);$j++){


                $aGroupPlayingTheRole = Group::where('groupid','=',$currentRoleGroups[$j])->where('tenantid', '=',  $tenantid)->get();


                $aGroupPlayingTheRoleArray = json_decode($aGroupPlayingTheRole[0]->members);

                //return $aGroupPlayingTheRoleArray;

                for($k=0; $k< count($aGroupPlayingTheRoleArray); $k++){

                    if($aGroupPlayingTheRoleArray[$k] === $userArray[0]->userid){

                        return true;

                    }
                }
            }
        }
        else{
            return false;
        }

        return false;
    }

    public function groupsNotPlayingRole(Request $request, $roleid){

        $roles= Role::where('roleid', '=', $roleid)->where('tenantid', '=',  $request->get('tenantid'))->get();

        if(count($roles) === 1){

            $role = $roles[0];


            $currentRoleGroups = json_decode($role->groupsplayingrole);


            if(count($currentRoleGroups) === 0){

                return Group::all();
            }
            else{

                return Group::whereNotIn('groupid',$currentRoleGroups)->where('tenantid', '=',  $request->get('tenantid'))->get();
            }



        }


    }

    public function usersNotPlayingRole(Request $request, $roleid){

        $roles= Role::where('roleid', '=', $roleid)->where('tenantid', '=',  $request->get('tenantid'))->get();

        if(count($roles) === 1){

            $role = $roles[0];


            $currentUsersPlayingRole = json_decode($role->usersplayingrole);


            if(count($currentUsersPlayingRole) === 0){

                return User::where('tenantid', '=',  $request->get('tenantid'))->where('userid', '!=', env('BASE_ADMIN_USER_ID'))->get(['userid', 'firstname', 'lastname']);
            }
            else{

                return User::where('tenantid', '=',  $request->get('tenantid'))->where('userid', '!=', env('BASE_ADMIN_USER_ID'))->whereNotIn('userid',$currentUsersPlayingRole)->get(['userid', 'firstname', 'lastname']);
            }



        }


    }

}
