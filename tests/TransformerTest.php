<?php

namespace Neoan3\Apps;

use Neoan3\Model\IndexModel;
use PHPUnit\Framework\TestCase;
use PHPUnit\Util\Exception;

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

        try{
            Db::ask('>Truncate user');
            Db::ask('>Truncate user_email');
            Db::ask('>Truncate user_password');
        } catch (DbException $e){
            var_dump($e->getMessage());
            die();
        }

        $this->transformerInstance = new Transformer(\MockTransformer::class,'user' , __DIR__ .'/mockMigrate.json');
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
        $this->assertIsString($result['id']);
        return $result['id'];
    }

    /**
     *
     * @depends testCreate
     *
     * @param $givenId
     *
     * @return String
     * @throws DbException
     */
    public function testGet($givenId)
    {
        $r = $this->transformerInstance::get($givenId);
        $this->assertIsArray($r);
        $this->assertIsString($r['id']);
        return $r['id'];
    }

    public function testFind(){
        $r = $this->transformerInstance::find(['userName'=>'sam']);
        $this->assertIsArray($r,'Could not resolve');
        $this->assertArrayHasKey(0,$r,'Empty result');
        $this->assertArrayHasKey('id',$r[0],'Result has wrong format');
    }
    public function testFindMagic(){
        $r = $this->transformerInstance::findEmail(['email'=>'some@other.com']);
        $this->assertIsArray($r,'Could not resolve');
        $this->assertArrayHasKey(0,$r,'Empty result');
        $this->assertArrayHasKey('id',$r[0],'Result has wrong format');
    }
    /**
     * @depends testGet
     *
     * @param $givenId
     */
    public function testUpdate($givenId){

        $r = $this->transformerInstance::update(['userName'=>'Josh'],$givenId);
        $this->assertTrue(true);
    }


}
