<?php
declare (strict_types = 1);

namespace app\command;

use app\AppFactory\RabbitMq\MachineConsumer;
use app\AppFactory\RabbitMq\MqConsumer;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class MachineReceive extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('MachineReceive')
            ->setDescription('the comer command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
//        $output->writeln('comer');
        $consumer = new MachineConsumer();
        $consumer->consumer();
    }
}
