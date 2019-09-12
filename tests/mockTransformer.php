<?php

use Neoan3\Apps\Db;

require_once '../vendor/autoload.php';

class MockTransformer
{
    private static function checkUnique($input,$all){
        $e = Db::easy('user_email.id',['email'=>$all['email']['email'],'^delete_date']);
        $u = Db::easy('user.id',['user_name'=>$all['userName']]);
        if(empty($e) && empty($u)){
            return $input;
        } else {
            throw new Exception((empty($u) ? 'Email' : 'User name'). ' not unique');
        }
    }
    static function modelStructure(){
        $mainId = Db::uuid()->uuid;
        return [
            'id' => [
                'on_creation' => function($input) use ($mainId){
                    $mainId = $input ? $input : $mainId;
                    return '$'. $mainId;
                }
            ],
            'inserted'=>[
                'translate' => 'insert_date',
                'on_read' => function($input){ return '#user.'.$input;},
                'on_creation' => function($input,$all){return self::checkUnique($input, $all);}
            ],
            'userName'=>[
                'required'=>true,
                'translate' => 'user_name'
            ],
            'email' => [
                'translate' =>'user_email',
                'required' => true,
                'depth' => 'one',
                'required_fields' => ['email'],
                'on_creation' =>[
                    'confirm_code' => function(){
                        return 'Ops::hash(23)';
                    },
                    'user_id' => function() use ($mainId){ return '$' . $mainId;}
                ],
                'on_read' =>[
                    'insert_date' =>function($input){ return '#user_email.'.$input.':inserted';}
                ]
            ],
            'password' => [
                'translate' =>'user_password',
                'protection' =>'hidden',
                'required' => true,
                'required_fields' => ['password'],
                'depth' => 'one',
                'on_creation' => [
                    'password' => function($input){
                        return '=' . password_hash($input, PASSWORD_DEFAULT);
                    },
                    'confirm_code' => function(){return 'somehash';},
                    'user_id' => function() use ($mainId){ return '$' . $mainId;}
                ]
            ]
        ];
    }

}
