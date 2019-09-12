<?php

namespace Neoan3\Apps;

use Neoan3\Model\IndexModel;
use PHPUnit\Framework\TestCase;

require '../vendor/neoan3-model/index/Index.model.php';
require '../vendor/autoload.php';
require '../Transformer.php';
require 'mockTransformer.php';


class TransformerTest extends TestCase
{

    private $yourDb = 'db_app';
    private $hitId;
    private $transformerInstance;
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        Db::setEnvironment(['assumes_uuid'=>true,'name'=>$this->yourDb]);
        $user = Db::easy('user.id',['^delete_date'],['limit'=>[0,1]]);
        if(!empty($user)){
            $this->hitId = $user[0]['id'];
        } else {
            var_dump('This test requires db-connectivity and tables reflecting the mockTransformer.php structure.');
            die();
        }
        $this->transformerInstance = new Transformer(\MockTransformer::class,'user' , __DIR__ .'/mockMigrate.json');
    }

    public function test__construct()
    {
        var_dump(IndexModel::first(['some']));
    }
    public function testCreate()
    {

        $result = $this->transformerInstance::create([
            'email' => [
                'email' => 'some@other.com'
            ],
            'password' => [
                'password' => 'foobarbaz'
            ],
            'userName' => 'sam'
        ]);
        $this->assertIsArray($result,'Failed. Db connection for test set?');
        $this->assertArrayHasKey('id',$result,'No id generated!');
        $this->hitId = $result['id'];
    }

    public function testGet()
    {
        $r = $this->transformerInstance::get($this->hitId);
        $this->assertIsArray($r);
    }


    public function cleanUp()
    {

    }
}
