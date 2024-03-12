<?php
declare (strict_types = 1);

namespace app\command;

use app\AppFactory\RabbitMq\MqConsumer;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class DataUpload extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('DataUpload')
            ->setDescription('the comer command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
//        $output->writeln('comer');
        $consumer = new MqConsumer();
        $consumer->dataUpload();
    }
}
