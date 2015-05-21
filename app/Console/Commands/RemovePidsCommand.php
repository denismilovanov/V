<?php namespace App\Console\Commands;

class RemovePidsCommand extends \App\Console\SingleCommand {
    public $name = 'remove_pids';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
        $pids = glob('/tmp/' . APP_ENV . '/*.pid');

        sort($pids);

        foreach ($pids as $pid_file) {
            // см. коммент ниже
            if (! file_exists($pid_file)) {
                continue;
            }

            $pid = file_get_contents($pid_file);

            $result = exec('ps --pid ' . $pid);
            $does_not_work = strpos($result, $pid) === false;

            if ($does_not_work) {
                \Log::info('Удаляем: ' . $pid_file . ' -> ' . $pid);

                // вот так вот... glob говорит, что файл есть, а file_exists
                // говорит, что его нет
                // значит, файл утащили в промежутке между ними
                // ни файла, ни процесса... красота :)
                if (file_exists($pid_file)) {
                    unlink($pid_file);
                }
            }

        }

        return 0;
    }

}
