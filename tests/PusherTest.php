<?php

require_once 'bootstrap.php';

use \App\Models\Pusher;

class EmailTest extends TestCase {

    public function test1() {
        $message = \Swift_Message::newInstance()
            ->setSubject('Привет от юнит-теста')
            ->setFrom(array('postmaster@vmeste-app.ru' => 'Postmaster'))
            ->setTo([env('DEVELOPER_EMAIL')])
            ->setBody('Привет от юнит-теста');

        $transport = \Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');

        $mailer = \Swift_Mailer::newInstance($transport);

        $result = $mailer->send($message);
    }

}
