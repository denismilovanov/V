<?php namespace App\Console\Commands;

class RemovePidsCommand extends \App\Console\SingleCommand {
    public $name = 'remove_pids';

    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
        $pids = glob('/tmp/' . APP_ENV . '/*.pid');

        sort($pids);

        foreach ($pids as $pid_file) {
            $pid = file_get_contents($pid_file);

            $result = exec('ps --pid ' . $pid);
            $does_not_works = strpos($pid, $result) === false;

            if ($does_not_works) {
                unlink($pid_file);
                \Log::info('Удаляем: ' . $pid_file . ' -> ' . $pid);
            }

        }

        return 0;
    }

}
