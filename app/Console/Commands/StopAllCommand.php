<?php namespace App\Console\Commands;

class StopAllCommand extends \App\Console\SingleCommand {
    public $name = 'stop_all';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
        $pids = glob('/tmp/' . APP_ENV . '/*.pid');

        sort($pids);

        foreach ($pids as $pid_file) {
            $remove_pid = false;

            $pid = file_get_contents($pid_file);
            if (is_numeric($pid)) {
                $result = posix_kill($pid, 15);
                \Log::info('Тормозим: ' . $pid_file . ' -> ' . $pid);
                if ($result) {
                    \Log::info('Успешно');
                } else {
                    \Log::info('Нет такого процесса');
                    $remove_pid = true;
                }
            } else {
                \Log::info('Внутри файла нет числа');
                $remove_pid = true;
            }

            if ($remove_pid and file_exists($pid_file)) {
                unlink($pid_file);
                \Log::info('Удаляем: ' . $pid_file);
            }
        }

        return 0;
    }

}
