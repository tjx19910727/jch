<?php
declare (strict_types = 1);

namespace app\command;

use app\AppFactory\RabbitMq\MqConsumer;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class ExportQueue extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('ExportQueue')
            ->setDescription('the comer command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
//        $output->writeln('comer');
        try {
            $consumer = new MqConsumer();
            $consumer->export_queue();
        } catch (\Exception $e) {
            actionException($e,1);
            echo $e->getMessage();
        }
    }
}
