<?php
namespace ReportingBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractReportingCommand extends ContainerAwareCommand implements ReportingCommandInterface
{
    protected function configure()
    {
        $this->setName(sprintf("reporting:%s", $this->getEventName()))
            ->setDescription("Reporting command")
            ->addOption('start', null, InputOption::VALUE_OPTIONAL, 'start is date expression, format Y-m-d', $this->getDefaultStart())
            ->addOption('end', null, InputOption::VALUE_OPTIONAL, 'end is date expression, format Y-m-d', $this->getDefaultEnd())
            ->addOption('providers', null, InputOption::VALUE_OPTIONAL, 'run specified providers by id, separated by ","')
            ->addOption('exclude', null, InputOption::VALUE_OPTIONAL, 'not run specified providers by id');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = $input->getOption('start');
        $end = $input->getOption('end');
        $runProviders = $input->getOption('providers');
        $excludeProviders = $input->getOption('exclude');
        
        $output->writeln('Fiding all reporting providers tagged "reporting.provider"');
        $providers = $this->getContainer()->get('reporting.provider.chain')->getProviders();
        if ($runProviders != "") {
            $runProviders = explode(",", $runProviders);
            foreach ($providers as $id => $provider) {
                if (!in_array($id, $runProviders)) {
                    unset($providers[$id]);
                }
            }
        }
        if($excludeProviders != "") {
            if (!in_array($excludeProviders, $providers)) {
               unset($providers[$excludeProviders]);
            }
        }

        $output->writeln('<info>'.count($providers).' reporting providers found</info>');
        if (empty($providers)) {
            return;
        }

        $start = new \Datetime($start);
        $start->setTime(0, 0, 0);
        $end = new \Datetime($end);
        $end->setTime(0, 0, 0);

        $output->writeln('Gathering reports ...');
        $this->process($providers, $start, $end, $output);
        $output->writeln('Completed!');
    }

    abstract protected function getEventName();

    public function getDefaultStart()
    {
        return "-1 day";
    }

    public function getDefaultEnd()
    {
        return "";
    }
}