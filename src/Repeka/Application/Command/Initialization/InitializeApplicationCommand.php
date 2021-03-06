<?php
namespace Repeka\Application\Command\Initialization;

use Repeka\Application\Cqrs\Middleware\FirewallMiddleware;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeApplicationCommand extends ContainerAwareCommand {
    /** @var iterable|Command[] */
    private $initializationCommands;

    public function __construct(iterable $initializationCommands) {
        parent::__construct();
        $this->initializationCommands = $initializationCommands;
    }

    protected function configure() {
        $this
            ->setName('repeka:initialize')
            ->setDescription('Initializes data in database required for the app to work properly.')
            ->addOption('skip-backup')
            ->addOption('skip-migrations');
    }

    /** @inheritdoc */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->getApplication()->setAutoExit(false);
        $this->getApplication()->run(new StringInput('repeka:prepare-database'), $output);
        $this->getApplication()->setCatchExceptions(false);
        if (!$input->getOption('skip-backup')) {
            $this->getApplication()->run(new StringInput('db:backup'), $output);
        }
        if (!$input->getOption('skip-migrations')) {
            $this->getApplication()->run(new StringInput('doctrine:migrations:migrate --no-interaction --allow-no-migration'), $output);
        }
        FirewallMiddleware::bypass(
            function () use ($output) {
                foreach ($this->initializationCommands as $initializationCommand) {
                    $this->getApplication()->run(new StringInput($initializationCommand->getName()), $output);
                }
            }
        );
    }
}
