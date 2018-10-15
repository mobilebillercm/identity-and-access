<?php

namespace App\Http\Controllers;

use App\Domain\Model\Identity\Category;
use App\Domain\Model\Identity\UserRegistrationInvitation;
use App\Domain\Model\Identity\Person;
use App\Domain\Model\Identity\Tenant;
use App\Jobs\ProcessAllResourceFiles;
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

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use phpDocumentor\Reflection\Types\Array_;
use Webpatser\Uuid\Uuid;




class TenantController extends Controller
{


    public function provisionTenant(Request $request)
    {

        //return $request->all();

        $validator = Validator::make(

            $request->all(),
            [
                'tenantname' => 'required|string|min:1|max:100',
                'tenantdescrition' => 'required|string|min:1|max:1000',
                'tenantcity' => 'required|string|min:1|max:150',
                'tenantregion' => 'required|string|min:1|max:150',
                'tenantlogo' => 'required|image',
                'administratorfirstname' => 'required|string|min:1|max:100',
                'administratorlastname' => 'required|string|min:1|max:100',
                'administratoremail' => 'required|email|min:1|max:50',
                'adminitratorpassword'=> 'required|string|min:1|max:100',
                'adminitratorpassword_confirmation'=> 'required|string|min:1|max:100',
                'administratorphone' => ['required', 'regex:/^(22|23|24|67|69|65|68|66)[0-9]{7}$/']
            ]
        );


        if ($validator->fails()) {
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $validator->errors()->first()), 200);
        }

        if( $request->get('adminitratorpassword') !==  $request->get('adminitratorpassword_confirmation')){

            return response(array('success' => 0, 'faillure' => 1, 'raison' => "Password Confirmation Failled"), 200);

        }

        $tenatlogo = $request->file('tenantlogo');

        $tenatlogo_path = null;

        if (!($tenatlogo === null) and $tenatlogo->isValid()) {
            $tenatlogo_path = Storage::disk('local')->put('logos', $tenatlogo);
        }


        $tenantToCreate = new Tenant(

            Uuid::generate()->string,
            $request->get('tenantname'),
            $request->get('tenantcity'),
            $request->get('tenantregion'),
            $request->get('tenantdescrition'),
            $tenatlogo_path,
            true
        );

        $tenantDefaultAdminUserToCreate = new User(
            Uuid::generate()->string,
            $tenantToCreate->tenantid,
            null,
            $request->get('administratorfirstname'),
            $request->get('administratorlastname'),
            true,
            $request->get('administratoremail'),
            $request->get('administratorphone'),
            $request->get('administratoremail'),
            $request->get('adminitratorpassword'));

        $tenantSslProvision  = array('tenant'=> $tenantToCreate, 'adminuser'=>$tenantDefaultAdminUserToCreate);


        ProcessRegistrationMessageWithPwd::dispatch(env('TENANT_SSL_PROVISIONED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($tenantSslProvision));

        DB::beginTransaction();

        try{

            $encryptepassword = bcrypt($tenantDefaultAdminUserToCreate->password);
            $tenantDefaultAdminUserToCreate->password = $encryptepassword;


            $tenantToCreate->save();
            $tenantDefaultAdminUserToCreate->save();

        }catch (\Exception $e){

            DB::rollBack();
            //return response(array('success' => 0, 'faillure' => 1, 'raison' => $e->getMessage()), 200);

            return response(array('success' => 0, 'faillure' => 1, 'raison' => 'Unable to Provision Tenant'), 200);

        }

        DB::commit();



        $tenantDefaultAdminUserToCreate->password = '';

        $tenantProvision  = array('tenant'=> $tenantToCreate, 'adminuser'=>$tenantDefaultAdminUserToCreate);



        ProcessMessages::dispatch(env('TENANT_PROVISIONED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($tenantProvision));


       /* $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');


        try {



            $channel = $connection->channel();

            $channel->exchange_declare('TENANT_PROVISIONED_EXCHANGE', 'fanout', false, true, false);


            $msg = new AMQPMessage( json_encode($tenantProvision), array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));

            $channel->basic_publish($msg, 'TENANT_PROVISIONED_EXCHANGE');
            $channel->close();

        } catch (\Exception $exception){




        }
        finally{

            if ($connection != null){
                $connection->close();
            }

        }*/





        return response(array('success' => 1, 'faillure' => 0, 'response' => 'Tenant Account Approvisioned Successfully'));
    }

    public  function  deactivateTenantAccount(Request $request, $tenantid){


        $tenantsToDeactivate = Tenant::where('tenantid', '=', $tenantid)->get();

        if(!(count($tenantsToDeactivate) === 1)){

            return response(array('success' => 0, 'faillure' => 1, 'raison' => 'No Tenant found'), 200);

        }

        $foundTenant = $tenantsToDeactivate[0];

        if($foundTenant->enablement == false){

            return response(array('success' => 0, 'faillure' => 1, 'raison' => 'Tenant account is already deactivated'), 200);

        }


        $foundTenant->enablement = false;

        DB::beginTransaction();

        try{


            $foundTenant->update();

        }catch (\Exception $e){

            DB::rollBack();

            return response(array('success' => 0, 'faillure' => 1, 'raison' => 'Unable to deactivate Teanant acoount'), 200);

        }


        DB::commit();



        ProcessMessages::dispatch(env('TENANT_ACCOUNT_DEACTIVATED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($foundTenant));





        return response(array('success' => 1, 'faillure' => 0, 'response' => 'Tenant deactivated successfully'), 200);

}

    public  function  reactivateTenantAccount(Request $request, $tenantid){


        $tenantsToReactivate = Tenant::where('tenantid', '=', $tenantid)->get();

        if(!(count($tenantsToReactivate) === 1)){

            return response(array('success' => 0, 'faillure' => 1, 'raison' => 'No Tenant found'), 200);

        }

        $foundTenant = $tenantsToReactivate[0];

        if($foundTenant->enablement == true){

            return response(array('success' => 0, 'faillure' => 1, 'raison' => 'Tenant account is already activated'), 200);

        }


        $foundTenant->enablement = true;

        DB::beginTransaction();

        try{


            $foundTenant->update();

        }catch (\Exception $e){

            DB::rollBack();

            return response(array('success' => 0, 'faillure' => 1, 'raison' => 'Unable to reactivate Teanant acoount'), 200);

        }


        DB::commit();



        ProcessMessages::dispatch(env('TENANT_ACCOUNT_REACTIVATED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($foundTenant));



        return response(array('success' => 1, 'faillure' => 0, 'response' => 'Tenant reactivated successfully'), 200);

    }



}
