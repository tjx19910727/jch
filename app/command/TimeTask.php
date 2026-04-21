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
     * php /home/wwwroot/kiosk/think time_task authManagerLog clearLog          删除180天前的用户事件记录，每月或每周或每天定时任务运行一次
     * php /home/wwwroot/kiosk/think time_task machine countOnline              结算昨天在线数据，每天定时任务运行一次
     * php /home/wwwroot/kiosk/think time_task machine checkOffline             检查设备最后心跳时间判断在线离线，每隔1分钟执行一次定时任务，判断30秒内未更新心跳的为离线
     * php /home/wwwroot/kiosk/think time_task machine checkOnOff               检查当天设置定时开关机设备是否正常执行，若不正常则重发临时断电开关机任务做执行
     * php /home/wwwroot/kiosk/think time_task export clearExcel                清除超过3天的Excel，每天定时任务运行一次
     * php /home/wwwroot/kiosk/think time_task coupon clearCouponUsed           清除已过期或已作废未使用的优惠券码，每天定时任务运行一次
     *
     * command
     *      php think time_task [moduleType] [actionType]
     * moduleType     machine：设备定时任务，goods：商品定时任务，export：导出， activity：营销活动
     * actionType
     *      authManagerLog：
     *          clearLog                    删除180天前的用户事件记录
     *      machine：
     *          countOnline                 结算设备昨天在线数据
     *          checkOffline                检查设备最后心跳时间判断在线离线
     *          checkOnOff                  检查设备定时开关机是否正常
    *          updateSimCardUsage          每天0点同步物联卡基础信息并统计流量
     *      machineChannelStock
     *          countMcStock                统计库存报表，已废弃，使用实时获取
     *      goods：
     *          updateGoodsSynchronization  同步商品信息，守护进程触发命令
     *          updateMgSynchronization     同步设备商品库信息，守护进程触发命令
     *      export：
     *          clearExcel                  清除超过3天的Excel
     *      coupon：
     *          clearCouponUsed             清除已过期或已作废未使用的优惠券码
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
