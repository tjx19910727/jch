<?php
declare (strict_types = 1);

namespace app\command;

use Bluerhinos\phpMQTT;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Mqtt extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('mqtt')
            ->setDescription('the mqtt command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
//        $output->writeln('mqtt');
    }

    protected function subscribe()
    {
        $server = "";
        $port = 1883;
        $clientId = uniqid();
        $topic = "mqtt-12345";
        $username = "";
        $pwd = "";
        $mqtt = new phpMQTT($server,$port,$clientId);
        if (!$mqtt->connect(true,NULL,$username,$pwd)) {
            exit(1);
        }
        $mqtt->debug = true;

        $topics[$topic] = array("qos" => 0, "function" => array($this,"procMsg"));
        $mqtt->subscribe($topics,0);
        while($mqtt->proc()) {

        }
        $mqtt->close();
    }
}
