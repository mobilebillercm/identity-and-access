<?php

namespace App\Http\Controllers;

use App\Domain\Model\Identity\Group;
use App\Domain\Model\Identity\GroupMember;
use Illuminate\Http\Request;
use Webpatser\Uuid\Uuid;
use App\User;


class GroupController extends Controller
{


    public function createNewGroup(Request $request){



        $validator = Validator::make(

            $request->all(),
            [
                'name' => 'required|string|min:1|max:100',
                'description' => 'required|string|min:1|max:1000',
                'tenantid' => 'required|string|min:1|max:150',
            ]
        );

        if ($validator->fails()) {
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $validator->errors()->first()), 200);
        }

        $groupToRegister = new Group(
            Uuid::generate()->string,
            $request->get('tenantid'),
            $request->get('name'),
            $request->get('description'),
            "[]");


        $groupToRegister->save();

        return response(array('success' => 1, 'faillure' => 0, 'response' => 'Group creation successfull'), 200);
    }

    public function addGroupMemberToGroup(Request $request, $groupid)
    {

        $groupToMemberToJsonString = file_get_contents('php://input');
        $groupToMemberToArray =  json_decode($groupToMemberToJsonString, true);
        $userid = $groupToMemberToArray['userid'];
        $tenantid = $groupToMemberToArray['tenantid'];


        //return $userid;
        //Get the user or Get the group

        $ausertoadd = User::where('userid', '=', $userid)->where('tenantid', '=',  $tenantid)->get();


        //return $ausertoadd[0];

        //Get the group we are adding the user to
        $agrouptoaddto = Group::where('groupid', '=', $groupid)->get();




        if (count($ausertoadd) === 1 && count($agrouptoaddto) === 1 ) {

            //Get current groups members
            $currentgroupmembers = json_decode($agrouptoaddto[0]->members);



            //Verify user to add is not already in the list
            for($i=0; $i<count($currentgroupmembers);$i++){

                if($currentgroupmembers[$i] === $ausertoadd[0]->userid){
                    return response(array('success'=>0, 'faillure'=>1, 'raison'=>'User already a member'));
                }
            }


            $newgroupmembers = [];

            //Add new member to the list



            array_push($newgroupmembers, $ausertoadd[0]->userid);

            //Add previous elts
            for($i=0; $i<count($currentgroupmembers);$i++){

                array_push($newgroupmembers, $currentgroupmembers[$i]);

            }

            //Update Group member list
            $agrouptoaddto[0]->members = json_encode($newgroupmembers);

            //return $agrouptoaddto[0]->members;


            $agrouptoaddto[0]->save();


            return response(array('success'=>1, 'faillure'=>0, 'response'=>'User added successfully'));

            //return $agrouptoaddto[0]->members;



        }
        else{

            return response(array('success'=>0, 'faillure'=>1, 'raison'=>'Wrong state'));


        }


    }

    public function retrieveAllGroup(Request $request){

        return Group::all();
    }

    public function notGroupMembers(Request $request, $groupid){

        $groups = Group::where('groupid', '=', $groupid)->where('tenantid', '=',  $request->get('tenantid'))->get();

        if(count($groups) === 1){

            $group = $groups[0];


            $currentGroupMembers = json_decode($group->members);


            if(count($currentGroupMembers) === 0){

                return User::where('tenantid', '=',  $request->get('tenantid'))->where('userid', '!=', env('BASE_ADMIN_USER_ID'))->get(['userid','firstname', 'lastname']);
            }
            else{

                return User::where('tenantid', '=',  $request->get('tenantid'))->where('userid', '!=', env('BASE_ADMIN_USER_ID'))->whereNotIn('userid',$currentGroupMembers)->get(['userid','firstname', 'lastname']);
            }



        }


    }




}
