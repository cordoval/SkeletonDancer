<?php

namespace Rollerworks\Tools\SkeletonDancer;

use Rollerworks\Tools\SkeletonDancer\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

final class GenerateCommand extends Command
{
    private static $types = ['library', 'extension', 'symfony-bundle', 'project'];

    protected function configure()
    {
        $this
            ->setName('generate')
            ->setDescription('Generates an empty project in the current directory')
            ->addOption(
                'type',
                null,
                InputOption::VALUE_REQUIRED,
                'Type of the project ('.implode(', ', self::$types).')'
            )
            ->addOption(
                'name',
                null,
                InputOption::VALUE_REQUIRED,
                'Title of the project, eg: Rollerworks Search Pomm Extension'
            )
            ->addOption(
                'namespace',
                null,
                InputOption::VALUE_REQUIRED,
                'PHP Namespace of the project'
            )
            ->addOption(
                'author',
                null,
                InputOption::VALUE_REQUIRED,
                'Author of the project (used for composer and license)',
                'Sebastiaan Stok <s.stok@rollerscapes.net>' // Problem? :-)
            )
            ->addOption(
                'php-min',
                null,
                InputOption::VALUE_REQUIRED,
                'Minimum required PHP version',
                '5.5'
            )

            // Special options
            ->addOption(
                'doc-format',
                null,
                InputOption::VALUE_REQUIRED,
                'Documentation format (rst, markdown, none)',
                'markdown'
            )
            ->addOption(
                'enable-phpunit',
                null,
                InputOption::VALUE_NONE
            )
            ->addOption(
                'enable-phpspec',
                null,
                InputOption::VALUE_NONE
            )
            ->addOption(
                'enable-sf-test-bridge',
                null,
                InputOption::VALUE_NONE
            )
            ->addOption(
                'external-issues',
                null,
                InputOption::VALUE_NONE
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        $style->title('SkeletonDancer - PHP Project bootstrapping');
        $style->block('Before we start I need to something about your project, please fill in all the questions.');

        $input->setOption('type', $style->askQuestion(new ChoiceQuestion('Type', self::$types, $input->getOption('type'))));
        $input->setOption('name', $style->askQuestion(new Question('Name', $input->getOption('name'))));
        $input->setOption('namespace', $style->askQuestion(new Question('Namespace', $input->getOption('namespace'))));
        $input->setOption('author', $style->askQuestion(new Question('Author', $input->getOption('author'))));
        $input->setOption('php-min', $style->askQuestion(new Question('Php-min', $input->getOption('php-min'))));
        $input->setOption('doc-format', $style->askQuestion(new ChoiceQuestion('Documentation format', ['rst', 'markdown', 'none'], array_search($input->getOption('doc-format'), ['rst', 'markdown', 'none']))));

        if (!$input->getOption('enable-phpunit') && $style->askQuestion(new ConfirmationQuestion('Enable PHPUnit?'))) {
            $input->setOption('enable-phpunit', true);
        }

        if (!$input->getOption('enable-phpspec') && $style->askQuestion(new ConfirmationQuestion('Enable PHPSpec'))) {
            $input->setOption('enable-phpspec', true);
        }

        if (!$input->getOption('enable-sf-test-bridge') && $style->askQuestion(new ConfirmationQuestion('Enable Symfony PHPUnit bridge'))) {
            $input->setOption('enable-sf-test-bridge', true);
        }

        // XXX Only for extension type
        if (!$input->getOption('external-issues') && $style->askQuestion(new ConfirmationQuestion('external-issues'))) {
            $input->setOption('external-issues', true);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filesystem = new Filesystem();
        $twig = new \Twig_Environment(
            new \Twig_Loader_Filesystem(__DIR__.'/../Resources/Templates'),
            [
                'debug' => true,
                'cache' => new \Twig_Cache_Filesystem(__DIR__.'/../temp'),
                'autoescape' => 'filename',
            ]
        );

        $workingDir = getcwd();

        $style = new SymfonyStyle($input, $output);
        $style->text('Start dancing, this may take a while...');

        (new Generator\ComposerGenerator($twig, $filesystem))->generate(
            $input->getOption('namespace'),
            $input->getOption('type'),
            'MIT', //$input->getOption('license')
            $input->getOption('author'),
            $input->getOption('php-min'),
            $workingDir
        );

        $style->success('Done, enjoy!');
    }
}