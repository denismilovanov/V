<?php

require_once 'bootstrap.php';

class ErrorCollectorTest extends TestCase {

    public function test1() {
        \App\Models\ErrorCollector::addError('FOR_TESTS', 'Для тестов', 'Для тестов');
    }

}
