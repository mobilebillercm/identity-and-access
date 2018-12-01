<?php
namespace App\Domain\Model\Identity;
use Illuminate\Database\Eloquent\Model;


class Tenant extends Model
{

    protected $table = 'tenants';
    protected $fillable = ['tenantid', 'name', 'city', 'region', 'description', 'logo', 'enablement', 'taxpayernumber', 'numbertraderegister'];


    public function __construct($tenantid = null, $name = null, $city = null, $region = null, $description = null, $logo = null, $enablement = null,
                                $taxpayernumber = null, $numbertraderegister = null, $attributes = array())
    {
        parent::__construct($attributes);
        $this->tenantid = $tenantid;
        $this->name = $name;
        $this->city = $city;
        $this->region = $region;
        $this->description = $description;
        $this->logo = $logo;
        $this->enablement = $enablement;
        $this->taxpayernumber = $taxpayernumber;
        $this->numbertraderegister = $numbertraderegister;

    }

}
