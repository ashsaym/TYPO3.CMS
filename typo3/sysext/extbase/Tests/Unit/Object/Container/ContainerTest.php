<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Object\Container;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Extbase\Object\Container\Container;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\Object\Exception\CannotBuildObjectException;
use TYPO3\CMS\Extbase\Reflection\Exception\UnknownClassException;
use TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClassForPublicPropertyInjection;
use TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ProtectedPropertyInjectClass;
use TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\PublicPropertyInjectClass;

/**
 * Test case
 */
class ContainerTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\Container\Container
     */
    protected $container;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var \TYPO3\CMS\Extbase\Object\Container\ClassInfo
     */
    protected $cachedClassInfo;

    protected function setUp()
    {
        // The mocked cache will always indicate that he has nothing in the cache to force that we get the real class info
        $mockedCache = $this->getMockBuilder(\TYPO3\CMS\Extbase\Object\Container\ClassInfoCache::class)
            ->setMethods(['get', 'set', 'has'])
            ->getMock();
        $mockedCache->expects($this->any())->method('get')->will($this->returnValue(false));
        $mockedCache->expects($this->never())->method('has');

        $this->logger = $this->getMockBuilder(Logger::class)
            ->setMethods(['notice'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = $this->getMockBuilder(\TYPO3\CMS\Extbase\Object\Container\Container::class)
            ->setMethods(['getLogger', 'getClassInfoCache'])
            ->getMock();
        $this->container->expects($this->any())->method('getClassInfoCache')->will($this->returnValue($mockedCache));
        $this->container->expects($this->any())->method('getLogger')->will($this->returnValue($this->logger));
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfSimpleClass()
    {
        $object = $this->container->getInstance('t3lib_object_tests_c');
        $this->assertInstanceOf('t3lib_object_tests_c', $object);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfSimpleNamespacedClass()
    {
        $object = $this->container->getInstance(\TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\NamespacedClass::class);
        $this->assertInstanceOf(\TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\NamespacedClass::class, $object);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfAClassWithConstructorInjection()
    {
        $object = $this->container->getInstance('t3lib_object_tests_b');
        $this->assertInstanceOf('t3lib_object_tests_b', $object);
        $this->assertInstanceOf('t3lib_object_tests_c', $object->c);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfAClassWithTwoLevelDependency()
    {
        $object = $this->container->getInstance('t3lib_object_tests_a');
        $this->assertInstanceOf('t3lib_object_tests_a', $object);
        $this->assertInstanceOf('t3lib_object_tests_c', $object->b->c);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfAClassWithMixedSimpleTypeAndConstructorInjection()
    {
        $object = $this->container->getInstance('t3lib_object_tests_amixed_array');
        $this->assertInstanceOf('t3lib_object_tests_amixed_array', $object);
        $this->assertEquals(['some' => 'default'], $object->myvalue);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfAClassWithMixedSimpleTypeAndConstructorInjectionWithNullDefaultValue()
    {
        $object = $this->container->getInstance('t3lib_object_tests_amixed_null');
        $this->assertInstanceOf('t3lib_object_tests_amixed_null', $object);
        $this->assertNull($object->myvalue);
    }

    /**
     * @test
     */
    public function getInstanceThrowsExceptionWhenTryingToInstanciateASingletonWithConstructorParameters()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1292858051);
        $this->container->getInstance('t3lib_object_tests_amixed_array_singleton', ['somevalue']);
    }

    /**
     * @test
     */
    public function getInstanceReturnsInstanceOfAClassWithConstructorInjectionAndDefaultConstructorParameters()
    {
        $object = $this->container->getInstance('t3lib_object_tests_amixed_array');
        $this->assertInstanceOf('t3lib_object_tests_b', $object->b);
        $this->assertInstanceOf('t3lib_object_tests_c', $object->c);
        $this->assertEquals(['some' => 'default'], $object->myvalue);
    }

    /**
     * @test
     */
    public function getInstancePassesGivenParameterToTheNewObject()
    {
        $mockObject = $this->createMock('t3lib_object_tests_c');
        $object = $this->container->getInstance('t3lib_object_tests_a', [$mockObject]);
        $this->assertInstanceOf('t3lib_object_tests_a', $object);
        $this->assertSame($mockObject, $object->c);
    }

    /**
     * @test
     */
    public function getInstanceReturnsAFreshInstanceIfObjectIsNoSingleton()
    {
        $object1 = $this->container->getInstance('t3lib_object_tests_a');
        $object2 = $this->container->getInstance('t3lib_object_tests_a');
        $this->assertNotSame($object1, $object2);
    }

    /**
     * @test
     */
    public function getInstanceReturnsSameInstanceInstanceIfObjectIsSingleton()
    {
        $object1 = $this->container->getInstance('t3lib_object_tests_singleton');
        $object2 = $this->container->getInstance('t3lib_object_tests_singleton');
        $this->assertSame($object1, $object2);
    }

    /**
     * @test
     */
    public function getInstanceThrowsExceptionIfPrototypeObjectsWiredViaConstructorInjectionContainCyclicDependencies()
    {
        $this->expectException(CannotBuildObjectException::class);
        $this->expectExceptionCode(1295611406);
        $this->container->getInstance('t3lib_object_tests_cyclic1WithSetterDependency');
    }

    /**
     * @test
     */
    public function getInstanceThrowsExceptionIfPrototypeObjectsWiredViaSetterInjectionContainCyclicDependencies()
    {
        $this->expectException(CannotBuildObjectException::class);
        $this->expectExceptionCode(1295611406);
        $this->container->getInstance('t3lib_object_tests_cyclic1');
    }

    /**
     * @test
     */
    public function getInstanceThrowsExceptionIfClassWasNotFound()
    {
        $this->expectException(UnknownClassException::class);
        $this->expectExceptionCode(1278450972);
        $this->container->getInstance('nonextistingclass_bla');
    }

    /**
     * @test
     */
    public function getInstanceInitializesObjects()
    {
        $instance = $this->container->getInstance('t3lib_object_tests_initializable');
        $this->assertTrue($instance->isInitialized());
    }

    /**
     * @test
     */
    public function getEmptyObjectReturnsInstanceOfSimpleClass()
    {
        $object = $this->container->getEmptyObject('t3lib_object_tests_c');
        $this->assertInstanceOf('t3lib_object_tests_c', $object);
    }

    /**
     * @test
     */
    public function getEmptyObjectReturnsInstanceOfClassImplementingSerializable()
    {
        $object = $this->container->getEmptyObject('t3lib_object_tests_serializable');
        $this->assertInstanceOf('t3lib_object_tests_serializable', $object);
    }

    /**
     * @test
     */
    public function getEmptyObjectInitializesObjects()
    {
        $object = $this->container->getEmptyObject('t3lib_object_tests_initializable');
        $this->assertTrue($object->isInitialized());
    }

    /**
     * @test
     */
    public function test_canGetChildClass()
    {
        $object = $this->container->getInstance('t3lib_object_tests_b_child');
        $this->assertInstanceOf('t3lib_object_tests_b_child', $object);
    }

    /**
     * @test
     */
    public function test_canInjectInterfaceInClass()
    {
        $this->container->registerImplementation('t3lib_object_tests_someinterface', 't3lib_object_tests_someimplementation');
        $object = $this->container->getInstance('t3lib_object_tests_needsinterface');
        $this->assertInstanceOf('t3lib_object_tests_needsinterface', $object);
        $this->assertInstanceOf('t3lib_object_tests_someinterface', $object->dependency);
        $this->assertInstanceOf('t3lib_object_tests_someimplementation', $object->dependency);
    }

    /**
     * @test
     */
    public function test_canBuildCyclicDependenciesOfSingletonsWithSetter()
    {
        $object = $this->container->getInstance('t3lib_object_tests_resolveablecyclic1');
        $this->assertInstanceOf('t3lib_object_tests_resolveablecyclic1', $object);
        $this->assertInstanceOf('t3lib_object_tests_resolveablecyclic1', $object->o2->o3->o1);
    }

    /**
     * @test
     */
    public function singletonWhichRequiresPrototypeViaSetterInjectionWorksAndAddsDebugMessage()
    {
        $this->logger->expects($this->once())->method('notice')->with('The singleton "t3lib_object_singletonNeedsPrototype" needs a prototype in "injectDependency". This is often a bad code smell; often you rather want to inject a singleton.');
        $object = $this->container->getInstance('t3lib_object_singletonNeedsPrototype');
        $this->assertInstanceOf('t3lib_object_prototype', $object->dependency);
    }

    /**
     * @test
     */
    public function singletonWhichRequiresSingletonViaSetterInjectionWorks()
    {
        $this->logger->expects($this->never())->method('notice');
        $object = $this->container->getInstance('t3lib_object_singletonNeedsSingleton');
        $this->assertInstanceOf('t3lib_object_singleton', $object->dependency);
    }

    /**
     * @test
     */
    public function prototypeWhichRequiresPrototypeViaSetterInjectionWorks()
    {
        $this->logger->expects($this->never())->method('notice');
        $object = $this->container->getInstance('t3lib_object_prototypeNeedsPrototype');
        $this->assertInstanceOf('t3lib_object_prototype', $object->dependency);
    }

    /**
     * @test
     */
    public function prototypeWhichRequiresSingletonViaSetterInjectionWorks()
    {
        $this->logger->expects($this->never())->method('notice');
        $object = $this->container->getInstance('t3lib_object_prototypeNeedsSingleton');
        $this->assertInstanceOf('t3lib_object_singleton', $object->dependency);
    }

    /**
     * @test
     */
    public function singletonWhichRequiresPrototypeViaConstructorInjectionWorksAndAddsDebugMessage()
    {
        $this->logger->expects($this->once())->method('notice')->with('The singleton "t3lib_object_singletonNeedsPrototypeInConstructor" needs a prototype in the constructor. This is often a bad code smell; often you rather want to inject a singleton.');
        $object = $this->container->getInstance('t3lib_object_singletonNeedsPrototypeInConstructor');
        $this->assertInstanceOf('t3lib_object_prototype', $object->dependency);
    }

    /**
     * @test
     */
    public function singletonWhichRequiresSingletonViaConstructorInjectionWorks()
    {
        $this->logger->expects($this->never())->method('notice');
        $object = $this->container->getInstance('t3lib_object_singletonNeedsSingletonInConstructor');
        $this->assertInstanceOf('t3lib_object_singleton', $object->dependency);
    }

    /**
     * @test
     */
    public function prototypeWhichRequiresPrototypeViaConstructorInjectionWorks()
    {
        $this->logger->expects($this->never())->method('notice');
        $object = $this->container->getInstance('t3lib_object_prototypeNeedsPrototypeInConstructor');
        $this->assertInstanceOf('t3lib_object_prototype', $object->dependency);
    }

    /**
     * @test
     */
    public function prototypeWhichRequiresSingletonViaConstructorInjectionWorks()
    {
        $this->logger->expects($this->never())->method('notice');
        $object = $this->container->getInstance('t3lib_object_prototypeNeedsSingletonInConstructor');
        $this->assertInstanceOf('t3lib_object_singleton', $object->dependency);
    }

    /**
     * @test
     */
    public function isSingletonReturnsTrueForSingletonInstancesAndFalseForPrototypes()
    {
        $this->assertTrue($this->container->isSingleton(\TYPO3\CMS\Extbase\Object\Container\Container::class));
        $this->assertFalse($this->container->isSingleton(\TYPO3\CMS\Extbase\Core\Bootstrap::class));
    }

    /**
     * @test
     */
    public function isPrototypeReturnsFalseForSingletonInstancesAndTrueForPrototypes()
    {
        $this->assertFalse($this->container->isPrototype(\TYPO3\CMS\Extbase\Object\Container\Container::class));
        $this->assertTrue($this->container->isPrototype(\TYPO3\CMS\Extbase\Core\Bootstrap::class));
    }

    /************************************************
     * Test regarding constructor argument injection
     ************************************************/

    /**
     * test class SimpleTypeConstructorArgument
     * @test
     */
    public function getInstanceGivesSimpleConstructorArgumentToClassInstance()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\SimpleTypeConstructorArgument::class,
            [true]
        );
        $this->assertTrue($object->foo);
    }

    /**
     * test class SimpleTypeConstructorArgument
     * @test
     */
    public function getInstanceDoesNotInfluenceSimpleTypeConstructorArgumentIfNotGiven()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\SimpleTypeConstructorArgument::class
        );
        $this->assertFalse($object->foo);
    }

    /**
     * test class MandatoryConstructorArgument
     * @test
     */
    public function getInstanceGivesExistingConstructorArgumentToClassInstance()
    {
        $argumentTestClass = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgument::class,
            [$argumentTestClass]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgument::class,
            $object
        );
        $this->assertSame($argumentTestClass, $object->argumentTestClass);
    }

    /**
     * test class MandatoryConstructorArgument
     * @test
     */
    public function getInstanceInjectsNewInstanceOfClassToClassIfArgumentIsMandatory()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgument::class
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgument::class,
            $object
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClass
        );
    }

    /**
     * test class OptionalConstructorArgument
     * @test
     */
    public function getInstanceDoesNotInjectAnOptionalArgumentIfNotGiven()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\OptionalConstructorArgument::class
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\OptionalConstructorArgument::class,
            $object
        );
        $this->assertNull($object->argumentTestClass);
    }

    /**
     * test class OptionalConstructorArgument
     * @test
     */
    public function getInstanceDoesNotInjectAnOptionalArgumentIfGivenArgumentIsNull()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\OptionalConstructorArgument::class,
            [null]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\OptionalConstructorArgument::class,
            $object
        );
        $this->assertNull($object->argumentTestClass);
    }

    /**
     * test class OptionalConstructorArgument
     * @test
     */
    public function getInstanceGivesExistingConstructorArgumentToClassInstanceIfArgumentIsGiven()
    {
        $argumentTestClass = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\OptionalConstructorArgument::class,
            [$argumentTestClass]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\OptionalConstructorArgument::class,
            $object
        );
        $this->assertSame($argumentTestClass, $object->argumentTestClass);
    }

    /**
     * test class MandatoryConstructorArgumentTwo
     * @test
     */
    public function getInstanceGivesTwoArgumentsToClassConstructor()
    {
        $firstArgument = new Fixtures\ArgumentTestClass();
        $secondArgument = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo::class,
            [$firstArgument, $secondArgument]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo::class,
            $object
        );
        $this->assertSame(
            $firstArgument,
            $object->argumentTestClass
        );
        $this->assertSame(
            $secondArgument,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class MandatoryConstructorArgumentTwo
     * @test
     */
    public function getInstanceInjectsTwoMandatoryArguments()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo::class
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo::class,
            $object
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClass
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
        $this->assertNotSame(
            $object->argumentTestClass,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class MandatoryConstructorArgumentTwo
     * @test
     */
    public function getInstanceInjectsSecondMandatoryArgumentIfFirstIsGiven()
    {
        $firstArgument = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo::class,
            [$firstArgument]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo::class,
            $object
        );
        $this->assertSame(
            $firstArgument,
            $object->argumentTestClass
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
        $this->assertNotSame(
            $object->argumentTestClass,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class MandatoryConstructorArgumentTwo
     * @test
     */
    public function getInstanceInjectsFirstMandatoryArgumentIfSecondIsGiven()
    {
        $secondArgument = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo::class,
            [null, $secondArgument]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\MandatoryConstructorArgumentTwo::class,
            $object
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClass
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
        $this->assertSame(
            $secondArgument,
            $object->argumentTestClassTwo
        );
        $this->assertNotSame(
            $object->argumentTestClass,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsSecondOptional
     * @test
     */
    public function getInstanceGivesTwoArgumentsToClassConstructorIfSecondIsOptional()
    {
        $firstArgument = new Fixtures\ArgumentTestClass();
        $secondArgument = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            [$firstArgument, $secondArgument]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            $object
        );
        $this->assertSame(
            $firstArgument,
            $object->argumentTestClass
        );
        $this->assertSame(
            $secondArgument,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsSecondOptional
     * @test
     */
    public function getInstanceInjectsFirstMandatoryArgumentIfSecondIsOptionalAndNoneAreGiven()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            $object
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClass
        );
        $this->assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsSecondOptional
     * @test
     */
    public function getInstanceInjectsFirstMandatoryArgumentIfSecondIsOptionalAndBothAreGivenAsNull()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            [null, null]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            $object
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClass
        );
        $this->assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsSecondOptional
     * @test
     */
    public function getInstanceGivesFirstArgumentToConstructorIfSecondIsOptionalAndFirstIsGiven()
    {
        $firstArgument = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            [$firstArgument]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            $object
        );
        $this->assertSame(
            $firstArgument,
            $object->argumentTestClass
        );
        $this->assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsSecondOptional
     * @test
     */
    public function getInstanceGivesFirstArgumentToConstructorIfSecondIsOptionalFirstIsGivenAndSecondIsGivenNull()
    {
        $firstArgument = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            [$firstArgument, null]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsSecondOptional::class,
            $object
        );
        $this->assertSame(
            $firstArgument,
            $object->argumentTestClass
        );
        $this->assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsFirstOptional
     *
     * @test
     */
    public function getInstanceOnFirstOptionalAndSecondMandatoryInjectsSecondArgumentIfFirstIsGivenAsNull()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            [null]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            $object
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsFirstOptional
     * @test
     */
    public function getInstanceOnFirstOptionalAndSecondMandatoryGivesTwoGivenArgumentsToConstructor()
    {
        $first = new Fixtures\ArgumentTestClass();
        $second = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            [$first, $second]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            $object
        );
        $this->assertSame(
            $first,
            $object->argumentTestClass
        );
        $this->assertSame(
            $second,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsFirstOptional
     * @test
     */
    public function getInstanceOnFirstOptionalAndSecondMandatoryInjectsSecondArgumentIfFirstIsGiven()
    {
        $first = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            [$first]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            $object
        );
        $this->assertSame(
            $first,
            $object->argumentTestClass
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
        $this->assertNotSame(
            $object->argumentTestClass,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsFirstOptional
     *
     * @test
     */
    public function getInstanceOnFirstOptionalAndSecondMandatoryGivesSecondArgumentAsIsIfFirstIsGivenAsNullAndSecondIsGiven()
    {
        $second = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            [null, $second]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            $object
        );
        $this->assertSame(
            $second,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsFirstOptional
     *
     * @test
     */
    public function getInstanceOnFirstOptionalAndSecondMandatoryInjectsSecondArgumentIfFirstIsGivenAsNullAndSecondIsNull()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            [null, null]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsFirstOptional::class,
            $object
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClass::class,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesTwoGivenArgumentsToConstructor()
    {
        $first = new Fixtures\ArgumentTestClass();
        $second = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            [$first, $second]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            $object
        );
        $this->assertSame(
            $first,
            $object->argumentTestClass
        );
        $this->assertSame(
            $second,
            $object->argumentTestClassTwo
        );
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesNoArgumentsToConstructorIfArgumentsAreNull()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            [null, null]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            $object
        );
        $this->assertNull($object->argumentTestClass);
        $this->assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesNoArgumentsToConstructorIfNoneAreGiven()
    {
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            $object
        );
        $this->assertNull($object->argumentTestClass);
        $this->assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesOneArgumentToConstructorIfFirstIsObjectAndSecondIsNotGiven()
    {
        $first = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            [$first]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            $object
        );
        $this->assertSame(
            $first,
            $object->argumentTestClass
        );
        $this->assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesOneArgumentToConstructorIfFirstIsObjectAndSecondIsNull()
    {
        $first = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            [$first, null]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            $object
        );
        $this->assertSame(
            $first,
            $object->argumentTestClass
        );
        $this->assertNull($object->argumentTestClassTwo);
    }

    /**
     * test class TwoConstructorArgumentsBothOptional
     * @test
     */
    public function getInstanceOnTwoOptionalGivesOneArgumentToConstructorIfFirstIsNullAndSecondIsObject()
    {
        $second = new Fixtures\ArgumentTestClass();
        $object = $this->container->getInstance(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            [null, $second]
        );
        $this->assertInstanceOf(
            \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\TwoConstructorArgumentsBothOptional::class,
            $object
        );
        $this->assertNull($object->argumentTestClass);
        $this->assertSame(
            $second,
            $object->argumentTestClassTwo
        );
    }

    /**
     * @test
     */
    public function getInstanceInjectsPublicProperties()
    {
        $container = new Container();
        $object = $container->getInstance(PublicPropertyInjectClass::class);
        self::assertInstanceOf(ArgumentTestClassForPublicPropertyInjection::class, $object->foo);
    }

    /**
     * @test
     */
    public function getInstanceInjectsProtectedProperties()
    {
        $container = new Container();
        $object = $container->getInstance(ProtectedPropertyInjectClass::class);
        self::assertInstanceOf(ArgumentTestClassForPublicPropertyInjection::class, $object->getFoo());
    }
}
