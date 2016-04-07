# symfony-reporting-bundle
Simple Reporting Bundle for Symfony 2

This is a simple module for time series reporting in Symfony 2. You have to read the code and modify for usage.
In general, this bundle will help you prepare data for reporting daily, weekly, monthly and annually very quickly.
The concept is there are providers that gather data in one day, then aggregate data for that month or year.

Sample files:
* PaymentReportProvider: gather data from payment table
* PaymentReportingController: sample controller for using reporting bundle. It prepares data for google charts.
* services.yml: define PaymentReportProvider as a service with tag 'reporting.provider'
<pre>
services:

    payment.reporting.provider:
            class: Reporting\Provider\PaymentReportProvider
            calls:
                - [ setServiceContainer, [@service_container] ]
            tags:
                - { name: reporting.provider }
</pre>

Finally, setup cronjob for gather command:
<pre>
0 0 * * * php app/console reporting:gather >> cron.txt 2>&1
</pre>
