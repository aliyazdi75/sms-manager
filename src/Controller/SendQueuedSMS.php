<?php
/**
 * Created by PhpStorm.
 * User: Ali
 * Date: 22/02/2019
 * Time: 01:05 AM
 */

namespace App\Controller;

use App\Controller\SMSController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @Cron(minute="/1")
 */
class SendQueuedSMS extends Command
{
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $failSMS = new SMSController();
        foreach ($failSMS->getFailSMSArray() as $sms) {
            $failSMS->SendingProcess($sms);
        }
    }
}