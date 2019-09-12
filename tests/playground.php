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
/*Db::ask('>Truncate user');
Db::ask('>Truncate user_email');
Db::ask('>Truncate user_password');*/

$t = new Transformer(MockTransformer::class,'user', __DIR__ .'/mockMigrate.json');

$id = 'abcd';

var_dump($t::createEmail(['email' => 'some3@sother.com'],$id));
die();

try{
    $d = Transformer::create([
        'email' => [
            'email' => 'some@sother.com'
        ],
        'password' => [
            'password' => 'foobarbaz'
        ],
        'userName' => 'samy'
    ]);
} catch (Exception $e){
    var_dump($e->getMessage());
    die();
}

var_dump($d);
die();
