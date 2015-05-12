<?php

require_once 'bootstrap.php';

class IOSPushTest extends TestCase {

    public function test1() {
        return;

        $push = new ApnsPHP_Push(
            ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
            PROTECTED_PATH . '/config/ck_develop.pem'
        );
        $push->setProviderCertificatePassphrase('qwerty');
        $push->connect();

        $message = new ApnsPHP_Message('e7a43b2c4e6834f1d266f094e9ad50c00b3d133136a463e046c2f641e594c1d8'); //0a96344b909af2718c2cbe250842b578a770e9bf45a90110c1cb4db67ba5a05f');
        $message->setText('Юнит-тест пройден!');
        $message->setExpiry(30);
        $message->setBadge(5);
        $message->setSound();
        //$message->setCustomProperty('acme1', 'bar');
        //$message->setCustomProperty('acme2', 42);

        $push->add($message);

        $push->send();
        $push->disconnect();

        $aErrorQueue = $push->getErrors();
        if (!empty($aErrorQueue)) {
            var_dump($aErrorQueue);
        }
    }

}
