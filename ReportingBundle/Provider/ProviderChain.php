<?php
namespace ReportingBundle\Provider;


class ProviderChain
{
    protected $providers=array();

    public function getProviders()
    {
        return $this->providers;
    }

    public function add($id, ReportingProviderInterface $provider)
    {
        $this->providers[$id] = $provider;
    }
}