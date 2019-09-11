<?php
class MockTransformer
{
    static function modelStructure(){
        $mainId = 'asdfkja982734siefndj';
        return [
            'id' => [
                'on_creation' => function($input) use ($mainId){
                    $mainId = $input ? $input : $mainId;
                    return '$'. $mainId;
                }
            ],
            'inserted'=>[
                'translate' => 'insert_date',
                'on_read' => function($input){ return '#user.'.$input;}
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
