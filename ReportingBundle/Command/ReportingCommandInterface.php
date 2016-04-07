<?php
namespace ReportingBundle\Command;


use Symfony\Component\Console\Output\OutputInterface;

interface ReportingCommandInterface
{
    public function process(array $providers, \DateTime $start, \DateTime $end, OutputInterface $output);
}