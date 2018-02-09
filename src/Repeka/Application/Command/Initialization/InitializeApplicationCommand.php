<?php
namespace Repeka\Application\Command\Initialization;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeApplicationCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('repeka:initialize')
            ->setDescription('Initializes data in database required for the app to work properly.')
            ->addOption('skip-backup');
    }

    /** @inheritdoc */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->getApplication()->setAutoExit(false);
        $this->getApplication()->run(new StringInput('repeka:prepare-database'), $output);
        $this->getApplication()->setCatchExceptions(false);
        if (!$input->getOption('skip-backup')) {
            $this->getApplication()->run(new StringInput('db:backup'), $output);
        }
        $this->getApplication()->run(new StringInput('doctrine:migrations:migrate --no-interaction --allow-no-migration'), $output);
        $this->getApplication()->run(new StringInput('repeka:initialize:system-languages'), $output);
        $this->getApplication()->run(new StringInput('repeka:initialize:system-metadata'), $output);
        $this->getApplication()->run(new StringInput('repeka:initialize:system-resource-kinds'), $output);
        $this->getApplication()->run(new StringInput('repeka:initialize:system-user-roles'), $output);
        $this->getApplication()->run(new StringInput('repeka:initialize:user-metadata'), $output);
    }
}
