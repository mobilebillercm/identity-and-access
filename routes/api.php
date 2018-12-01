<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/




Route::post('/access-token', 'ApiAuthProviderService@authenticated');

Route::post('/confirm-access-token', 'ApiAuthProviderService@validateAccessTokAndRelatedScopes')->middleware(['auth:api']);

Route::post('/lougout-user/{username}/{tenantid}', 'UserController@logout')->middleware(['auth:api']);

Route::get('/users', function (){
    return \App\User::where('userid', '!=', env('BASE_ADMIN_USER_ID'))->get(['userid', 'firstname', 'lastname', 'email', 'enablement']);
})->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::get('/is-user-exists/{userid}', 'UserController@isUserExists');

Route::post('/roles', 'RoleController@createNewRole')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::get('/roles', 'RoleController@retrieveAllRoles')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::post('/roles/{roleid}/groups-playing-role', 'RoleController@addGroupToRole')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::get('/roles/{roleid}/groups-playing-role', 'RoleController@retrieveGroupsPlayingRole')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::get('/roles/{roleid}/groups-not-playing-role', 'RoleController@groupsNotPlayingRole')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::post('/roles/{roleid}/users-playing-role', 'RoleController@addUserToRole')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::get('/roles/{roleid}/users-not-playing-role', 'RoleController@usersNotPlayingRole')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::get('/roles/{roleid}/is-user-in-role/{userid}/tenants/{tenantid}', 'RoleController@isUserInRole')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::post('/groups', 'GroupController@createNewGroup')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::get('/groups', 'GroupController@retrieveAllGroup')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::post('/groups/{groupid}/members', 'GroupController@addGroupMemberToGroup')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::get('/groups/{groupid}/members', 'GroupController@retrieveGroupMembers')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::get('/groups/{groupid}/not-members', 'GroupController@notGroupMembers')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::get('/scopes', 'ScopeController@retrieveAllScopes')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]); //tested

Route::post('/users/{userid}/deactivate', 'UserController@deactivateUser')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::post('/users/{userid}/reactivate', 'UserController@reactivateUser')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);




/////MOBILE BILLER FUNCTIONS
Route::post('/tenants-provisions', 'TenantController@provisionTenant');

Route::post('/tenants/{tenantid}/account-reactivation', 'TenantController@reactivateTenantAccount')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::post('/tenants/{tenantid}/account-deactivation', 'TenantController@deactivateTenantAccount')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_IDENTITIES_AND_ACCESSES')]);

Route::post('/users/{username}/password-reset-request', 'UserController@requestPasswordReset');

Route::post('/users/{username}/password-reset/{passwordresetinvitationid}', 'UserController@resetPassword');

Route::post('/users-invitations', 'UserController@inviteUserToRegister')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_COLLABORATORS')]);

Route::get('/users/{inviteduserid}/registration-invitations', 'UserController@getInvitedUserById');

Route::post('/users/{inviteduserid}/registration-invitations', 'UserController@registerInvitedUser');

Route::post('/users/{username}/login', 'UserController@login');

Route::post('/users/{username}/verify', 'UserController@verifyPassword')->middleware('wallet.client');

Route::post('/users/{username}/change-password', 'UserController@changePassword')->middleware(['auth:api']);

Route::get('tenants/{id}/logo', 'TenantController@getLogo')->middleware(['auth:api']);

Route::post('/users-subaccounts', 'UserController@createSubAccount')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_COLLABORATORS')]);

Route::get('/users/{userid}/{tenantid}/subaccounts', 'UserController@getSubaccount')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_SUBACCOUNT_ACCESSES')]);

Route::put('/users/{userid}/{tenantid}/desable', 'UserController@desableUser')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_ACCOUNT_ACCESSES')]);

Route::put('/users/{userid}/{tenantid}/enable', 'UserController@enableUser')->middleware(['auth:api','scopes:'.env('SCOPE_MANAGE_ACCOUNT_ACCESSES')]);













Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


