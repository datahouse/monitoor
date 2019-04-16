<?php

namespace Datahouse\MON\Router;

/**
 * Class Rule
 *
 * @package Router
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class Rule implements \Datahouse\Framework\Router\Rule
{

    const NAMESPACE_BASE = 'Datahouse\\MON\\';
    const FRAMEWORK_ROUTE = 'Datahouse\\Framework\\Router\\Route';
    const NAMESPACE_SEPARATOR = '\\';
    /**
     * @var \Dice\Dice
     */
    private $dice;

    /**
     * @param \Dice\Dice $dice the dice obj
     */
    public function __construct(\Dice\Dice $dice)
    {
        $this->dice = $dice;
    }

    /**
     * find
     *
     * @param array $route the routes
     *
     * @return bool
     */
    public function find(array $route)
    {
        //version
        array_shift($route);
        $api = ucfirst(array_shift($route));
        $className =
            self::NAMESPACE_BASE . $api . self::NAMESPACE_SEPARATOR .
            ucfirst(array_shift($route));
        $viewName = self::NAMESPACE_BASE . 'View';
        if (!class_exists($viewName)) {
            return false;
        }
        $controllerName = $className . self::NAMESPACE_SEPARATOR . 'Controller';
        $modelName = $className . self::NAMESPACE_SEPARATOR . 'Model';
        $viewModelName = $className . self::NAMESPACE_SEPARATOR . 'ViewModel';

        $rule = new \Dice\Rule;
        $rule->constructParams[] = new \Dice\Instance($viewName);
        if (class_exists($controllerName)) {
            $rule->constructParams[] = new \Dice\Instance($controllerName);
        }

        if (class_exists($modelName)) {
            $rule->shareInstances[] = $modelName;
        }
        if (class_exists($viewModelName)) {
            $rule->shareInstances[] = $viewModelName;
        }

        $this->dice->addRule(self::FRAMEWORK_ROUTE, $rule);

        $rule = new \Dice\Rule;
        $rule->substitutions['ViewModel'] = new \Dice\Instance($viewModelName);
        $this->dice->addRule($viewName, $rule);

        return $this->dice->create(self::FRAMEWORK_ROUTE);
    }
}
