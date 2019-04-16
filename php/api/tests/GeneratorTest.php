<?php


namespace Datahouse\MON\Tests;

/**
 * Class GeneratorTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $files = glob(__DIR__ . '/json/*.json');
        foreach ($files as $fileName) {
            $file = file_get_contents($fileName);
            $json = json_decode($file, true);
            $classes = $this->classFinder($json);

            foreach ($classes as $className => $params) {
                $this->createClassFile($className, $params);
            }
        }
        $genFiles = glob(__DIR__ . '/srcGen/*.php');
        $this->assertTrue(count($genFiles) > 0);

        $cmd = 'cp -r ' . __DIR__ . '/srcGen/*.php ' . __DIR__ .
            '/../src/Types/Gen/';
        shell_exec($cmd);
    }

    /**
     * createClassFile
     *
     * @param string $className the class name
     * @param array  $params    the field list
     *
     * @return void
     */
    private function createClassFile($className, $params)
    {
        $template = file_get_contents(__DIR__ . '/templates/class.templ');
        $template = str_replace('{CLASS_NAME}', ucfirst($className), $template);
        $classFile =
            fopen(__DIR__ . '/srcGen/' . ucfirst($className) . '.php', 'w');
        $vars = '';
        $setter = '';
        $getter = '';
        foreach ($params as $param) {
            $bool = '';
            if ($param == 'readOnly') {
                $bool = ' = false ';
            }
            $vars .= PHP_EOL . "    " . 'public $' . $param . $bool . ';';

            $getTemplate =
                file_get_contents(__DIR__ . '/templates/getter.templ');
            $getTemplate =
                str_replace('{PARAM_NAME}', $param, $getTemplate);
            $getter .= PHP_EOL . PHP_EOL;
            $getter .= str_replace(
                '{METHOD_NAME}',
                ucfirst($param),
                $getTemplate
            );
            $setTemplate =
                file_get_contents(__DIR__ . '/templates/setter.templ');
            $setTemplate =
                str_replace('{PARAM_NAME}', $param, $setTemplate);
            $setter .= PHP_EOL . PHP_EOL;
            $setter .= str_replace(
                '{METHOD_NAME}',
                ucfirst($param),
                $setTemplate
            );
        }
        $template = str_replace('{PARAM}', $vars, $template);
        $template = str_replace('{GETTER}', $getter, $template);
        $template = str_replace('{SETTER}', $setter, $template);
        fwrite($classFile, $template);
        fclose($classFile);
    }

    /**
     * classFinder
     *
     * iterating through the json schema and looking for classes
     *
     * @param array $json the json schema
     *
     * @return array
     */
    private function classFinder($json)
    {
        $classes = array();
        foreach ($json as $key => $values) {
            if ($this->isClass($key, $values)) {
                $className = $this->getClassName(array_keys($values));
                $fieldList = $this->getFieldList($values[$className]);
                $classes[$className] = $fieldList;
            }
            if (is_array($values)) {
                $classes =
                    array_merge(
                        $this->classFinder($values),
                        $classes
                    );
            }
        }
        return $classes;
    }

    /**
     * isClassName
     * checks if this is a class
     * true if the first property has an stereotype struct
     *
     * @param string $key    the key
     * @param array  $values the values
     *
     * @return bool
     */
    private function isClass($key, $values)
    {
        //TODO remove dependency to properties
        if ($key === 'properties') {
            foreach (reset($values) as $name => $value) {
                if ($name == 'stereotype' && $value == 'struct') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * getClassName
     * returns the class name
     *
     * @param array $names the names
     *
     * @return string class name
     */
    private function getClassName($names)
    {
        return $names[0];
    }

    /**
     * getFieldList
     *
     * reads the field list of a class
     * meaning all properties keys
     *
     * @param array $values the values
     *
     * @return array
     */
    private function getFieldList($values)
    {
        foreach ($values as $key => $value) {
            if ($key === 'properties') {
                return array_keys($value);
            }
        }
    }

    /**
     * prepare test data
     *
     * @return void
     */
    protected function setUp()
    {
        $genFiles = glob(__DIR__ . '/srcGen/*.php');
        foreach ($genFiles as $genFile) {
            if (is_file($genFile)) {
                // delete file
                unlink($genFile);
            }
        }
        $genFiles = glob(__DIR__ . '/srcGen/*.php');
        $this->assertTrue(count($genFiles) == 0);

        $genFiles = glob(__DIR__ . '/../src/Types/Gen/*.php');
        foreach ($genFiles as $genFile) {
            if (is_file($genFile)) {
                // delete file
                unlink($genFile);
            }
        }
        $genFiles = glob(__DIR__ . '/../src/Types/Gen/*.php');
        $this->assertTrue(count($genFiles) == 0);
    }

    /**
     * tearDown
     *
     * @return void
     */
    protected function tearDown()
    {
        $genFiles = glob(__DIR__ . '/srcGen/*.php');
        foreach ($genFiles as $genFile) {
            if (is_file($genFile)) {
                // delete file
                unlink($genFile);
            }
        }
    }
}
