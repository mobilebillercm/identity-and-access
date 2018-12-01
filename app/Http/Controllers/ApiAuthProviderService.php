<?php

namespace App\Http\Controllers;

use App\Domain\Model\Identity\Role;
use App\User;
use function GuzzleHttp\Promise\all;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ApiAuthProviderService extends Controller
{
    use AuthenticatesUsers;

    protected $roleController;

    public function __construct()
    {
        $this->roleController = new RoleController();
    }


    protected function authenticated(Request $request)
    {
        // implement your user role retrieval logic, for example retrieve from `roles` database table



        $username = $request->get('username');
        $tenantid = $request->get('tenantid');


        $users = User::where('email', '=', $username)->where('tenantid', '=',  $tenantid)->get();

        $user = null;

        if(count($users) === 1){

            $user = $users[0];
        }
        else{

            return  json_encode(array('error'=>'invalid_credentials', 'message'=>'The user credentials were incorrect'), 200);
        }

        $roles = Role::all();
        $scopes = [];

        foreach ($roles as $role){

            if ($this->roleController->isUserInRole($role->roleid, $user->userid, $user->tenantid)){

                //return $this->roleController->isUserInRole($role->roleid, $user->userid);

                $roleScopes = json_decode($role->scopes, true);

                for($i = 0; $i < count($roleScopes); $i++){

                    array_push($scopes, $roleScopes[$i]);
                }
            }

        }

        //return $scopes;



        $request->request->add(['scope' => join(" ", $scopes)]);

        //return $request->get('scope');

        // grant scopes based on the role that we get previously




        //return $request->headers;

        // forward the request to the oauth token request endpoint
        $tokenRequest = Request::create(
            '/oauth/token',
            'post'
        );
        return Route::dispatch($tokenRequest);
    }

    protected function validateAccessTokAndRelatedScopes(Request $request){

        //return response($request->user(), 200);



        $fp = fopen('t.txt', 'w');
        fprintf($fp, '%s', $request->get('Authorization'));
        fclose($fp);

        $scopesJsonString = file_get_contents('php://input');
        $scope =  json_decode($scopesJsonString, true);

        //return json_encode($scope['scope']);

        return response($request->user()->tokenCan($scope['scope']) ? '1' : '0', 200);


    }


}

/*
 *
 *   Manage-Activity-Category Manage-Activity-Requests
 */
