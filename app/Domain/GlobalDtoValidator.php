<?php
/**
 * Created by PhpStorm.
 * User: el
 * Date: 9/20/18
 * Time: 11:03 AM
 */

namespace App\Domain;



use Illuminate\Support\Facades\Validator;

class GlobalDtoValidator
{
    public static function  validateData(Array $fiedlstovalidate, Array $rules){

        return Validator::make($fiedlstovalidate, $rules);

    }

    public static function  requireStringMinMax(int $min, int $max){

        return 'required|string|min:'.$min.'|max:'.$max ;

    }

    public static function  requireNumeric(){

        return 'required|numeric';

    }

    public static function  requireInteger(){

        return 'required|integer';

    }



}