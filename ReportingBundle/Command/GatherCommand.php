<?php
namespace ReportingBundle\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

class GatherCommand extends AbstractReportingCommand implements ReportingCommandInterface
{
    protected function configure()
    {
        parent::configure();
        $this->setDescription('Gathers reports data.');
    }

    public function process(array $providers, \DateTime $start, \DateTime $end, OutputInterface $output)
    {
        foreach($providers as $id => $provider) {
            try {
                $output->write(sprintf('<comment>%s</comment>: ',$provider->__toString()));
                $provider->gather($start, $end);
                $output->writeln('Finished.');
            } catch(\Exception $e) {
                $output->writeln(sprintf('Failed. Error: <comment>%s</comment>.',$e->getMessage()));
            }
        }
    }

    protected function getEventName()
    {
        return 'gather';
    }
}