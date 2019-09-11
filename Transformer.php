<?php

namespace Neoan3\Apps;

use Exception;
use Neoan3\Model\IndexModel;

class Transformer
{
    private static $knownModels = [];
    private static $transformer = [];
    private static $model = '';
    function __construct($transformer, $model){
        self::$transformer = $transformer;
        self::$model = $model;
    }

    private static function getStructure($model){
        if(isset(self::$knownModels[$model])){
            return self::$knownModels;
        }
        try {
            return IndexModel::getMigrateStructure($model);
        } catch (Exception $e){
            return false;
        }
    }

    /**
     * @param $id
     *
     * @return array|mixed
     * @throws DbException
     */
    static function get($id){
        $id = $id[0];

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
    static function create($obj)
    {
        $transform = IndexModel::validateAgainstTransformer($obj,self::$transformer::modelStructure());
        $toDb = IndexModel::flatten(self::$model,$transform);
        /*foreach ($toDb as $table => $values){
            Db::$table($values);
        }*/
        var_dump($toDb);
    }
}
