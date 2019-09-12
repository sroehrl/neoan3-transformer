<?php

namespace Neoan3\Apps;

use Exception;
use Neoan3\Model\IndexModel;

class Transformer
{
    private static $knownModels = [];
    private static $transformer = [];
    private static $model = '';
    private static $migratePath;
    private static $assumesUuid;
    function __construct($transformer, $model, $migratePath = false, $assumesUuid = true){
        self::$transformer = $transformer;
        self::$model = $model;
        self::$migratePath = $migratePath;
        self::$assumesUuid = $assumesUuid;
    }
    static function __callStatic($name, $arguments)
    {
        $givenId = isset($arguments[1]) ? $arguments[1] : false;
        if(method_exists(self::class,$name)){
            return call_user_func_array([self::class,$name],$arguments);
        } else {
            $parts =  preg_split('/([A-Z])/',$name,-1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            // does base-method exist?
            if(!method_exists(self::class,$parts[0])){
                throw new Exception('Magic method must start with either "get", "create", "update" or "delete"');
            }
            $function = $parts[0];
            $subModel = '';
            for($i=1;$i<count($parts);$i++){
                $subModel .= $parts[$i];
            }
            $subModel = lcfirst($subModel);
            $arguments[0] = [$subModel => $arguments[0]];

            return self::$function($arguments[0], $givenId, $subModel);
        }
    }

    /**
     * @param $model
     *
     * @return array
     * @throws Exception
     */
    private static function getStructure($model){
        if(isset(self::$knownModels[$model])){
            return self::$knownModels;
        }
        return IndexModel::getMigrateStructure($model, self::$migratePath);
    }



    /**
     * @param $id
     *
     * @return array|mixed
     * @throws DbException
     * @throws Exception
     */
    static function get($id){
        $structure = self::getStructure(self::$model);

        $transformer = self::$transformer::modelStructure();
        // transformer-translations?
        $translatedTransformer = [];
        foreach ($transformer as $tableOrColumn => $values){
            if(isset($values['translate'])){
                $translatedTransformer[$values['translate']] = $values;
                $translatedTransformer[$values['translate']]['translate'] = $tableOrColumn;
            } else {
                $translatedTransformer[$tableOrColumn] = $values;
            }
        }
        $includeDeleted = false;
        $queries = [self::$model=>[]];
        $model = self::$model;
        foreach ($structure as $tableOrColumn => $definition){
            reset($definition);
            if(is_array($definition[key($definition)])){
                // is table
                if(isset($translatedTransformer[$tableOrColumn]['protection']) && $translatedTransformer[$tableOrColumn]['protection'] == 'hidden'){
                    continue;
                }
                $queries[$tableOrColumn]['as'] = isset($translatedTransformer[$tableOrColumn]['translate'])?$translatedTransformer[$tableOrColumn]['translate'] : $tableOrColumn;;
                $queries[$tableOrColumn]['where'] = [];
                if(!$includeDeleted){
                    $queries[$tableOrColumn]['where'] = ['^delete_date'];
                }
                $queries[$tableOrColumn]['depth'] = isset($translatedTransformer[$tableOrColumn]['depth'])?$translatedTransformer[$tableOrColumn]['depth'] : 'many';
                foreach ($definition as $key =>$value){
                    if($key == $model .'_id'){
                        $queries[$tableOrColumn]['where'][$key] = '$'.$id;
                    }
                    // default
                    $queries[$tableOrColumn]['select'][$key] = $tableOrColumn.'.'.$key;
                    if(isset($translatedTransformer[$tableOrColumn])){
                        // onRead?
                        if(isset($translatedTransformer[$tableOrColumn]['on_read']) && isset($translatedTransformer[$tableOrColumn]['on_read'][$key])){
                            $queries[$tableOrColumn]['select'][$key] = $translatedTransformer[$tableOrColumn]['on_read'][$key]($key);
                        }
                    }
                }
            } else {
                // is key of main
                $queries[$model]['depth'] = 'main';
                $queries[$model]['as'] = $model;
                if($tableOrColumn == 'id'){
                    $queries[$model]['where'][$tableOrColumn] = '$'.$id;
                }
                // default
                $queries[$model]['select'][$tableOrColumn] = $model.'.'.$tableOrColumn;
                if(isset($translatedTransformer[$tableOrColumn])){
                    // is hidden/protected?
                    if(isset($translatedTransformer[$tableOrColumn]['protection'])){
                        // do nothing for now
                        unset($queries[$model]['select'][$tableOrColumn]);
                    } else {
                        // onRead?
                        if(isset($translatedTransformer[$tableOrColumn]['on_read'])){
                            $queries[$model]['select'][$tableOrColumn] = $translatedTransformer[$tableOrColumn]['on_read']($tableOrColumn);
                        }
                        // translate?
                        if(isset($translatedTransformer[$tableOrColumn]['translate'])){
                            $queries[$model]['select'][$tableOrColumn] .= ':' . $translatedTransformer[$tableOrColumn]['translate'];
                        }
                    }
                }
            }
        }
        foreach ($queries as $i =>$query){
            $queries[$i]['select'] = implode(' ',array_values($queries[$i]['select']));
        }
        $entity = [];

        foreach ($queries as $table => $query){
            switch ($query['depth']){
                case 'main': $entity = IndexModel::first(Db::easy($query['select'],$query['where'])); break;
                case 'one': $entity[$query['as']] = IndexModel::first(Db::easy($query['select'],$query['where'])); break;
                case 'many': $entity[$query['as']] = Db::easy($query['select'],$query['where']); break;
            }
        }
        return $entity;
    }

    /**
     * @param      $obj
     *
     * @param bool $subModel
     *
     * @param bool $givenId
     *
     * @return array
     * @throws DbException
     */
    static function create($obj, $givenId = false, $subModel = false)
    {
        $toDb = self::prepareForTransaction($obj,$givenId,$subModel);

        foreach ($toDb as $table => $values){
            Db::ask($table,$values);

        }
        if(isset($toDb[self::$model]['id']) || $givenId){
            $id = $givenId ? $givenId : $toDb[self::$model]['id'];
            return self::get(self::sanitizeId($id));
        }
        return $toDb;
    }

    static function update($obj, $givenId, $subModel = false){
        $existingEntity = self::find(['id'=>$givenId],false, $subModel);
        if(empty($existingEntity)){
            throw new Exception('Cannot find entity to update');
        }
        $merged = $existingEntity[0];
        if(!$subModel){
            foreach ($obj as $key => $value){
                if(isset($merged[$key])){
                    $merged[$key] = $value;
                }
            }
        }
        var_dump($merged);
        $toDb = self::prepareForTransaction($merged,$givenId,$subModel,'update');
        var_dump($toDb);
        die();
    }

    static function find($obj, $void = false, $subModel = false){
        $structure = self::$transformer::modelStructure();
        $table = self::$model;
        $qualifier = 'id';
        $condition = [];
        $results = [];
        if($subModel){
            $qualifier = self::$model . '_id';
            $structure = $structure[$subModel];
            $table = isset($structure['translate']) ? $structure['translate'] : $subModel;
        }
        foreach ($structure as $columnOrTable => $values){
            if(isset($obj[$columnOrTable])){
                $prefix = (substr(strtolower($columnOrTable),-2) == 'id' && self::$assumesUuid) ? '$' : '';
                if(isset($values['translate'])){
                    $condition[$values['translate']] = $prefix . $obj[$columnOrTable];
                } else {
                    $condition[$columnOrTable] = $prefix . $obj[$columnOrTable];
                }

            }
        }
        $ids = Db::easy($table . '.' . $qualifier,$condition);
        foreach ($ids as $id){
            $results[] = self::get($id[$qualifier]);
        }
        return $results;
    }

    private static function prepareForTransaction($passIn, $givenId = false, $subModel = false, $crudOperation = 'create'){
        $structure = self::$transformer::modelStructure($givenId);
        $transform = IndexModel::validateAgainstTransformer($passIn, $structure, $crudOperation, $subModel);
        return IndexModel::flatten(self::$model,$transform);
    }

    /**
     * @param $idString
     *
     * @return string|string[]|null
     */
    private static function sanitizeId($idString){
        if(is_numeric($idString)){
            return $idString;
        } else {
            return preg_replace('/\$|UNHEX|\(|\)/','',$idString);
        }
    }
}
