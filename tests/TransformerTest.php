<?php

namespace Neoan3\Apps;

use Neoan3\Model\IndexModel;
use PHPUnit\Framework\TestCase;
use Exception;

require '../vendor/neoan3-model/index/Index.model.php';
require '../vendor/autoload.php';
require '../TransformValidator.php';
require '../Transformer.php';
require 'mockTransformer.php';


/**
 * Class TransformerTest
 *
 * @package Neoan3\Apps
 */
class TransformerTest extends TestCase
{

    /**
     * @var string
     */
    private $yourDb = 'db_app';
    /**
     * @var Transformer
     */
    private $transformerInstance;

    /**
     * TransformerTest constructor.
     *
     * @param null   $name
     * @param array  $data
     * @param string $dataName
     *
     * @throws DbException
     */
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


    /**
     * @return mixed
     * @throws DbException
     */
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
        $this->validateModel($result);
        return $result['id'];
    }
    /**
     *
     * @depends testCreate
     *
     * @param $givenId
     *
     */
    public function testCreateMagicFailure($givenId){
        // try duplicate
        $this->expectException(Exception::class);
        $this->transformerInstance::createEmail([
            'email' => 'some@other.com'
        ],$givenId);
    }
    /**
     *
     * @depends testCreate
     *
     * @param $givenId
     *
     */
    public function testCreateMagic($givenId){
        $result = $this->transformerInstance::createEmail([
            'email' => 'some2nd@other.com'
        ],$givenId);
        $this->validateModel($result);
    }

    /**
     * @throws DbException
     */
    public function testCreateFailure(){
        $this->expectException(Exception::class);
        $this->transformerInstance::create([
            'email' => 'some@other.com',
            'password' => [
                'password' => 'foobarbaz'
            ],
            'userName' => 'sam'
        ]);
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
        $this->validateModel($r);
        return $r['id'];
    }

    /**
     *
     */
    public function testFind(){
        $r = $this->transformerInstance::find(['userName'=>'sam']);
        $this->validateModel($r, true);

    }

    /**
     *
     */
    public function testFindMagic(){
        $r = $this->transformerInstance::findEmail(['email'=>'some@other.com']);
        $this->validateModel($r, true);
    }

    /**
     * @depends testGet
     *
     * @param $givenId
     *
     * @throws \Exception
     */
    public function testUpdate($givenId){
        $r = $this->transformerInstance::update(['userName'=>'Josh'],$givenId);
        $this->validateModel($r);
    }
    /**
     * @depends testGet
     *
     * @param $givenId
     *
     * @throws \Exception
     */
    public function testUpdateFailure($givenId){
        // expect duplicate
        $this->expectException(Exception::class);
        $this->transformerInstance::update(['userName'=>'Josh'],$givenId);
    }
    /**
     * @depends testGet
     *
     * @param $givenId
     *
     * @throws \Exception
     */
    public function testUpdateMagic($givenId){
        $user = $this->transformerInstance::get($givenId);
        $this->validateModel($user);
        // according to mock-transformer this should never be done. BUT: we are testing
        $t = $this->transformerInstance::updateEmail(['email' => 'some@other.com'],$givenId);
        $this->validateModel($t);

    }

    /**
     * @param      $model
     * @param bool $multi
     */
    private function validateModel($model, $multi=false){
        $this->assertIsArray($model,'Could not resolve');
        if($multi){
            $this->assertArrayHasKey(0,$model,'Empty result');
            $this->assertArrayHasKey('id',$model[0],'Result has wrong format');
        } else {
            $this->assertIsArray($model,'Could not retrieve user');
            $this->assertArrayHasKey('email',$model,'User model format issue: can\'t find email in model.');
        }

    }


}
