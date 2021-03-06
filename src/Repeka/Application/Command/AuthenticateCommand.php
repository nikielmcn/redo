<?php
namespace Repeka\Application\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AuthenticateCommand extends Command {
    /** @var AuthenticationManagerInterface */
    private $authenticationManager;

    public function __construct(AuthenticationManagerInterface $authenticationManager) {
        $this->authenticationManager = $authenticationManager;
        parent::__construct();
    }

    protected function configure() {
        $this
            ->setName('repeka:authenticate')
            ->addArgument('username', InputArgument::OPTIONAL)
            ->addArgument('password', InputArgument::OPTIONAL)
            ->setDescription('Verifies if the supplied credentials are valid.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $helper = $this->getHelper('question');
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        if (!$username) {
            $username = $helper->ask($input, $output, new Question('Username: '));
            $password = $helper->ask($input, $output, (new Question('Password: '))->setHidden(true)->setHiddenFallback(false));
        }
        $result = $this->authenticate($username, $password);
        $output->writeln($result);
    }

    private function authenticate(string $username, string $password): string {
        $token = new UsernamePasswordToken($username, $password, 'main');
        try {
            $authenticatedToken = $this->authenticationManager->authenticate($token);
            $user = $authenticatedToken->getUser();
            return "Credentials valid.\nUser: {$user->getUsername()} ({$user->getId()})";
        } catch (AuthenticationException $e) {
            return "Credentials invalid.\n" . $e->getMessage();
        }
    }
}
