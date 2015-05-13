<?php namespace App\Models;

class Helper
{
    public static function softVersionFromStringToInt($string) {
        $soft_version = explode(".", $string);

        if (! isset($soft_version[0], $soft_version[1], $soft_version[2])) {
            return false;
        }

        return $soft_version = $soft_version[0] . sprintf('%02d', $soft_version[1]) . sprintf('%02d', $soft_version[2]);
    }

    public static function softVersionFromIntToString($int) {
        $minor2 = substr($int, -2, 2);
        $minor1 = substr($int, -4, 2);
        $major = substr($int, 0, -4);
        return (int) $major . '.' . (int)$minor1 . '.' . (int)$minor2;
    }

    private static $casusInstrumentalis = array(
        'саша' => 'Сашей',

        'анастасия' => 'Анастасией',
        'юлия' => 'Юлией',
        'мария' => 'Марией',
        'анна' => 'Анной',
        'екатерина' => 'Екатериной',
        'виктория' => 'Викторией',
        'кристина' => 'Кристиной',
        'ольга' => 'Ольгой',
        'ирина' => 'Ириной',
        'елена' => 'Еленой',
        'татьяна' => 'Татьяной',
        'светлана' => 'Светланой',
        'настя' => 'Настей',
        'ксения' => 'Ксенией',
        'дарья' => 'Дарьей',
        'алина' => 'Алиной',
        'наталья' => 'Натальей',
        'марина' => 'Мариной',
        'евгения' => 'Евгенией',
        'валерия' => 'Валерией',
        'катя' => 'Катей',
        'даша' => 'Дашей',
        'аня' => 'Аней',
        'полина' => 'Полиной',
        'яна' => 'Яной',
        'юля' => 'Юлей',
        'диана' => 'Дианой',
        'карина' => 'Кариной',
        'алена' => 'Аленой',
        'алёна' => 'Алёной',
        'елизавета' => 'Елизаветой',
        'маша' => 'Машей',
        'маргарита' => 'Маргаритой',
        'наташа' => 'Наташей',
        'катерина' => 'Катериной',
        'оля' => 'Олей',
        'софья' => 'Софьей',
        'софия' => 'Софией',

        'сергей' => 'Сергеем',
        'дмитрий' => 'Дмитрием',
        'андрей' => 'Андреем',
        'алексей' => 'Алексеем',
        'евгений' => 'Евгением',
        'максим' => 'Максимом',
        'денис' => 'Денисом',
        'антон' => 'Антоном',
        'роман' => 'Романом',
        'илья' => 'Ильей',
        'иван' => 'Иваном',
        'никита' => 'Никитой',
        'игорь' => 'Игорем',
        'дима' => 'Димой',
        'павел' => 'Павлом',
        'олег' => 'Олегом',
        'владимир' => 'Владимиром',
        'кирилл' => 'Кириллом',
        'михаил' => 'Михаилом',
        'николай' => 'Николаем',
        'артём' => 'Артемом',
        'руслан' => 'Русланом',
        'виталий' => 'Виталием',
        'владислав' => 'Владиславом',
        'вадим' => 'Вадимом',
        'влад' => 'Владом',
        'константин' => 'Константином',
        'егор' => 'Егором',
        'стас' => 'Стасом',
        'станислав' => 'Станиславом',
    );

    public static function casusInstrumentalis($name, $sex) {
        $nameL = mb_strtolower($name);
        if ($nameL == 'александр') {
            return $sex == 1 ? 'александрой' : 'александром';
        }
        return isset(self::$casusInstrumentalis[$nameL]) ? self::$casusInstrumentalis[$nameL] : $name;
    }
};
