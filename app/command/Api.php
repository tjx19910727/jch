<?php
declare (strict_types = 1);

namespace app\command;

use app\AppFactory\AppFactory;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

class Api extends Command
{
    /**
     * 定时任务配置信息
     * 设置两个参数：moduleType，actionType
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('api')
            ->addArgument("moduleType",Argument::OPTIONAL,'time task module type')
            ->addArgument("actionType",Argument::OPTIONAL,'time task action type')
            ->setDescription('the time_task command');
    }

    /**
     * 定时任务
     *
     * php /home/wwwroot/kiosk/think api callback trigger_send              对外API推送通知
     *
     * command
     *      php think api [moduleType] [actionType]
     * moduleType     callback：推送回调通知数据
     * actionType     trigger_send：触发循环最多8次的推送
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        $result = '未定义命令类型';
        try {
            $argument = $input->getArguments();
            if (isset($argument['moduleType']) && isset($argument['actionType'])) {
                $module = $argument['moduleType'];
                $action = $argument['actionType'];
                $app = AppFactory::api();
                $result = $app->$module->$action();
            }// 指令输出
            $output->writeln($result);
        } catch (\Exception $e) {
            actionException($e,1);
//             指令输出
            $output->writeln($e->getMessage());
        }
    }
}
