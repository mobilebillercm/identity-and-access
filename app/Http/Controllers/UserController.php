<?php

namespace App\Http\Controllers;

use App\Domain\GlobalDbRecordCounter;
use App\Domain\GlobalResultHandler;
use App\Domain\Model\Identity\Role;
use App\Domain\Model\Identity\UserRegistrationInvitation;
use App\Domain\Model\Identity\PasswordResetInvitation;
use App\Domain\Model\Identity\Person;
use App\Domain\Model\Identity\Tenant;
use App\Jobs\ProcessUserChangePassword;
use App\User;
use App\Domain\Model\Identity\UserDescriptor;
use App\Jobs\ProcessMessages;
use App\Jobs\ProcessRegistrationMessageWithPwd;
use App\Jobs\ProcessSendMail;
use App\Mail\MailNotificator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Webpatser\Uuid\Uuid;


class UserController extends Controller
{




    public  function requestPasswordReset(Request $request, $username){


        $validator = Validator::make(

            $request->all(),
            [
                'tenantid' => 'required|string|min:1|max:150',
            ]
        );


        if ($validator->fails()) {
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $validator->errors()->first()), 200);
        }



        $tenantsForUserToResetPassword = Tenant::where('tenantid', '=', $request->get('tenantid'))->get();

        if(!(count($tenantsForUserToResetPassword) === 1)){
            return response(array('success'=>0, 'faillure'=>1, 'raison'=>'No Tenant found'));
        }


        $usersToResetPassword = User::where('username', '=', $username)->where('tenantid', '=', $request->get('tenantid'))->get();

        if(!(count($usersToResetPassword) === 1)){
            return response(array('success'=>0, 'faillure'=>1, 'raison'=>'No User found for the specified tenant'));
        }


        $userToResetPassword = $usersToResetPassword[0];

        if(!($userToResetPassword->enablement)){
            return response(array('success'=>0, 'faillure'=>1, 'raison'=>'Your account is deactivated'));
        }


        $currentValidPasswordResetInvitations = PasswordResetInvitation::where('email', '=', $username)->where('tenantid', '=', $request->get('tenantid'))->where('used', '=', false)->get();

        if((count($currentValidPasswordResetInvitations) === 1)){



            $currentValidPasswordResetInvitation = $currentValidPasswordResetInvitations[0];


            $url = env('HOST_WEB_CLIENT_DOMAIN').'/users/' . $userToResetPassword->userid . '/password-reset/'.$currentValidPasswordResetInvitation->invitationid;
	    //$url = 'https://mobilebiller.idea-cm.club'.'/users/' . $userToResetPassword->userid . '/password-reset/'.$currentValidPasswordResetInvitation->invitationid;
            $to =  $userToResetPassword->email;

            $mailNotificator = new MailNotificator("PASSWORD RESET",
                [' ', 'Dear  '  . $userToResetPassword->firstname, 'Please use the link below to reset your password', ' ', 'Sincerely,'],
                $url, 'mails.password-reset-temp');

            ProcessSendMail::dispatch($to, $mailNotificator);


            return response(array('success' => 1, 'faillure' => 0, 'response' => 'Use this link to reset your password '.$url), 200);


        }elseif ((count($currentValidPasswordResetInvitations) > 1)){

            return response(array('success' => 0, 'faillure' => 1, 'raison' => 'Multiple Password Reset Invitations found'), 200);

        }else {


            $passwordResetInvitation = new PasswordResetInvitation(
                Uuid::generate()->string,
                $userToResetPassword->tenantid,
                $userToResetPassword->firstname,
                $userToResetPassword->lastname,
                $userToResetPassword->username
            );


            DB::beginTransaction();

            try {

                $passwordResetInvitation->save();


            } catch (\Exception $e) {

                DB::rollBack();

                return response(array('success' => 0, 'faillure' => 1, 'raison' => 'Unable to Create a Password Reset Invitation'), 200);

            }

            DB::commit();

            $url = env('HOST_WEB_CLIENT_DOMAIN') . '/users/' . $userToResetPassword->userid . '/password-reset/' . $passwordResetInvitation->invitationid;
	    //$url = 'https://mobilebiller.idea-cm.club' . '/users/' . $userToResetPassword->userid . '/password-reset/' . $passwordResetInvitation->invitationid;
            $to = $userToResetPassword->email;

            $mailNotificator = new MailNotificator("PASSWORD RESET",
                [' ', 'Dear  ' . $userToResetPassword->firstname, 'Please use the link below to reset your password', ' ', 'Sincerely,'],
                $url, 'mails.password-reset-temp');

            ProcessSendMail::dispatch($to, $mailNotificator);
        }


        return response(array('success' => 1, 'faillure' => 0, 'response' => 'Use this link to reset your password '.$url), 200);


    }

    public  function resetPassword(Request $request, $username, $invitationid){



        //return $request->get('email');


        $validator = Validator::make(

            $request->all(),
            [
                'newpassword' => 'required|string|min:6|max:150',
                'email' => 'required|string|min:6|max:150',
            ]
        );


        if ($validator->fails()) {
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $validator->errors()->first()), 200);
        }


        DB::beginTransaction();

        try {


            $currentValidPasswordResetInvitations = PasswordResetInvitation::where('invitationid', '=', $invitationid)->
            where('email', '=', $request->get('email'))->where('used', '=', false)->get();

            //return json_encode($currentValidPasswordResetInvitations[0]->tenantid);

            if(!(count($currentValidPasswordResetInvitations) === 1)){

                return response(array('success'=>0, 'faillure'=>1, 'raison'=>'No Password Reset Invitation found'));

            }


            $usersToResetPassword = User::where('userid', '=', $username)->where('tenantid', '=', $currentValidPasswordResetInvitations[0]->tenantid)->get();

            if(!(count($usersToResetPassword) === 1)){
                return response(array('success'=>0, 'faillure'=>1, 'raison'=>'No User found for the specified tenant'));
            }


            $userToResetPassword = $usersToResetPassword[0];

            if(!($userToResetPassword->enablement)){
                return response(array('success'=>0, 'faillure'=>1, 'raison'=>'Your account is deactivated'));
            }




            $newpassword = $request->get('newpassword');


            $currentValidPasswordResetInvitations[0]->used = true;


            $userToResetPassword->password = bcrypt($newpassword);

            $userToResetPassword->save();
            $currentValidPasswordResetInvitations[0]->update();

        } catch (\Exception $e) {

            DB::rollBack();

            return response(array('success' => 0, 'faillure' => 1, 'raison' => 'Unable to reset Password'), 200);

        }

        DB::commit();


        $userToResetPassword->password = $newpassword;

        ProcessUserChangePassword::dispatch(env('USER_PASSWORD_CHANGE_SSL_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($userToResetPassword));


        return response(array('success' => 1, 'faillure' => 0, 'response' => 'Password Reset successfully'), 200);



    }

    public function inviteUserToRegister(Request $request){


        $validator = Validator::make(

            $request->all(),
            [
                'firstname' => 'required|string|min:1|max:100',
                'lastname' => 'required|string|min:1|max:100',
                'email' => 'required|email|min:1|max:50',
                'tenantid' => 'required|string|min:1|max:150',
                'phone1' => ['required', 'regex:/^(22|23|24|67|69|65|68|66)[0-9]{7}$/'],
                'invited_by' => 'required|string|min:1|max:100',
            ]
        );

        if ($validator->fails()) {
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $validator->errors()->first()), 200);
        }


        $tenantsForUserToInvite = Tenant::where('tenantid', '=', $request->get('tenantid'))->get();

        if(!(count($tenantsForUserToInvite) === 1)){

            return response(array('success' => 0, 'faillure' => 1, 'raison' => "Tenant not found"), 200);

        }


        $userRegistrationInvitions = UserRegistrationInvitation::where('email', '=',  $request->get('email'))
            ->where('invited_by', '=',  $request->get('invited_by'))
            ->where('tenantid', '=',  $request->get('tenantid'))->get();


        $url = null;


        $userRegistrationInvition = null;

        if (count($userRegistrationInvitions) > 0){
            $userRegistrationInvition =  $userRegistrationInvitions[0];
            $url = $userRegistrationInvitions[0]->url;
        }else{

            $userRegistrationInvition = new UserRegistrationInvitation(Uuid::generate()->string, $request->get('tenantid'), $request->get('firstname'),  $request->get('lastname'),
                $request->get('email'),  $request->get('phone1'),  $request->get('invited_by'), date('Y-m-d H:i:s',time()),
                null, 1);

            $userRegistrationInvition->save();

            $url = env('HOST_WEB_CLIENT_DOMAIN').'/users/' . $userRegistrationInvition->userid . '/registration-invitations/'.$userRegistrationInvition->tenantid;
            $userRegistrationInvition->url = $url;
            $userRegistrationInvition->save();
        }



        $to =  $request->get('email');


        $mailNotificator = new MailNotificator("REGISTRATION INVITATION",
            [' ', 'Dear ' . $request->get('firstname'), 'Please use the link below to register into the system', ' ', 'Sincerely,'],
            $url, 'mails.invitation-registration-temp');

        //Todo Ne doit pas etre appeler lors de l'enregistrement
        ProcessMessages::dispatch(env('TENANT_COLLABORATOR_INVITED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($userRegistrationInvition));


        ProcessSendMail::dispatch($to, $mailNotificator);

        //Mail::to($to)->send($mailNotificator);

        return response(array('success' => 1, 'faillure' => 0, 'response' => 'Invitation sent Successfully. The user ' . $to . ' Will receive the link: ' .
            $url . ' that will allow him to register into the system'), 200);
    }

    public  function  registerInvitedUser(Request $request, $inviteduserid){

        $validator = Validator::make($request->all(),
            [
               'password'=>'required|string|min:6',
                'password_confirmation'=>'required|string|min:6',
                'tenantid'=>'required|string|min:1',
            ]);

        if ($validator->fails()){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $validator->errors()->first()), 200);
        }


        if (($request->get('password') === null) or strlen($request->get('password')) < 6 ) {

            return response(array('success' => 0, 'faillure' => 1, 'raison' => "Password must be at leat 6 characters"), 200);
        }


        if (!($request->get('password') === $request->get('password_confirmation'))) {

            return response(array('success' => 0, 'faillure' => 1, 'raison' => "Password does not match"), 200);
        }



        $password = $request->get('password');


        $tenants = Tenant::where('tenantid', '=', $request->get('tenantid'))->get();

        if(!GlobalDbRecordCounter::countDbRecordIsExactlelOne($tenants)){

            return GlobalResultHandler::buildFaillureReasonArray('Tenant found');
        }


        //Verify user was invited
        $userRegistrationInvitations = UserRegistrationInvitation::where('userid', '=', $inviteduserid)->where('tenantid', '=', $request->get('tenantid'))->get();

        $foundUserRegistrationInvitationId = null;

        if(count($userRegistrationInvitations) === 1 and $userRegistrationInvitations[0]->active === 1){

            $foundUserRegistrationInvitationId = $userRegistrationInvitations[0]->userid;

        }

        if($inviteduserid === $foundUserRegistrationInvitationId ){
            $encryptedpassword = bcrypt($password);
        } else{
            return response(array('success' => 0, 'faillure' => 1, 'raison' => 'Registration yet done'), 200);
        }


        $usersRoles  = Role::where('tenantid', '=', $request->get('tenantid'))->where('name', '=', env('ROLE_TENANT_USERS'))->get();
        if (!(count($usersRoles) === 1)){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => 'ROLE_TENANT_USERS not found'), 200);
        }


        //Note the second email is actually the username

        DB::beginTransaction();

        try{


            $aNewUser = new User($inviteduserid,  $userRegistrationInvitations[0]->tenantid, $userRegistrationInvitations[0]->invited_by, $userRegistrationInvitations[0]->firstname,  $userRegistrationInvitations[0]->lastname, true,
                $userRegistrationInvitations[0]->email,  $userRegistrationInvitations[0]->phone, $userRegistrationInvitations[0]->email, $encryptedpassword);

            $aNewUser->save();


            $usersRole = $usersRoles[0];
            $usersPlayingTenantUserRole = json_decode($usersRole->usersplayingrole);
            array_push($usersPlayingTenantUserRole, $inviteduserid);
            $usersRole->usersplayingrole = json_encode($usersPlayingTenantUserRole);

            $usersRole->save();


            $userRegistrationInvitations[0]->active = 0;
            $userRegistrationInvitations[0]->save();

        }catch (\Exception $e){

            DB::rollBack();

            return response(array('success' => 0, 'faillure' => 1, 'raison' => $e->getMessage()), 200);

        }

        DB::commit();

        //Rabbitmq must be secured
        $aNewUser->password = $request->get('password');
        $aNewUser->tenantname = $tenants[0]->name;


        ProcessRegistrationMessageWithPwd::dispatch(env('REGISTERED_USERS_SSL_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($aNewUser));

        $aNewUser->password = '';

        ProcessMessages::dispatch(env('REGISTERED_USERS_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($aNewUser));

        return response(array('success' => 1, 'faillure' => 0, 'response' => 'Registration successfull'), 200);

    }

    public  function login(Request $request, $username){

        $password = $request->get('password');

        //retrieve user with given username
        $userWithUsernameArray = User::where('username', '=', $username)->where('tenantid', '=', $request->get('tenantid'))->get();
        //retrieve user tenant

        $userTenants = Tenant::where('tenantid', '=', $request->get('tenantid'))->get();

        if(!(count($userTenants) === 1)){
            return response(array('success'=>0, 'faillure' => 1, 'raison' => "Tenant not found"), 200);
        }

        $userTenant = $userTenants[0];

        if(count($userWithUsernameArray) === 1){

            $userWithUsername = $userWithUsernameArray[0];
        }
        else{

            return response(array('success'=>0, 'faillure' => 1, 'raison' => "User not found"), 200);
            //return "multiple users were returned!";
        }
        //Check the tenant and user are enabled

        if(!($userTenant->enablement)){

            return response(array('success'=>0, 'faillure' => 1, 'raison' => "Tenant Account disabled"), 200);

        }

        if(!($userWithUsername->enablement)){

            return response(array('success'=>0, 'faillure' => 1, 'raison' => "User Account disabled"), 200);
            //return "useris disabled!";
        }

        //check the given password matches
        if (Hash::check($password, $userWithUsername->password))
        {
            $userdescriptor = new UserDescriptor($userWithUsername->tenantid, $userWithUsername->username, $userWithUsername->email,
                $userWithUsername->lastname.' '.$userWithUsername->firstname, $userTenant->name, $userTenant->description,
                $userWithUsername->userid, $userWithUsername->phone, $userTenant->taxpayernumber, $userTenant->numbertraderegister );



            return response(array('success'=>1, 'faillure' => 0, 'response' => $userdescriptor), 200);
            //return response($userdescriptor, 200);
        }
        return response(array('success'=>0, 'faillure' => 1, 'raison' => "Bad credentials"), 200);
    }

    public  function verifyPassword(Request $request, $username){


       // return $request->all();

        $password = $request->get('password');

        //retrieve user with given username
        $userWithUsernameArray = User::where('username', '=', $username)->where('tenantid', '=', $request->get('tenantid'))->get();
        //retrieve user tenant
        $userTenants = Tenant::where('tenantid', '=', $request->get('tenantid'))->get();

        if(!(count($userTenants) === 1)){
            return response(array('success'=>0, 'faillure' => 1, 'raison' => "Tenant not found  "), 200);
        }

        $userTenant = $userTenants[0];

        if(count($userWithUsernameArray) === 1){

            $userWithUsername = $userWithUsernameArray[0];
        } else{

            return response(array('success'=>0, 'faillure' => 1, 'raison' => "User not found"), 200);
            //return "multiple users were returned!";
        }
        //Check the tenant and user are enabled

        if(!($userTenant->enablement)){
            return response(array('success'=>0, 'faillure' => 1, 'raison' => "Tenant Account disabled"), 200);
        }

        if(!($userWithUsername->enablement)){

            return response(array('success'=>0, 'faillure' => 1, 'raison' => "User Account disabled"), 200);
            //return "useris disabled!";
        }

        //check the given password matches
        if (Hash::check($password, $userWithUsername->password))
        {

            $userdescriptor = new UserDescriptor($userWithUsername->tenantid, $userWithUsername->username, $userWithUsername->email, $userWithUsername->lastname.' '.$userWithUsername->firstname);

            return response(array('success'=>1, 'faillure' => 0, 'response' => $userdescriptor), 200);
            //return response($userdescriptor, 200);
        }
        return response(array('success'=>0, 'faillure' => 1, 'raison' => "Bad credentials"), 200);
    }

    public  function changePassword(Request $request, $username){


        $oldpassword = $request->get('oldpassword');
        $newpassword = $request->get('newpassword');

        $newpasswordconfirmation = $request->get('newpasswordconfirmation');


        if(strlen($newpassword ) < 6){

            return response(array('success'=>0, 'faillure'=>1, 'raison'=>'Password must be at least 6 characters'));

        }



        if(!($newpassword === $newpasswordconfirmation)){

            return response(array('success'=>0, 'faillure'=>1, 'raison'=>'Password confirmation failled'));

        }

        //retrieve user with given username
        $userWithUsernameArray = User::where('username', '=', $username)->where('tenantid', '=', $request->get('tenantid'))->get();

        if(count($userWithUsernameArray) === 1){

            $userWithUsername = $userWithUsernameArray[0];
        }
        else{

            return response(array('success'=>0, 'faillure'=>1, 'raison'=>'No User found'));

        }
        //Check the user is enabled

        if(!($userWithUsername->enablement)){

            return response(array('success'=>0, 'faillure'=>1, 'raison'=>'Account Disabled'));

        }


        //check the given password matches
        if (Hash::check($oldpassword, $userWithUsername->password))
        {

            $userWithUsernameArray[0]->password = $newpassword;

            $userToPubplish = $userWithUsernameArray[0];

            $userWithUsernameArray[0]->password = bcrypt($newpassword);

            $userWithUsernameArray[0]->save();

            $userToPubplish->password = $newpassword;


            ProcessUserChangePassword::dispatch(env('USER_PASSWORD_CHANGE_SSL_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($userToPubplish));


            return response(array('success'=>1, 'faillure'=>0, 'response'=>'Password changed successfully'));
        } else{
            return response(array('success'=>0, 'faillure'=>1, 'raison'=>'Current password incorrect!'));

        }




    }

    public function getInvitedUserById(Request $request, $inviteduserid){
        $invitedusers = UserRegistrationInvitation::where('userid', '=', $inviteduserid)->where('tenantid', '=', $request->get('tenantid'))->get();
        if (count($invitedusers) === 1){
            return response(array('success'=>1, 'faillure'=>0, 'response'=>$invitedusers[0]));
        }
        return response(array('success'=>0, 'faillure'=>1, 'raison'=>'Something went wrong'));
    }

    public function isUserExists(Request $request, $userid){

        $users = User::where('userid', '=', $userid)->where('tenantid', '=', $request->get('tenantid'))->get();
        if (count($users) === 1){
            return "1";
        }
        return "0";
    }

    public function deactivateUser(Request $request, $userid){

        $users = User::where('userid', '=', $userid)->where('tenantid', '=', $request->get('tenantid'))->get();

        if (!count($users) === 1){
            return response(array('success'=>0, 'faillure'=>1, 'response'=>'Something went wrong'));
        }

        $user = $users[0];

        $user->enablement = false;

        $userTokens = $user->tokens;


        foreach($userTokens as $token) {
            $token->revoke();
        }

        $user->save();

        $user->password = '';


        ProcessMessages::dispatch(env('DEACTIVATED_USERS_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($user));

        return response(array('success'=>1, 'faillure'=>0, 'response'=>'User deactivated successfully'));



    }

    public function reactivateUser(Request $request, $userid){

        $users = User::where('userid', '=', $userid)->where('tenantid', '=', $request->get('tenantid'))->get();
        if (!count($users) === 1){
            return response(array('success'=>0, 'faillure'=>1, 'response'=>'Something went wrong'));
        }

        $user = $users[0];

        $user->enablement = true;

        $user->save();

        $user->password = '';

        ProcessMessages::dispatch(env('REACTIVATED_USERS_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($user));

        return response(array('success'=>1, 'faillure'=>0, 'response'=>'User reactivated successfully'));



    }

    public  function createPerson(Request $request){


        $validator = Validator::make(

            $request->all(),
            [
                //'firstname' => 'required|string|min:1|max:100',
                //'lastname' => 'required|string|min:1|max:100',
                'name' => 'required|string|min:1|max:500',
                'email' => 'required|email|min:1|max:50',
                'phone' => ['required', 'regex:/^(22|23|24|67|69|65|68|66)[0-9]{7}$/'],
            ]
        );

        if ($validator->fails()) {
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $validator->errors()), 200);
        }

        $person = new Person(Uuid::generate()->string, $request->get('firstname'), $request->get('lastname'),
            $request->get('name'),  $request->get('email'),  $request->get('phone'));



        $person->save();


        return response(array('success' => 1, 'faillure' => 0, 'response' => "Pesron created successfully"), 200);



    }

    public  function getPersons(){

        return response(array('success'=>1, 'faillure'=>0, 'response'=>Person::all()));

    }

    public function logout($username, $tenantid){


        $users = User::where('username', '=', $username)->where('tenantid', '=', $tenantid)->get();

        if (!count($users) === 1){
            return response(array('success'=>0, 'faillure'=>1, 'response'=>'User not found'));
        }

        $user = $users[0];


        $userTokens = $user->tokens;


        foreach($userTokens as $token) {
            $token->revoke();
        }

        return response(array('success'=>1, 'faillure'=>0, 'response'=>'User logged out successfully'));


    }

    public function createSubAccount(Request $request){

        $validator = Validator::make(

            $request->all(),
            [
                'firstname' => 'required|string|min:1|max:100',
                'lastname' => 'required|string|min:1|max:100',
                'email' => 'required|email|min:1|max:50',
                'tenantid' => 'required|string|min:1|max:150',
                'phone1' => ['required', 'regex:/^(22|23|24|67|69|65|68|66)[0-9]{7}$/'],
                'invited_by' => 'required|string|min:1|max:100',
            ]
        );

        if ($validator->fails()) {
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $validator->errors()->first()), 200);
        }


        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $tenants = Tenant::where('tenantid', '=', $request->get('tenantid'))->get();

        if(!GlobalDbRecordCounter::countDbRecordIsExactlelOne($tenants)){

            return GlobalResultHandler::buildFaillureReasonArray('Tenant found');
        }


        $usersRoles  = Role::where('tenantid', '=', $request->get('tenantid'))->where('name', '=', env('ROLE_TENANT_USERS'))->get();
        if (!(count($usersRoles) === 1)){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => 'ROLE_TENANT_USERS not found'), 200);
        }



        $encryptedpassword = bcrypt($request->get('phone1'));

        //Note the second email is actually the username

        DB::beginTransaction();

        try{



            $userId = Uuid::generate()->string;

            $aNewUser = new User($userId, $request->get('tenantid'), $request->get('invited_by'),
                $request->get('firstname'),  $request->get('lastname'), true,
                $request->get('email'),  $request->get('phone1'), $request->get('email'), $encryptedpassword);
            $aNewUser->save();

            $usersRole = $usersRoles[0];
            $usersPlayingTenantUserRole = json_decode($usersRole->usersplayingrole);
            array_push($usersPlayingTenantUserRole, $userId);
            $usersRole->usersplayingrole = json_encode($usersPlayingTenantUserRole);

            $usersRole->save();



        }catch (\Exception $e){
            DB::rollBack();
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $e->getMessage()), 200);
        }

        DB::commit();

        //Rabbitmq must be secured
        $aNewUser->password = $request->get('phone1');
        $aNewUser->tenantname = $tenants[0]->name;


        ProcessRegistrationMessageWithPwd::dispatch(env('REGISTERED_USERS_SSL_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($aNewUser));

        $aNewUser->password = '';

        ProcessMessages::dispatch(env('REGISTERED_USERS_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($aNewUser));

        return response(array('success' => 1, 'faillure' => 0, 'response' => 'Ajouter avec Succes'), 200);

    }


    public function getSubaccount(Request $request, $userid, $tenantid){
        $subAccounts = User::where('tenantid', '=', $tenantid)->where('parent','=', $userid)->get();
        return response(array('success'=>1, 'faillure'=>0, 'response'=>$subAccounts));
    }

    public function desableUser(Request $request, $userid, $tenantid){
        $users = User::where('tenantid', '=', $tenantid)->where('userid','=', $userid)->get();
        if (!(count($users) === 1)){
            return response(array('success'=>0, 'faillure'=>1, 'raison'=>"User not found"));
        }



        $user = $users[0];
        $user->enablement = 0;

        $userTokens = $user->tokens;


        foreach($userTokens as $token) {
            $token->revoke();
        }
        $user->save();
        return response(array('success'=>1, 'faillure'=>0, 'response'=>"Utilisateur Desactive avec succes"));

    }

    public function enableUser(Request $request, $userid, $tenantid){
        $users = User::where('tenantid', '=', $tenantid)->where('userid','=', $userid)->get();
        if (!(count($users) === 1)){
            return response(array('success'=>0, 'faillure'=>1, 'raison'=>"User not found"));
        }
        $user = $users[0];
        $user->enablement = 1;
	$user->save();
        return response(array('success'=>1, 'faillure'=>0, 'response'=>"Utilisateur Reactive avec succes"));
    }

}
