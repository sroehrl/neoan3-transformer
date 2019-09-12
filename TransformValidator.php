<?php


namespace Neoan3\Apps;


use Exception;

class TransformValidator
{


    static function validateStructureCreate($passIn, $structure, $depth=false){
        foreach ($structure as $tableOrField => $info) {
            // if missing
            if (isset($info['required']) && $info['required'] && !isset($passIn[$tableOrField])) {
                throw new Exception('Missing: ' . $tableOrField);
            }
            // deep?
            if (isset($info['depth']) && !$depth) {
                self::validateRequiredFields($info,$passIn[$tableOrField]);
                $passIn[$tableOrField] = self::validateStructureCreate($passIn[$tableOrField],$structure[$tableOrField]);
            }
            if (isset($info['on_creation'])) {

                foreach ($info['on_creation'] as $field => $transform) {
                    if ($info['depth'] == 'one') {
                        $value = isset($obj[$tableOrField][$field]) ? $obj[$tableOrField][$field] : false;
                        $obj[$tableOrField][$field] = $transform($value, $obj);
                    } else {
                        foreach ($obj[$tableOrField] as $i => $oneInMany) {
                            $value = isset($oneInMany[$field]) ? $oneInMany[$field] : false;
                            $obj[$tableOrField][$i][$field] = $transform($value, $obj);
                        }
                    }
                }
            }

            // translate
            if (isset($info['translate']) && $info['translate'] && isset($passIn[$tableOrField])) {
                $passIn[$info['translate']] = $passIn[$tableOrField];
                unset($passIn[$tableOrField]);
            }
        }
        return $passIn;
    }
    static function validateRequiredFields($description, $passIn){
        if (isset($description['required_fields'])) {
            foreach ($description['required_fields'] as $field) {
                if ($description['depth'] == 'one') {
                    if (!isset($passIn[$field])) {
                        throw new Exception('Missing or malformed: ' . $field);
                    }
                } else {
                    if ($description['depth'] == 'many') {
                        if (empty($passIn)) {
                            throw new Exception('Missing or malformed: ' . $field);
                        }
                        foreach ($passIn as $oneInMany) {
                            if (!isset($oneInMany[$field])) {
                                throw new Exception('Missing or malformed: ' . $field);
                            }
                        }
                    }
                }
            }
        }
    }
    static function validateAgainstTransformer($obj, $transformer, $operation = 'create', $subModel = false)
    {
        $allowSubmodelAttempt = $subModel;
        foreach ($transformer as $tableOrField => $info) {
            if (!$subModel || $tableOrField == $subModel) {
                $allowSubmodelAttempt = true;
                // if missing
                if (isset($info['required']) && $info['required'] && !isset($obj[$tableOrField]) &&
                    $operation == 'create') {
                    throw new Exception('Missing: ' . $tableOrField);
                }
                // is table or field?
                if (isset($info['depth'])) {
                    // table
                    if (isset($info['required_fields'])) {
                        foreach ($info['required_fields'] as $field) {
                            if ($info['depth'] == 'one') {
                                if (!isset($obj[$tableOrField][$field])) {
                                    throw new Exception('Missing or malformed: ' . $field);
                                }
                            } else {
                                if ($info['depth'] == 'many') {
                                    if (empty($obj[$tableOrField])) {
                                        throw new Exception('Missing or malformed: ' . $field);
                                    }
                                    foreach ($obj[$tableOrField] as $oneInMany) {
                                        if (!isset($oneInMany[$field])) {
                                            throw new Exception('Missing or malformed: ' . $field);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if (isset($info['on_creation']) && $operation == 'create') {
                        foreach ($info['on_creation'] as $field => $transform) {
                            if ($info['depth'] == 'one') {
                                $value = isset($obj[$tableOrField][$field]) ? $obj[$tableOrField][$field] : false;
                                $obj[$tableOrField][$field] = $transform($value, $obj);
                            } else {
                                foreach ($obj[$tableOrField] as $i => $oneInMany) {
                                    $value = isset($oneInMany[$field]) ? $oneInMany[$field] : false;
                                    $obj[$tableOrField][$i][$field] = $transform($value, $obj);
                                }
                            }
                        }
                    }
                    if (isset($info['on_update'])) {
                        foreach ($info['on_update'] as $field => $transform) {
                            if ($info['depth'] == 'one') {
                                $value = isset($obj[$tableOrField][$field]) ? $obj[$tableOrField][$field] : false;
                                $obj[$tableOrField][$field] = $transform($value, $obj);
                            } else {
                                foreach ($obj[$tableOrField] as $i => $oneInMany) {
                                    $value = isset($oneInMany[$field]) ? $oneInMany[$field] : false;
                                    $obj[$tableOrField][$i][$field] = $transform($value, $obj);
                                }
                            }
                        }
                    }
                } else {
                    // value
                    if (isset($info['on_creation']) && $operation == 'create') {
                        $obj[$tableOrField] =
                            $info['on_creation'](isset($obj[$tableOrField]) ? $obj[$tableOrField] : false, $obj);
                    }
                    if (isset($info['on_update']) && $operation == 'update') {
                        $obj[$tableOrField] =
                            $info['on_update'](isset($obj[$tableOrField]) ? $obj[$tableOrField] : false, $obj);
                    }
                }
                // translate?
                if (isset($info['translate']) && $info['translate'] && isset($obj[$tableOrField])) {
                    $obj[$info['translate']] = $obj[$tableOrField];
                    unset($obj[$tableOrField]);
                }
            }

        }
        if (!$allowSubmodelAttempt) {
            throw new Exception('Partial model does not fit description');
        }

        return $obj;
    }
}
