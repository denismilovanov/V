<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\Likes;
use \App\Models\Helper;

class EchoLikesCommand extends \LaravelSingleInstanceCommand\Command
{
    public $name = 'echo_likes';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->checkInstance($input);

        $tag = 'echo_likes' . mt_rand();
        $jobs = 0;

        \Queue::subscribe('echo_likes', $tag, function (RabbitMQJob $job) use (& $jobs, $tag) {
            $json = $job->getRawBody();
            \Log::info($json);

            $data = json_decode($json, 'assoc')['data'];

            \Log::info('Начали ' . $jobs);

            if ($result = \App\Models\Likes::echoLike($data)) {
                \Log::info('Завершили: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                $job->delete();
            }

            Helper::closeDBConnections();

            if (++ $jobs == 10000) {
                \Queue::unsubscribe($tag);
            }
        });

        \Log::info('Завершили подписку ' . $tag);
        Helper::closeDBConnections();
    }
}
