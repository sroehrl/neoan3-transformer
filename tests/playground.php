<?php

use Neoan3\Apps\Db;
use Neoan3\Apps\Transformer;
use Neoan3\Model\IndexModel;

require '../vendor/autoload.php';
require '../vendor/neoan3-model/index/Index.model.php';
require '../Transformer.php';
require 'mockTransformer.php';

// set db
Db::setEnvironment(['assumes_uuid'=>true,'name'=>'db_app']);

$t = new Transformer(MockTransformer::class,'user');
try{
    $d = Transformer::create([
        'email' => [
            'email' => 'some@other.com'
        ],
        'password' => [
            'password' => 'foobarbaz'
        ],
        'userName' => 'sam'
    ]);
} catch (Exception $e){
    var_dump($e->getMessage());
    die();
}

var_dump($d);
die();