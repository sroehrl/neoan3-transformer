<?php

namespace Neoan3\Apps;

require '../vendor/autoload.php';
require '../Transformer.php';
require 'mockTransformer.php';

class TransformerTest extends \PHPUnit_Framework_TestCase
{

    public function test__construct()
    {

    }

    public function testGet()
    {

    }

    public function testCreate()
    {
        Db::setEnvironment(['assumes_uuid'=>true,'name'=>'db_app']);
        $t = new Transformer(\MockTransformer::class,'user');
        $t::create([
            'email' => [
                'email' => 'some@other.com'
            ],
            'password' => [
                'password' => 'foobarbaz'
            ],
            'userName' => 'sam'
        ]);
    }
}
