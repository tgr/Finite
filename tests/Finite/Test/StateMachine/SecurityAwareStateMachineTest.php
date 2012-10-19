<?php

namespace Finite\Test\StateMachine;

use Finite\StateMachine\SecurityAwareStateMachine;

/**
 * @author Yohan Giarelli <yohan@frequence-web.fr>
 */
class SecurityAwareStateMachineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SecurityAwareStateMachine
     */
    protected $object;

    protected function setUp()
    {
        $statefulMock = $this->getMock('Finite\StatefulInterface');
        $statefulMock->expects($this->any())->method('getFiniteState')->will($this->returnValue('s1'));

        $this->object = new SecurityAwareStateMachine($statefulMock);
        $this->object->setEventDispatcher($this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface'));
        $this->object->addTransition('t12', 's1', 's2');
        $this->object->addTransition('t23', 's2', 's3');
        $this->object->initialize();
    }

    public function testCan()
    {
        if (!function_exists('trait_exists')) {
            $this->markTestSkipped('This test needs PHP5.4+');
        }

        $securityMock = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $this->object->setSecurityContext($securityMock);

        $addIsGrandedExpectation = function($return, $transition) use($securityMock) {
            static $at = 0;

            $securityMock
                ->expects($this->at($at++))
                ->method('isGranted')
                ->with($transition, $this->object->getObject())
                ->will($this->returnValue($return));
        };

        $addIsGrandedExpectation(true, 't12');
        $addIsGrandedExpectation(true, 't23');
        $addIsGrandedExpectation(false, 't12');
        $addIsGrandedExpectation(true, 't23');


        $this->assertTrue($this->object->can('t12'));
        $this->assertFalse($this->object->can('t23'));
        $this->assertFalse($this->object->can('t12'));
        $this->assertFalse($this->object->can('t23'));
    }

}
