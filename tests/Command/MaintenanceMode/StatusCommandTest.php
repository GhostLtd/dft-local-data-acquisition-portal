<?php

namespace App\Tests\Command\MaintenanceMode;

use App\Command\MaintenanceMode\StatusCommand;
use App\Repository\Utility\MaintenanceLockRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StatusCommandTest extends TestCase
{
    /** @var MaintenanceLockRepository&MockObject */
    private MaintenanceLockRepository $maintenanceLockRepository;
    /** @var InputInterface&MockObject */
    private InputInterface $input;
    /** @var OutputInterface&MockObject */
    private OutputInterface $output;
    private StatusCommand $command;

    protected function setUp(): void
    {
        $this->maintenanceLockRepository = $this->createMock(MaintenanceLockRepository::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->command = new StatusCommand($this->maintenanceLockRepository);
    }

    /**
     * @dataProvider maintenanceStatusProvider
     */
    public function testExecute(bool $isActive, array $whitelistedIps): void
    {
        $this->maintenanceLockRepository->expects($this->once())
            ->method('getIsActiveAndWhitelistedIps')
            ->willReturn([$isActive, $whitelistedIps]);

        $result = $this->command->run($this->input, $this->output);

        $this->assertSame(Command::SUCCESS, $result);
    }

    public function maintenanceStatusProvider(): array
    {
        return [
            'maintenance not active' => [false, []],
            'maintenance active with no IPs' => [true, []],
            'maintenance active with single IP' => [true, ['192.168.1.1']],
            'maintenance active with multiple IPs' => [true, ['192.168.1.1', '10.0.0.1', '172.16.0.1']],
            'maintenance active with localhost' => [true, ['127.0.0.1']],
        ];
    }

    public function testCommandConfiguration(): void
    {
        $this->assertSame('ldap:maintenance-mode:status', $this->command->getName());
        $this->assertSame('check the maintenance status', $this->command->getDescription());
    }

    public function testStatusWhenNotActive(): void
    {
        $this->maintenanceLockRepository->expects($this->once())
            ->method('getIsActiveAndWhitelistedIps')
            ->willReturn([false, []]);

        $result = $this->command->run($this->input, $this->output);
        $this->assertSame(Command::SUCCESS, $result);
    }

    public function testStatusWhenActiveWithMultipleIps(): void
    {
        $ips = ['192.168.1.1', '10.0.0.1', '172.16.0.1'];
        $this->maintenanceLockRepository->expects($this->once())
            ->method('getIsActiveAndWhitelistedIps')
            ->willReturn([true, $ips]);

        $result = $this->command->run($this->input, $this->output);
        $this->assertSame(Command::SUCCESS, $result);
    }

    public function testStatusWhenActiveWithEmptyIpList(): void
    {
        $this->maintenanceLockRepository->expects($this->once())
            ->method('getIsActiveAndWhitelistedIps')
            ->willReturn([true, []]);

        $result = $this->command->run($this->input, $this->output);
        $this->assertSame(Command::SUCCESS, $result);
    }
}