<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;
use App\Models\UsersIndex;
use App\Models\Helper;


class UpdateProfileVKCommand extends \LaravelSingleInstanceCommand\Command
{
    public $name = 'update_profile_vk';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
        $this->checkInstance($input);

        $tag = 'update_profile_vk' . mt_rand();
        $jobs = 0;

        \Queue::subscribe('update_profile_vk', $tag, function (RabbitMQJob $job) use (& $jobs, $tag) {
            $json = $job->getRawBody();
            \Log::info(json_encode(json_decode($json), JSON_UNESCAPED_UNICODE));

            $data = json_decode($json, 'assoc')['data'];

            \Log::info('Начали ' . $jobs);

            if (UsersIndex::updateProfileVk($data['user_id']) !== false) {
                \Log::info('Завершили задание');
                $job->delete();
            } else {
                \Log::info('Возвращаем на обработку');
                $job->release(60);
            }

            Helper::closeDBConnections();

            if (++ $jobs == 100) {
                \Queue::unsubscribe($tag);
            }
        });

        \Log::info('Завершили подписку ' . $tag);

        Pusher::disconnect();
        Helper::closeDBConnections();

        return 0;
    }

}
