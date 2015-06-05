<?php namespace App\Console\Commands;

use FintechFab\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use \App\Models\Helper;

class SendErrorsCommand extends \LaravelSingleInstanceCommand\Command
{
    public $name = 'send_errors';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->checkInstance($input);

        $tag = 'send_errors' . mt_rand();
        $jobs = 0;

        \Queue::subscribe('send_errors', $tag, function (RabbitMQJob $job) use (& $jobs, $tag) {
            $json = $job->getRawBody();

            $data = json_decode($json, 'assoc')['data'];
            $data_without_message = $data;
            unset($data_without_message['message']);

            \Log::info('Начали ' . json_encode($data_without_message, JSON_UNESCAPED_UNICODE));

            if ($result = self::sendError($data)) {
                \Log::info('Завершили: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                $job->delete();
            } else {
                $job->release(60);
            }

            Helper::closeDBConnections();

            if (++ $jobs == 1000) {
                \Queue::unsubscribe($tag);
            }
        });

        \Log::info('Завершили подписку ' . $tag);
        Helper::closeDBConnections();
    }

    private static function sendError($data) {
        try {
            $message = \Swift_Message::newInstance()
                ->setSubject($data['header'])
                ->setFrom(array('postmaster@vmeste-app.ru' => 'Postmaster'))
                ->setTo([env('DEVELOPER_EMAIL')])
                ->setBody($data['message']);

            $transport = \Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');

            $mailer = \Swift_Mailer::newInstance($transport);

            $result = $mailer->send($message);

            return $result;
        } catch (\Exception $e) {
            \Log::info($e->getMessage());
            return false;
        }
    }
}
