<?php

require_once 'bootstrap.php';

use \App\Models\Helper;

mb_internal_encoding("UTF-8");

class DeclensionsTest extends TestCase {

    public function test1() {
        $names = [
            'Александра' => ['Александрой', 'Aлександры'],
            'алекса' => ['Алексой', 'Алексы'],
            'Александр' => ['Александром', 'Aлександра'],
            'Юлия' => ['Юлией', 'Юлии'],
            'сергей' => ['Сергеем', 'Сергея'],
            'Паша' => ['Пашей', 'Паши'],
            'Виктор' => ['Виктором', 'Виктора'],
        ];

        foreach ($names as $name => $decl) {
            $this->assertEquals(Helper::casusInstrumentalis($name, null), $decl[0]);
            $this->assertEquals(Helper::genitivus($name, null), $decl[1]);
        }
    }

}
