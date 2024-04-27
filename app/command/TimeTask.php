<?php
declare (strict_types = 1);

namespace app\command;

use app\AppFactory\AppFactory;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

class TimeTask extends Command
{
    /**
     * 定时任务配置信息
     * 设置两个参数：moduleType，actionType
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('time_task')
            ->addArgument("moduleType",Argument::OPTIONAL,'time task module type')
            ->addArgument("actionType",Argument::OPTIONAL,'time task action type')
            ->setDescription('the time_task command');
    }

    /**
     * 定时任务
     *
     * php /home/wwwroot/kiosk/think time_task machine countOnline              结算昨天在线数据，每天定时任务运行一次
     * php /home/wwwroot/kiosk/think time_task machine checkOffline             检查设备最后心跳时间判断在线离线，每隔30秒执行一次定时任务，判断120秒内未更新心跳的为离线
     *
     * command
     *      php think time_task [moduleType] [actionType]
     * moduleType     machine：设备定时任务，goods：商品定时任务
     * actionType
     *      machine：
     *          countOnline                 结算设备昨天在线数据
     *          checkOffline                检查设备最后心跳时间判断在线离线
     *      machineChannelStock
     *          countMcStock                统计库存报表
     *      goods：
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
                $app = AppFactory::timeTask();
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
