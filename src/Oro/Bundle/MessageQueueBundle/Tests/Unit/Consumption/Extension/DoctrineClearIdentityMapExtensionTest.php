<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\DoctrineClearIdentityMapExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class DoctrineClearIdentityMapExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DoctrineClearIdentityMapExtension($this->createRegistryMock());
    }

    public function testShouldClearIdentityMap()
    {
        $manager = $this->createManagerMock();
        $manager
            ->expects($this->once())
            ->method('clear')
        ;

        $registry = $this->createRegistryMock();
        $registry
            ->expects($this->once())
            ->method('getManagers')
            ->will($this->returnValue(['manager-name' => $manager]))
        ;

        $context = $this->createContext();
        $context->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with('Clear identity map for manager "manager-name"')
        ;

        $extension = new DoctrineClearIdentityMapExtension($registry);
        $extension->onPreReceived($context);
    }

    /**
     * @return Context
     */
    protected function createContext()
    {
        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($this->createMock(LoggerInterface::class));
        $context->setMessageConsumer($this->createMock(MessageConsumerInterface::class));
        $context->setMessageProcessor($this->createMock(MessageProcessorInterface::class));

        return $context;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RegistryInterface
     */
    protected function createRegistryMock()
    {
        return $this->createMock(RegistryInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected function createManagerMock()
    {
        return $this->createMock(ObjectManager::class);
    }
}
