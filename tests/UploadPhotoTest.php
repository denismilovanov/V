<?php

require_once 'bootstrap.php';

class UploadPhotoTest extends TestCase {

    public function test1() {

        $access = Bootstrap::getAccess($this);

        // создаем картинку

        $image = '/tmp/image.jpg';
        $im = imagecreatetruecolor(120, 20);
        $text_color = imagecolorallocate($im, 233, 14, 91);
        imagestring($im, 1, 5, 5,  'A Simple Text String', $text_color);
        imagejpeg($im, $image);

        // закачиваем картинку

        $result = Bootstrap::postFromApi('uploadPhoto', array(
            'key' => $access['key'],
            'photo' => base64_encode(file_get_contents($image)),
            'extension' => 'jpeg',
        ));

        $this->assertEquals($result['status'], 1);

        // выкидываем

        unlink($image);

    }

}
