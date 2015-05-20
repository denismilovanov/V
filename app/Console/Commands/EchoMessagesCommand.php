<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\Messages;

class EchoMessagesCommand extends \Illuminate\Console\Command
{
    public $name = 'echo_messages';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $tag = 'echo_messages' . mt_rand();
        $jobs = 0;

        \Queue::subscribe('echo', $tag, function (RabbitMQJob $job) use (& $jobs, $tag) {
            $json = $job->getRawBody();
            \Log::info($json);

            $data = json_decode($json, 'assoc')['data'];

            \Log::info('Начали' . $jobs);

            if ($result = \App\Models\Messages::echoMessage($data)) {
                \Log::info('Завершили: ' . json_encode($result));
                $job->delete();
            }

            if (++ $jobs == 10000) {
                \Queue::unsubscribe($tag);
            }
        });

        \Log::info('Завершили ' . $tag);
    }
}
